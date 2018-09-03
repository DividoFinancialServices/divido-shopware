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

/**
 * Divido Payment Service  Class
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   DividoPayment
 * @author    Original Author <andrew.smith@divido.com>
 * @author    Another Author <jonthan.carter@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 0.2.0
 */
class CronSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Cronjob_UpdatePlans' => 'onPlanUpdateCronjob'
        ];
    }

    public function onPlanUpdateCronjob(\Shopware_Components_Cron_CronJob $job)
    {
       $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');
        $apiKey = $config["Api Key"];
        
        $inserts = ["(?,?,?)"];
        $values = ['1','All Plans', 'All Plans'];
        /*
        if(!empty($apiKey))
        {
            require_once('../lib/Divido.php');
            Divido::setMerchant($apiKey);
            $plans = \Divido_Finances::all(null,$apiKey);
            if ($plans->status == 'ok') {
                foreach($plans as $plan)
                {
                    $inserts[] = "(?,?,?)";
                    $values[] = $plan->id;
                    $values[] = $plan->name;
                    $values[] = $plan->name;
                }
            }
        }
        */
        Shopware()->Db()->query("TRUNCATE TABLE `s_plans`");
        $sql = 'INSERT INTO s_plans (`id`,`name`,`description`) VALUES'.implode(",",$inserts);
        $insert = Shopware()->Db()->query($sql, $values);
    }
}