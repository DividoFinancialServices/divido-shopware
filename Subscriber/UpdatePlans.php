<?php

namespace DividoPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use DividoPayment\Models\Plan;

class UpdatePlans implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var integer
     * Duration (in milliseconds) before next plan request
     */
    const REFRESH_RATE = 7200; 

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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onArticlePostDispatch',
        ];
    }

    public function onArticlePostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Article $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();
        
        if ($request->getActionName() == 'index') {
            $this->set_plans();
        }
        
    }

    private function set_plans(){
        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');
        $apiKey = $config["Api Key"];

        if(!empty($apiKey))
        {
            $now = time();
            $recent_plan = Shopware()->Db()->query('SELECT `id` FROM `s_plans` WHERE `updated_on` > ? LIMIT 1',[$now - self::REFRESH_RATE]);
            if(!$recent_plan){
                require_once($this->pluginDirectory.'/lib/Divido.php');
                \Divido::setMerchant($apiKey);
                $finances_call = \Divido_Finances::all(null, $apiKey);
                if($finances_call->status == 'ok'){
                    foreach($finances_call->finances as $option){
                        $inserts[] = "(?,?,?,?)";
                        $values[] = $option->id;
                        $values[] = $option->text;
                        $values[] = $option->text;
                        $values[] = $now;
                    }
                    if(isset($inserts)){
                        Shopware()->Db()->query("TRUNCATE TABLE `s_plans`");
                        $sql = 'INSERT INTO s_plans (`id`, `name`, `description`, `updated_on`) VALUES'.implode(",",$inserts);
                        Shopware()->Db()->query($sql, $values);
                    }
                }
            }
        }
    }
}