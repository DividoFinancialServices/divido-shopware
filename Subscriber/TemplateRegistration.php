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
use DividoPayment\Components\DividoPayment\DividoPlansService;
use DividoPayment\Components\DividoPayment\DividoHelper;

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
            'Enlight_Controller_Action_PreDispatch_Frontend_Index' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Index' => 'onPostDispatchSecure',
        ];
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        return;
    }

    public function onPostDispatchSecure(\Enlight_Controller_ActionEventArgs $args)
    {
        if ($args->getSubject()->Request()->getActionName() == 'index'){
            $product = $args->getSubject()->View()->sArticle;

            $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
                ->getByPluginName('DividoPayment');

            $min_product_amount = (isset($config['Minimum Amount'])) ? $config['Minimum Amount']*100 : 0;
            $product_price = filter_var($product['price'], FILTER_SANITIZE_NUMBER_INT);
            if($product_price > $min_product_amount){
                $apiKey = $config["Api Key"];
                $key = preg_split("/\./", $apiKey);
                $args->getSubject()->View()->assign('apiKey', $key);
                
                $args->getSubject()->View()->assign('plans', implode(",", $plans_ids));

                if ($config['Widget Suffix']) {
                    $suffix = 'data-divido-suffix="' . strip_tags($config['Widget Suffix']) . '"';
                    $args->getSubject()->View()->assign('suffix', $suffix);
                }

                if ($config['Widget Prefix']) {
                    $prefix = 'data-divido-prefix="' . strip_tags($config['Widget Prefix']) . '"';
                    $args->getSubject()->View()->assign('prefix', $prefix);
                }

                $plans = $product['divido_finance_plans'];
                if(empty($plans)){
                    $plans = DividoPlansService::updatePlans();
                }
                foreach($plans as $plan) $plans_ids[] = $plan->getId();
                $args->getSubject()->View()->assign('plans', implode(",", $plans_ids));

                $args->getSubject()->View()->assign('show_divido', true);
            }else $args->getSubject()->View()->assign('show_divido', false);

            $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
        }
    }
}
