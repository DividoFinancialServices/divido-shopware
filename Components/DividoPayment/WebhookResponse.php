<?php

namespace DividoPayment\Components\DividoPayment;

/*
{
    "event": "application-status-update",
    "status": "READY",
    "name": "Test Test2",
    "firstName": "Test",
    "lastName": "Test2",
    "email": "test@divido.com",
    "phoneNumber": "7777777777",
    "proposal": "6118d492-7db3-47cb-8640-d84c1220ba0e",
    "application": "6118d492-7db3-47cb-8640-d84c1220ba0e",
    "reference": "",
    "metadata": {
        "quote_id": "698",
        "quote_hash": "4a13fb774bd58b30fa5539a04441750fd9c7d8824324c44cccdb7187ff403e47"
    }
}
*/
class WebhookResponse
{
    /**
     * @var string
     */
    public $event;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $proposal;

    /**
     * @var string
     */
    public $application;

    /**
     * @var string
     */
    public $signature;
    /**
     * @var string
     */
    public $bookingId;


}
