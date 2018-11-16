<?php

namespace FinancePlugin\Models;

use FinancePlugin\Components\Finance\Helper;

class Request
{
    private $countryId = 'GB';
    private $currencyId = 'GBP';
    private $languageId = 'en';
    private $financePlanId = '';
    private $merchantChannelId = '';
    private $applicants = [];
    private $orderItems = [];
    private $depositAmount = '';
    private $depositPercentage = '';
    private $finalisationRequired = false;
    private $merchantReference = '';
    private $urls = [
        "merchant_redirect_url" => "",
        "merchant_checkout_url" => "",
        "merchant_response_url" => ""
    ];
    private $metaData = [];

    public function getCountryId(){
        return $this->countryId;
    }
    public function setCountryId($countryId){
        $this->countryId = $countryId;
    }

    public function getCurrencyId()
    {
        return $this->currencyId;
    }
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $ccurrencyId;
    }

    public function getLanguageId()
    {
        return $this->languageId;
    }
    public function setLanguageId()
    {
        return $this->languageId;
    }

    public function setFinancePlanId($financePlanId)
    {
        $this->financePlanId = $financePlanId;
    }
    public function getFinancePlanId()
    {
        return $this->financePlanId;
    }

    public function setMerchantChannelId($merchantChannelId)
    {
        $this->merchantChannelId = $merchantChannelId;
    }
    public function getMerchantChannelId()
    {
        return $this->merchantChannelId;
    }

    public function setApplicants($applicants)
    {
        $this->applicants = $applicants;
    }
    public function addApplicant($applicant){
        $this->applicants[] = $applicant;
    }
    public function getApplicants()
    {
        return $this->applicants;
    }

    public function setOrderItems($orderItems)
    {
        $this->orderItems = $orderItems;
    }
    public function getOrderItems()
    {
        return $this->orderItems;
    }

    public function setDepositAmount($depositAmount)
    {
        $this->depositAmount = $depositAmount;
    }
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    public function setDepositPercentage($depositPercentage)
    {
        $this->depositPercentage = $depositPercentage;
    }
    public function getDepositPercentage()
    {
        return $this->depositPercentage;
    }
    
    public function setFinalisationRequired($finalisationRequired)
    {
        $this->finalisationRequired = $finalisationRequired;
    }
    public function getFinalisationRequired()
    {
        return $this->finalisationRequired;
    }
    
    public function setMerchantReference($merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }
    public function getMerchantReference()
    {
        return $this->merchantReference;
    }

    public function setUrls($urls)
    {
        $this->urls = $urls;
    }
    public function getUrls()
    {
        return $this->urls;
    }

    public function setMetadata($metaData)
    {
        $this->metaData = $metaData;
    }
    public function getMetaData()
    {
        return $this->metaData;
    }
}