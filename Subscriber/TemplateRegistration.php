<?php

namespace DividoPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use DividoPayment\Components\DividoPayment\DividoPaymentService;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @param $pluginDirectory
     * @param \Enlight_Template_Manager $templateManager
     */
    public function __construct($pluginDirectory, \Enlight_Template_Manager $templateManager)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
               ];
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('DividoPayment');
        $apiKey = $config["Api Key"];
        $key = preg_split("/\./",$apiKey);

        if($config['Small Price Widget']){
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('apiKey', $key['0']);
        }

        if($config['Widget Suffix']){
            $suffix='data-divido-suffix="'.strip_tags($config['Widget Suffix']).'"';
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('suffix', $suffix);
        }

        if($config['Widget Prefix']){
            $prefix='data-divido-prefix="'.strip_tags($config['Widget Prefix']).'"';
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('prefix', $prefix);
        }
        return;
    }


    //remove
    public function getDividoApiKey()
    {
        $service = $this->container->get('divido_payment.divido_payment_service');
        return $service->getConfig();
    }

}