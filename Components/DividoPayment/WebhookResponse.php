<?php
/**
 * Divido Payment Service - Webhook Response
 *
 * PHP version 5.5
 *
 * @category  CategoryName
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
namespace DividoPayment\Components\DividoPayment;

/**
 * Divido Payment Service Webhook Response
 *
 * PHP version 5.5
 *
 * @category  CategoryName
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
class WebhookResponse
{
    /**
     * Description of the event - application-status-update
     *
     * @var string
     */
    public $event;

    /**
     * Status Code returned - READY
     *
     * @var string
     */
    public $status;

    /**
     * Customers Name - Toby
     *
     * @var string
     */
    public $name;

    /**
     * Customers last name - SMITH
     *
     * @var string
     */
    public $lastname;

    /**
     * Customer email address - email@divido.com
     *
     * @var string Customer email address - email@divido.com
     */
    public $email;

    /**
     * Unique Identifier - 6118d492-7db3-47cb-8640-d84c1220ba0e
     *
     * @var string
     */
    public $proposal;

    /**
     * Unique Identifiers -  6118d492-7db3-47cb-8640-d84c1220ba0e
     *
     * @var string
     */
    public $application;

    /**
     *  Unique Basket Signatute
     *
     * @var string
     */
    public $signature;

    /**
     * The booking Id
     *
     * @var string
     */
    public $bookingId;
}
