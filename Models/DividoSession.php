<?php

namespace DividoPayment\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_divido_sessions")
 * @ORM\Entity
 */
class DividoSession extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer", length=8, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $orderNumber
     *
     * @ORM\Column(type="integer", length=10, nullable=true)
     */
    private $orderNumber;
    
    /**
     * @var integer $status
     *
     * @ORM\Column(type="integer", length=2, nullable=false)
     */
    private $status;

    /**
     * @var string $transactionID
     *
     * @ORM\Column(type="string", length=40, nullable=false)
     */
    private $transactionID;

    /**
     * @var string $key
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $key;

    /**
     * @var string $data
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $data;

    /**
     * @var string $plan
     *
     * @ORM\Column(type="string", length=25, nullable=false)
     */
    private $plan;

    /**
     * @var string $deposit
     *
     * @ORM\Column(type="decimal", length=10, nullable=true)
     */
    private $deposit;

    /**
     * @var string $ip_address
     *
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private $ip_address;

    /**
     * @var integer $created_on
     *
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $created_on;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param int $orderNumber
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string
     */
    public function getTransactionID()
    {
        return $this->transactionID;
    }

    /**
     * @param string $transactionID
     */
    public function setTransactionID($transactionID)
    {
        $this->transactionID = $transactionID;
    }


    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param string $ip_address
     */
    public function setIpAddress($ip_address)
    {
        $this->ip_address = $ip_address;
    }

    /**
     * @return int
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }

    /**
     * @param int $created_on
     */
    public function setCreatedOn($created_on)
    {
        $this->created_on = $created_on;
    }
    
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * @param decimal $deposit
     */
    public function setDeposit($deposit)
    {
        $this->deposit = $deposit;
    }

    /**
     * @return int
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @param int $plan
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
    }
}