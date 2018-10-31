<?php

namespace DividoPayment\Components\DividoPayment;

use DividoPayment\Components\DividoPayment\DividoHelper;

class DividoOrderService
{
    public function retrieveOrderFromDb($id, $connection){
        $get_order_query = $connection->createQueryBuilder();
        $order_sql = "SELECT * FROM `s_orders` WHERE `id`= :id LIMIT 1";
        $order = $get_order_query->fetchAll($order_sql,[':id' => $id]);

        if(isset($order[0])){
            return $order[0];
        }else return false;
    }

    public function saveOrder($order){
        $order->sCreateTemporaryOrder();
        $orderNumber = $order->sSaveOrder();
        if(!$orderNumber){
            DividoHelper::Debug('Could not create order', 'warning');
        }
        return $orderNumber;
    }

    public function findOrders($criteria, $connection){
        $find_order_query = $connection->createQueryBuilder();
        $find_order_query->select('s_order');
        
        foreach($criteria as $key=>$value)
            $find_order_query->where("`{$key}`= :{$key}")->setParameter(":{$key}",$value);
        
        $find_order_query->execute();
        return $find_order_query->fetch_all();
    }

    public function persistOrderAttributes($id, $attributes){
        $attributePersister = Shopware()->Container()->get(
            'shopware_attribute.data_persister'
        );
        
        return 
            $attributePersister->persist(
                $attributes,
                's_order_attributes',
                $id
            );

    }

}