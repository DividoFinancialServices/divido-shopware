<?php

namespace DividoPayment\Subscriber;

use Enlight\Event\SubscriberInterface;

class ExtendCustomer implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatch'
        ];
    }

    public function onCustomerPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Customer $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();
        
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');
        
        if ($request->getActionName() == 'index') {
            $view->extendsTemplate('backend/divido_payment/app.js');
        }
        
        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/divido_payment/view/detail/window.js');
        }
        
    }
}