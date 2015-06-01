<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Listener;

use Claroline\CoreBundle\Event\DisplayToolEvent;
use Claroline\CoreBundle\Event\PluginOptionsEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @DI\Service
 */
class UserTrackingListener
{
    private $httpKernel;
    private $request;

    /**
     * @DI\InjectParams({
     *     "httpKernel"         = @DI\Inject("http_kernel"),
     *     "requestStack"       = @DI\Inject("request_stack")
     * })
     */
    public function __construct(
        HttpKernelInterface $httpKernel,
        RequestStack $requestStack
    )
    {
        $this->httpKernel = $httpKernel;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @DI\Observe("plugin_options_usertrackingbundle")
     *
     * @param DisplayToolEvent $event
     */
    public function onPluginOptionsOpen(PluginOptionsEvent $event)
    {
        $params = array();
        $params['_controller'] = 'ClarolineUserTrackingBundle:UserTracking:pluginConfigureForm';
        $subRequest = $this->request->duplicate(array(), null, $params);
        $response = $this->httpKernel
            ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("open_tool_desktop_claroline_user_tracking_tool")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayDesktopUserTrackingTool(DisplayToolEvent $event)
    {
        $params = array();
        $params['_controller'] = 'ClarolineUserTrackingBundle:UserTracking:userTrackingIndex';
        $subRequest = $this->request->duplicate(array(), null, $params);
        $response = $this->httpKernel
            ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setContent($response->getContent());
        $event->stopPropagation();
    }
}
