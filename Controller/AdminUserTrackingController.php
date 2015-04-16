<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Controller;

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\Home\HomeTabConfig;
use Claroline\CoreBundle\Form\HomeTabConfigType;
use Claroline\CoreBundle\Form\HomeTabType;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\WidgetManager;
use Claroline\UserTrackingBundle\Manager\UserTrackingManager;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("canOpenAdminTool('claroline_user_tracking_admin_tool')")
 */
class AdminUserTrackingController extends Controller
{
    private $formFactory;
    private $homeTabManager;
    private $request;
    private $userTrackingManager;
    private $widgetManager;

    /**
     * @DI\InjectParams({
     *     "formFactory"         = @DI\Inject("form.factory"),
     *     "homeTabManager"      = @DI\Inject("claroline.manager.home_tab_manager"),
     *     "requestStack"        = @DI\Inject("request_stack"),
     *     "userTrackingManager" = @DI\Inject("claroline.manager.user_tracking_manager"),
     *     "widgetManager"       = @DI\Inject("claroline.manager.widget_manager")
     * })
     */
    public function __construct(
        FormFactory $formFactory,
        HomeTabManager $homeTabManager,
        RequestStack $requestStack,
        UserTrackingManager $userTrackingManager,
        WidgetManager $widgetManager
    )
    {
        $this->formFactory = $formFactory;
        $this->homeTabManager = $homeTabManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->userTrackingManager = $userTrackingManager;
        $this->widgetManager = $widgetManager;
    }

