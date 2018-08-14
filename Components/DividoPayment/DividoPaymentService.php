<?php

namespace DividoPayment\Components\DividoPayment;

class DividoPaymentService
{


    /**
     * @param PaymentResponse $response
     * @param string $token
     * @return bool
     */
    public function isValidToken(PaymentResponse $response, $token)
    {
        return hash_equals($token, $response->token);
    }

    /**
     * @param float $amount
     * @param int $customerId
     * @return string
     */
    public function createPaymentToken($amount, $customerId)
    {
        return md5(implode('|', [$amount, $customerId]));
    }

    //isValidSignature

        /**
     * @param $request \Enlight_Controller_Request_Request
     * @return WebhookResponse
     */
    public function createWebhookResponse(\Enlight_Controller_Request_Request $request){

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
        $dividoResponse->bookingId   = $data->metadata->bookingId;

        return $dividoResponse;

    }

}
