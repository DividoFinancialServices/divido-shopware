<?php

namespace DividoPayment\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;

class ModelSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(){
        return [
            Events::postPersist
        ];
    }

    public function postPersist(LifecycleEventArgs $arguments){
        $modelManager = $arguments->getEntityManager();

        $model = $arguments->getEntity();

        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');
        $apiKey = $config["Api Key"];
        
        $inserts = ["(?,?)"];
        $values = ['All Plans', 'All Plans'];
        
        if(!empty($apiKey))
        {
            require_once(__DIR__.'../../lib/Divido.php');
            \Divido::setMerchant($apiKey);
            $finances_call = \Divido_Finances::all(null, $apiKey);
            
            if($finances_call->status == 'ok'){
                foreach($finances_call->finances as $option){
                    if(in_array($option->id, $finance_list)){
                        $inserts = ["(?,?)"];
                        $values[] = $option->title;
                        $values[] = $option->title;
                    }
                }
            }
        }

        Shopware()->Db()->query("TRUNCATE TABLE `s_plans`");
        $sql = 'INSERT INTO s_plans (`name`,`description`) VALUES'.implode(",",$inserts);
        $insert = Shopware()->Db()->query($sql, $values);
    }


}

?>