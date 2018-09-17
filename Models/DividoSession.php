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
     * @var integer $orderID
     *
     * @ORM\Column(type="integer", length=10, nullable=true)
     */
    private $orderID;

    /**
     * @var string $data
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $data;

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
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderID;
    }

    /**
     * @param string $orderID
     */
    public function setOrderId($orderID)
    {
        $this->orderID = $orderID;
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
}