    /**
     * @EXT\Route(
     *     "/administration/index/tab/{homeTabId}",
     *     name="claro_user_tracking_administration_index",
     *     defaults={"homeTabId" = -1},
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template()
     */
    public function administrationIndexAction($homeTabId = -1)
    {
        $homeTabConfigs = $this->homeTabManager
            ->getHomeTabConfigsByType('admin_user_tracking');
        $tabId = intval($homeTabId);
        $widgets = array();
        $firstElement = true;
        $initWidgetsPosition = false;

        if ($tabId !== -1) {

            foreach ($homeTabConfigs as $homeTabConfig) {

                if ($tabId === $homeTabConfig->getHomeTab()->getId()) {
                    $firstElement = false;
                    break;
                }
            }
        }

        if ($firstElement) {
            $firstHomeTabConfig = reset($homeTabConfigs);

            if ($firstHomeTabConfig) {
                $tabId = $firstHomeTabConfig->getHomeTab()->getId();
            }
        }
        $homeTab = $this->homeTabManager->getHomeTabByIdAndType(
            $tabId,
            'admin_user_tracking'
        );
        $widgetHomeTabConfigs = is_null($homeTab) ?
            array() :
            $this->homeTabManager->getWidgetHomeTabConfigsByHomeTabAndType(
                $homeTab,
                'admin_user_tracking'
            );
        $wdcs = $this->widgetManager->generateWidgetDisplayConfigsForAdmin($widgetHomeTabConfigs);

        foreach ($wdcs as $wdc) {

            if ($wdc->getRow() === -1 || $wdc->getColumn() === -1) {
                $initWidgetsPosition = true;
                break;
            }
        }

        foreach ($widgetHomeTabConfigs as $widgetHomeTabConfig) {
            $widgetInstance = $widgetHomeTabConfig->getWidgetInstance();

            $event = $this->eventDispatcher->dispatch(
                "widget_{$widgetInstance->getWidget()->getName()}",
                'DisplayWidget',
                array($widgetInstance)
            );

            $widget['config'] = $widgetHomeTabConfig;
            $widget['content'] = $event->getContent();
            $widgetInstanceId = $widgetHomeTabConfig->getWidgetInstance()->getId();
            $widget['widgetDisplayConfig'] = $wdcs[$widgetInstanceId];
            $widgets[] = $widget;
        }

        return array(
            'curentHomeTabId' => $tabId,
            'homeTabConfigs' => $homeTabConfigs,
            'widgetsDatas' => $widgets,
            'initWidgetsPosition' => $initWidgetsPosition
        );
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/create/form",
     *     name="claro_user_tracking_admin_tab_create_form",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminTabCreateModalForm.html.twig")
     */
    public function adminTabCreateFormAction()
    {
        $homeTabForm = $this->formFactory->create(
            new HomeTabType(null, true),
            new HomeTab()
        );
        $homeTabConfigForm = $this->formFactory->create(
            new HomeTabConfigType(true),
            new HomeTabConfig()
        );

        return array(
            'homeTabForm' => $homeTabForm->createView(),
            'homeTabConfigForm' => $homeTabConfigForm->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/create",
     *     name="claro_user_tracking_admin_tab_create",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminTabCreateModalForm.html.twig")
     */
    public function adminTabCreateAction()
    {
        $homeTab = new HomeTab();
        $homeTabConfig = new HomeTabConfig();
        $homeTabForm = $this->formFactory->create(
            new HomeTabType(null, true),
            $homeTab
        );
        $homeTabConfigForm = $this->formFactory->create(
            new HomeTabConfigType(true),
            $homeTabConfig
        );
        $homeTabForm->handleRequest($this->request);
        $homeTabConfigForm->handleRequest($this->request);

        if ($homeTabForm->isValid() && $homeTabConfigForm->isValid()) {
            $homeTab->setType('admin_user_tracking');
            $this->homeTabManager->insertHomeTab($homeTab);

            $homeTabConfig->setHomeTab($homeTab);
            $homeTabConfig->setType('admin_user_tracking');

            $lastOrder = $this->homeTabManager
                ->getOrderOfLastHomeTabByType('admin_user_tracking');

            if (is_null($lastOrder['order_max'])) {
                $homeTabConfig->setTabOrder(1);
            } else {
                $homeTabConfig->setTabOrder($lastOrder['order_max'] + 1);
            }
            $this->homeTabManager->persistHomeTabConfigs($homeTab, $homeTabConfig);

            return new JsonResponse($homeTab->getId(), 200);
        } else {

            return array(
                'homeTabForm' => $homeTabForm->createView(),
                'homeTabConfigForm' => $homeTabConfigForm->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/{homeTab}/config/{homeTabConfig}/edit/form",
     *     name="claro_user_tracking_admin_tab_edit_form",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminTabEditModalForm.html.twig")
     */
    public function adminTabEditFormAction(HomeTab $homeTab, HomeTabConfig $homeTabConfig)
    {
        $this->checkHomeTab($homeTab);
        $this->checkHomeTabConfig($homeTabConfig);

        $homeTabForm = $this->formFactory->create(
            new HomeTabType(null, true),
            $homeTab
        );
        $homeTabConfigForm = $this->formFactory->create(
            new HomeTabConfigType(true),
            $homeTabConfig
        );

        return array(
            'homeTab' => $homeTab,
            'homeTabConfig' => $homeTabConfig,
            'homeTabForm' => $homeTabForm->createView(),
            'homeTabConfigForm' => $homeTabConfigForm->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/{homeTab}/config/{homeTabConfig}/edit",
     *     name="claro_user_tracking_admin_tab_edit",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminTabEditModalForm.html.twig")
     */
    public function adminTabEditAction(HomeTab $homeTab, HomeTabConfig $homeTabConfig)
    {
        $this->checkHomeTab($homeTab);
        $this->checkHomeTabConfig($homeTabConfig);

        $homeTabForm = $this->formFactory->create(
            new HomeTabType(null, true),
            $homeTab
        );
        $homeTabConfigForm = $this->formFactory->create(
            new HomeTabConfigType(true),
            $homeTabConfig
        );
        $homeTabForm->handleRequest($this->request);
        $homeTabConfigForm->handleRequest($this->request);

        if ($homeTabForm->isValid() && $homeTabConfigForm->isValid()) {
            $this->homeTabManager->persistHomeTabConfigs($homeTab, $homeTabConfig);
            $visibility = $homeTabConfig->isVisible() ? 'visible' : 'hidden';
            $lock = $homeTabConfig->isLocked() ? 'locked' : 'unlocked';

            return new JsonResponse(
                array(
                    'id' => $homeTab->getId(),
                    'name' => $homeTab->getName(),
                    'visibility' => $visibility,
                    'lock' => $lock
                ),
                200
            );
        } else {

            return array(
                'homeTab' => $homeTab,
                'homeTabConfig' => $homeTabConfig,
                'homeTabForm' => $homeTabForm->createView(),
                'homeTabConfigForm' => $homeTabConfigForm->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/{homeTab}/delete",
     *     name="claro_user_tracking_admin_tab_delete",
     *     options = {"expose"=true}
     * )
     *
     * @return Response
     */
    public function adminTabDeleteAction(HomeTab $homeTab)
    {
        $this->checkHomeTab($homeTab);
        $this->homeTabManager->deleteHomeTab($homeTab);

        return new Response('success', 204);
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/config/{homeTabConfig}/reorder/next/{nextHomeTabConfigId}",
     *     name="claro_user_tracking_admin_tab_config_reorder",
     *     options = {"expose"=true}
     * )
     * @EXT\Method("POST")
     *
     * @return Response
     */
    public function adminTabConfigReorderAction(
        HomeTabConfig $homeTabConfig,
        $nextHomeTabConfigId
    )
    {
        $this->checkHomeTabConfig($homeTabConfig);
        $homeTab = $homeTabConfig->getHomeTab();
        $this->checkHomeTab($homeTab);

        $this->homeTabManager->reorderHomeTabConfigsByType(
            'admin_user_tracking',
            $homeTabConfig,
            $nextHomeTabConfigId
        );

        return new Response('success', 200);
    }

    private function checkHomeTab(HomeTab $homeTab)
    {
        if (!is_null($homeTab->getUser()) ||
            !is_null($homeTab->getWorkspace()) ||
            $homeTab->getType() !== 'admin_user_tracking') {

            throw new AccessDeniedException();
        }
    }

    private function checkHomeTabConfig(HomeTabConfig $homeTabConfig)
    {
        if (!is_null($homeTabConfig->getUser()) ||
            !is_null($homeTabConfig->getWorkspace()) ||
            $homeTabConfig->getType() !== 'admin_user_tracking') {

            throw new AccessDeniedException();
        }
    }
}
