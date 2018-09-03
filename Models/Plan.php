<?php

namespace DividoPayment\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="s_plans")
 * @ORM\Entity
 */
class Plan
{
    /**
     * @var string $string
     *
     * @ORM\Column(type="string", length=15 nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;


    /**
     * @var string $description
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private $description;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}