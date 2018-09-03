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
        
        $inserts = ["(?,?,?)"];
        $values = ['1','All Plans', 'All Plans'];
            
        if(!empty($apiKey))
        {
            require_once(__DIR__.'../../lib/Divido.php');
            Divido::setMerchant($apiKey);
            $plans = \Divido_Finances::all(null,$apiKey);
            if ($plans->status == 'ok') {
                /*
                foreach($plans as $plan)
                {
                    $inserts[] = "(?,?,?)";
                    $values[] = $plan->id;
                    $values[] = $plan->name;
                    $values[] = $plan->name;
                }
                */
            }
        }

        Shopware()->Db()->query("TRUNCATE TABLE `s_plans`");
        $sql = 'INSERT INTO s_plans (`id`,`name`,`description`) VALUES'.implode(",",$inserts);
        $insert = Shopware()->Db()->query($sql, $values);
    }


}

?>