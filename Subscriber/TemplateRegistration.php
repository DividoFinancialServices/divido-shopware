<?php
/**
 * Finance Plugin Service
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   FinancePlugin
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
namespace FinancePlugin\Subscriber;

use Enlight\Event\SubscriberInterface;
use FinancePlugin\Components\Finance\PaymentService;
use FinancePlugin\Components\Finance\PlansService;
use FinancePlugin\Components\Finance\Helper;

/**
 * Payment Service  Class
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   FinancePlugin
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
            'Enlight_Controller_Action_PreDispatch_Frontend' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatchSecure',
        ];
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $args->get('subject')->View()->addTemplateDir($this->pluginDirectory . '/Resources/views');
        return;
    }

    public function onPostDispatchSecure(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->get('subject');
        $view = $controller->View();
        
        if ($controller->Request()->getActionName() == 'index'){
            $product = $view->sArticle;

            $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
                ->getByPluginName('FinancePlugin');

            $min_product_amount = (isset($config['Minimum Amount'])) ? $config['Minimum Amount']*100 : 0;
            $product_price = filter_var($product['price'], FILTER_SANITIZE_NUMBER_INT);

            if($product_price > $min_product_amount){
                $apiKey = $config["Api Key"];
                $key = preg_split("/\./", $apiKey);
                $view->assign('apiKey', $key[0]);
                
                $view->assign('plans', implode(",", $plans_ids));

                $suffix = ($config['Widget Suffix']) ? strip_tags($config['Widget Suffix']) : "";
                $view->assign('suffix', $suffix);

                $prefix = ($config['Widget Prefix']) ? strip_tags($config['Widget Prefix']) : "";
                $view->assign('prefix', $prefix);

                $plans = str_replace("|",",",$product['finance_plans']);
                if(empty($plans)){
                    $plans = PlansService::updatePlans();
                    foreach ($plans as $plan) $plans_ids[] = $plan->getId();
                    $view->assign('plans', implode(",", $plans_ids));
                }else $view->assign('plans',$plans);

                $view->assign('show_widget', true);
            }else $view->assign('show_widget', false);
        }
    }
}
