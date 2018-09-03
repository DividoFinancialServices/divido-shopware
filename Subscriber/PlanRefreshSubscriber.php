<?php

namespace DividoPayment\Subscriber;

use Enlight\Event\SubscriberInterface;

class AddPlanButton implements SubscriberInterface
{
    private $pluginDirectory;

    public function __construct($pluginDirectory){
        $this->pluginDirectory = $pluginDirectory;
    }

    public static function getSubscribedEvents(){
       return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onArticlePostDispatch'
        ];
    }

    public function onArticlePostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Article $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/article/detail/window.js');
        }
    }

}

?>