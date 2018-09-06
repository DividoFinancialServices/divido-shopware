<?php

namespace DividoPayment\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;

class ModelSubscriber implements EventSubscriber
{
    /*
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
        
        Shopware()->Db()->query("TRUNCATE TABLE `s_plans`");
        if(!empty($apiKey))
        {
            require_once($this->pluginDirectory.'/lib/Divido.php');
            \Divido::setMerchant($apiKey);
            $finances_call = \Divido_Finances::all(null, $apiKey);
            
            if($finances_call->status == 'ok'){
                foreach($finances_call->finances as $option){
                    $inserts[] = "(?,?,?)";
                    $values[] = $option->id;
                    $values[] = $option->title;
                    $values[] = $option->title;
                }
                if(isset($inserts)){
                    $sql = 'INSERT INTO s_plans (`id`, `name`, `description`) VALUES'.implode(",",$inserts);
                    Shopware()->Db()->query($sql, $values);
                }
            }
        }

        
    }


}

?>