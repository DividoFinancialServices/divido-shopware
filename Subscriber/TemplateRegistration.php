<?php
/**
 * Divido Payment Service
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
namespace DividoPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use DividoPayment\Components\DividoPayment\DividoPaymentService;

/**
 * Divido Payment Service  Class
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
class TemplateRegistration implements SubscriberInterface
{
    /*
     * @var string
     */
    private $pluginDirectory; //

    /*
     * @var \Enlight_Template_Manager
     */
    private $templateManager; //

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
        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');
        $apiKey = $config["Api Key"];
        $key = preg_split("/\./", $apiKey);

        $min_product_amount = (isset($config['Minimum Amount'])) ? $config['Minimum Amount'] : 0;
        $args->getSubject()->View()->assign('min_product_amount', $min_product_amount);

        $plans = $args->getSubject()->View()->sArticle->divido_finance_plans;
        if($plans !== null){
          $plans_string = str_replace("|",",",trim($plans,"|"));
        }
        $args->getSubject()->View()->assign('plans_list', $plans_string);

        if ($config['Small Price Widget']) {
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('apiKey', $key['0']);
        }

        if ($config['Widget Suffix']) {
            $suffix='data-divido-suffix="'.strip_tags($config['Widget Suffix']).'"';
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('suffix', $suffix);
        }

        if ($config['Widget Prefix']) {
            $prefix='data-divido-prefix="'.strip_tags($config['Widget Prefix']).'"';
            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
            $args->getSubject()->View()->assign('prefix', $prefix);
        }
        return;
    }
}
