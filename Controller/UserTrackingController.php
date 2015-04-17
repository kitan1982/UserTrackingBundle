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
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Widget\WidgetDisplayConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetHomeTabConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Form\HomeTabConfigType;
use Claroline\CoreBundle\Form\HomeTabType;
use Claroline\CoreBundle\Form\WidgetDisplayConfigType;
use Claroline\CoreBundle\Form\WidgetDisplayType;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\WidgetManager;
use Claroline\UserTrackingBundle\Form\WidgetInstanceType;
use Claroline\UserTrackingBundle\Manager\UserTrackingManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserTrackingController extends Controller
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
     *     "/index/tab/{homeTabId}",
     *     name="claro_user_tracking_index",
     *     defaults={"homeTabId" = -1},
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template()
     */
    public function userTrackingIndexAction($homeTabId = -1)
    {
        $homeTabConfigs = $this->homeTabManager
            ->getHomeTabConfigsByType('user_tracking');
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
            'user_tracking'
        );
        $widgetHomeTabConfigs = is_null($homeTab) ?
            array() :
            $this->homeTabManager->getWidgetHomeTabConfigsByHomeTabAndType(
                $homeTab,
                'user_tracking'
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
     *     "/tab/create/form",
     *     name="claro_user_tracking_tab_create_form",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineUserTrackingBundle:UserTracking:tabCreateModalForm.html.twig")
     */
    public function tabCreateFormAction()
    {
        $form = $this->formFactory->create(new HomeTabType(null, false), new HomeTab());

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/tab/create",
     *     name="claro_user_tracking_tab_create",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineUserTrackingBundle:UserTracking:tabCreateModalForm.html.twig")
     */
    public function tabCreateAction(User $user)
    {
        $homeTab = new HomeTab();
        $form = $this->formFactory->create(new HomeTabType(null, false), $homeTab);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $homeTab->setType('user_tracking');
            $homeTab->setUser($user);
            $this->homeTabManager->insertHomeTab($homeTab);

            $homeTabConfig = new HomeTabConfig();
            $homeTabConfig->setHomeTab($homeTab);
            $homeTabConfig->setType('user_tracking');
            $homeTabConfig->setUser($user);

            $lastOrder = $this->homeTabManager
                ->getOrderOfLastHomeTabByUserAndType($user, 'user_tracking');

            if (is_null($lastOrder['order_max'])) {
                $homeTabConfig->setTabOrder(1);
            } else {
                $homeTabConfig->setTabOrder($lastOrder['order_max'] + 1);
            }
            $this->homeTabManager->persistHomeTabConfigs($homeTab, $homeTabConfig);

            return new JsonResponse($homeTab->getId(), 200);
        } else {

            return array('form' => $form->createView());
        }
    }

    /**
     * @EXT\Route(
     *     "/tab/{homeTab}/edit/form",
     *     name="claro_user_tracking_tab_edit_form",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineUserTrackingBundle:UserTracking:tabEditModalForm.html.twig")
     */
    public function tabEditFormAction(User $user, HomeTab $homeTab)
    {
        $this->checkHomeTab($homeTab, $user);

        $form = $this->formFactory->create(new HomeTabType(null, false), $homeTab);

        return array('homeTab' => $homeTab, 'form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/tab/{homeTab}/edit",
     *     name="claro_user_tracking_tab_edit",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineUserTrackingBundle:UserTracking:tabEditModalForm.html.twig")
     */
    public function tabEditAction(User $user, HomeTab $homeTab)
    {
        $this->checkHomeTab($homeTab, $user);

        $form = $this->formFactory->create(new HomeTabType(null, false), $homeTab);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->homeTabManager->insertHomeTab($homeTab);

            return new JsonResponse(
                array('id' => $homeTab->getId(), 'name' => $homeTab->getName()),
                200
            );
        } else {

            return array('homeTab' => $homeTab, 'form' => $form->createView());
        }
    }

    /**
     * @EXT\Route(
     *     "/tab/{homeTab}/delete",
     *     name="claro_user_tracking_tab_delete",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function tabDeleteAction(User $user, HomeTab $homeTab)
    {
        $this->checkHomeTab($homeTab, $user);
        $this->homeTabManager->deleteHomeTab($homeTab);

        return new Response('success', 204);
    }

    private function checkHomeTab(HomeTab $homeTab, User $user)
    {
        if ($homeTab->getUser() !== $user ||
            !is_null($homeTab->getWorkspace()) ||
            $homeTab->getType() !== 'user_tracking') {

            throw new AccessDeniedException();
        }
    }

    private function checkHomeTabConfig(HomeTabConfig $homeTabConfig, User $user)
    {
        if ($homeTabConfig->getUser() !== $user ||
            !is_null($homeTabConfig->getWorkspace()) ||
            $homeTabConfig->getType() !== 'user_tracking') {

            throw new AccessDeniedException();
        }
    }
}
