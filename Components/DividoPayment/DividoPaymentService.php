<?php
/**
 * Divido Payment Service
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
 * Divido Payment Service Class
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
class DividoPaymentService
{

    /**
     * Token Checker
     *
     * @param PaymentResponse $response Payment response object
     * @param string          $token    passed token
     *
     * @return bool
     */
    public function isValidToken($amount, $customer_id, $token)
    {
        return password_verify($this->createTokenContent($amount,$customer_id), $token);
    }

    private function createTokenContent($amount,$customerId){
        return implode('|', [$amount, $customerId]);
    }

    /**
     * Token Creator
     *
     * @param float $amount     Amount passed in
     * @param int   $customerId Customer detail
     *
     * @return string
     */
    public function createPaymentToken($amount, $customerId)
    {
        return password_hash($this->createTokenContent($amount,$customerId), PASSWORD_DEFAULT);
    }

    /**
     * Webhook Helper
     *
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return WebhookResponse
     */
    public function createWebhookResponse(
        \Enlight_Controller_Request_Request $request
    ) {

        $data = json_decode($request->getRawBody());

        $dividoResponse = new WebhookResponse();
    
        $dividoResponse->event       = $data->event;
        $dividoResponse->status      = $data->status;
        $dividoResponse->name        = $data->name;
        $dividoResponse->email       = $data->email;
        $dividoResponse->proposal    = $data->proposal;
        $dividoResponse->application = $data->application;
        $dividoResponse->signature   = $data->metadata->signature;
        $dividoResponse->token       = $data->metadata->token;
        $dividoResponse->amount      = $data->metadata->amount;

        return $dividoResponse;
    }

    /**
     * @param $request \Enlight_Controller_Request_Request
     * @return PaymentResponse
     */
    public function createPaymentResponse(
        \Enlight_Controller_Request_Request $request
    ){
        $response = new PaymentResponse();
        $response->sessionId = $request->getParam('dsid', null);
        $response->status = $request->getParam('status', null);
        $response->token = $request->getParam('token', null);

        return $response;
    }
}
