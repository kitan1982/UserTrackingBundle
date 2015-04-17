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
use Claroline\CoreBundle\Entity\Widget\WidgetDisplayConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetHomeTabConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Form\HomeTabConfigType;
use Claroline\CoreBundle\Form\HomeTabType;
use Claroline\CoreBundle\Form\WidgetDisplayConfigType;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\WidgetManager;
use Claroline\UserTrackingBundle\Form\UserTrackingConfigurationType;
use Claroline\UserTrackingBundle\Form\WidgetInstanceType;
use Claroline\UserTrackingBundle\Manager\UserTrackingManager;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("canOpenAdminTool('claroline_user_tracking_admin_tool')")
 */
class AdminUserTrackingController extends Controller
{
    private $eventDispatcher;
    private $formFactory;
    private $homeTabManager;
    private $request;
    private $router;
    private $translator;
    private $userTrackingConfig;
    private $userTrackingManager;
    private $widgetManager;

    /**
     * @DI\InjectParams({
     *     "eventDispatcher"     = @DI\Inject("claroline.event.event_dispatcher"),
     *     "formFactory"         = @DI\Inject("form.factory"),
     *     "homeTabManager"      = @DI\Inject("claroline.manager.home_tab_manager"),
     *     "requestStack"        = @DI\Inject("request_stack"),
     *     "router"              = @DI\Inject("router"),
     *     "translator"          = @DI\Inject("translator"),
     *     "userTrackingManager" = @DI\Inject("claroline.manager.user_tracking_manager"),
     *     "widgetManager"       = @DI\Inject("claroline.manager.widget_manager")
     * })
     */
    public function __construct(
        StrictDispatcher $eventDispatcher,
        FormFactory $formFactory,
        HomeTabManager $homeTabManager,
        RequestStack $requestStack,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserTrackingManager $userTrackingManager,
        WidgetManager $widgetManager
    )
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->homeTabManager = $homeTabManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->translator = $translator;
        $this->userTrackingConfig = $userTrackingManager->getConfiguration();
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
        $canAddWidgets = count($this->userTrackingConfig->getWidgets()) > 0;

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
            'initWidgetsPosition' => $initWidgetsPosition,
            'canAddWidgets' => $canAddWidgets
        );
    }

    /**
     * @EXT\Route(
     *     "/administration/configure/form",
     *     name="claro_user_tracking_configure_form",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:userTrackingConfigureForm.html.twig")
     */
    public function userTrackingConfigureFormAction()
    {
        $form = $this->formFactory->create(
            new UserTrackingConfigurationType($this->translator),
            $this->userTrackingConfig
       );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/administration/configure",
     *     name="claro_user_tracking_configure",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:userTrackingConfigureForm.html.twig")
     */
    public function userTrackingConfigureAction()
    {
        $form = $this->formFactory->create(
            new UserTrackingConfigurationType($this->translator),
            $this->userTrackingConfig
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->userTrackingManager->persistConfiguration($this->userTrackingConfig);

            return new RedirectResponse(
                $this->router->generate('claro_user_tracking_administration_index')
            );
        } else {

            return array('form' => $form->createView());
        }
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

    /**
     * @EXT\Route(
     *     "/administration/tab/{homeTab}/widget/instance/create/form",
     *     name="claro_user_tracking_admin_widget_instance_create_form",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminWidgetInstanceCreateModalForm.html.twig")
     */
    public function adminWidgetInstanceCreateFormAction(HomeTab $homeTab)
    {
        $instanceForm = $this->formFactory->create(
            new WidgetInstanceType($this->userTrackingConfig),
            new WidgetInstance()
        );
        $displayConfigForm = $this->formFactory->create(
            new WidgetDisplayConfigType(),
            new WidgetDisplayConfig()
        );

        return array(
            'homeTab' => $homeTab,
            'instanceForm' => $instanceForm->createView(),
            'displayConfigForm' => $displayConfigForm->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "/administration/tab/{homeTab}/widget/instance/create",
     *     name="claro_user_tracking_admin_widget_instance_create",
     *     options = {"expose"=true}
     * )
     * @EXT\Template("ClarolineUserTrackingBundle:AdminUserTracking:adminWidgetInstanceCreateModalForm.html.twig")
     */
    public function adminWidgetInstanceCreateAction(HomeTab $homeTab)
    {
        $widgetInstance = new WidgetInstance();
        $widgetDisplayConfig = new WidgetDisplayConfig();

        $instanceForm = $this->formFactory->create(
            new WidgetInstanceType($this->userTrackingConfig),
            $widgetInstance
        );
        $displayConfigForm = $this->formFactory->create(
            new WidgetDisplayConfigType(),
            $widgetDisplayConfig
        );
        $instanceForm->handleRequest($this->request);
        $displayConfigForm->handleRequest($this->request);

        if ($instanceForm->isValid() && $displayConfigForm->isValid()) {

            $widgetInstance->setIsAdmin(true);
            $widgetInstance->setIsDesktop(false);
            $widgetHomeTabConfig = new WidgetHomeTabConfig();
            $widgetHomeTabConfig->setHomeTab($homeTab);
            $widgetHomeTabConfig->setWidgetInstance($widgetInstance);
            $widgetHomeTabConfig->setWidgetOrder(1);
            $widgetHomeTabConfig->setHomeTab($homeTab);
            $widgetHomeTabConfig->setType('admin_user_tracking');
            $widget = $widgetInstance->getWidget();
            $widgetDisplayConfig->setWidgetInstance($widgetInstance);
            $widgetDisplayConfig->setWidth($widget->getDefaultWidth());
            $widgetDisplayConfig->setHeight($widget->getDefaultHeight());

            $this->widgetManager->persistWidgetConfigs(
                $widgetInstance,
                $widgetHomeTabConfig,
                $widgetDisplayConfig
            );

            return new JsonResponse(
                array(
                    'widgetInstanceId' => $widgetInstance->getId(),
                    'widgetHomeTabConfigId' => $widgetHomeTabConfig->getId(),
                    'widgetDisplayConfigId' => $widgetDisplayConfig->getId(),
                    'color' => $widgetDisplayConfig->getColor(),
                    'name' => $widgetInstance->getName(),
                    'configurable' => $widgetInstance->getWidget()->isConfigurable() ? 1 : 0
                ),
                200
            );
        } else {

            return array(
                'homeTab' => $homeTab,
                'instanceForm' => $instanceForm->createView(),
                'displayConfigForm' => $displayConfigForm->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "/administration/widget/diplay/config/{widgetDisplayConfig}/position/row/{row}/column/{column}/update",
     *     name="claro_user_tracking_admin_widget_display_config_position_update",
     *     options = {"expose"=true}
     * )
     * @EXT\Method("POST")
     *
     * Update widget position.
     *
     * @return Response
     */
    public function adminWidgetDisplayConfigPositionUpdateAction(
        WidgetDisplayConfig $widgetDisplayConfig,
        $row,
        $column
    )
    {
        $this->checkAccessForWidgetDisplayConfig($widgetDisplayConfig);
        $widgetDisplayConfig->setRow($row);
        $widgetDisplayConfig->setColumn($column);
        $this->widgetManager->persistWidgetDisplayConfigs(array($widgetDisplayConfig));

        return new Response('success', 204);
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

    private function checkAccessForWidgetDisplayConfig(WidgetDisplayConfig $wdc)
    {
        if (!is_null($wdc->getUser()) || !is_null($wdc->getWorkspace())) {

            throw new AccessDeniedException();
        }
    }
}
