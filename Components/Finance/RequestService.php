<?php

namespace FinancePlugin\Components\Finance;

use Divido\MerchantSDK\Client;
use Divido\MerchantSDK\Environment;
use Divido\MerchantSDK\Models\Application;

class RequestService
{

    public static function setApplicantsFromUser(array $user):array{
        Helper::debug('setting request applicant');

        $billing = $user['billingaddress'];
        $return = array(
            'firstName' => $billing['firstname'],
            'lastName' => $billing['lastname'],
            'email' => $user['additional']['user']['email']
        );
        Helper::debug('CustomerArray:' . serialize($return), 'info');

        return [$return];
    }

    public static function setOrderItemsFromBasket(array $basket){
        $productsArray = array();
        foreach ($basket['content'] as $id => $product) {
            $row = [
                'name' => $product['articlename'],
                'quantity' => intval($product['quantity']),
                'price' => $product['price']*100,
            ];
            if ($product['modus'] == '0') {
                $row['plans'] = 
                    $product['additional_details']['attributes']['core']
                        ->get('finance_plans');
            }
            $productsArray[] = $row;
        }

        return $productsArray;
    }

    public static function makeRequest(\FinancePlugin\Models\Request $request){
        $apiKey = Helper::getApiKey();
        $sdk = new Client(
            $apiKey,
            Environment::SANDBOX
        );

        $application = (new Application())
            ->withCountryId($request->getCountryId())
            ->withCurrencyId($request->getCurrencyId())
            ->withLanguageId($request->getLanguageId())
            ->withFinancePlanId($request->getFinancePlanId())
            ->withApplicants($request->getApplicants())
            ->withOrderItems($request->getOrderItems())
            ->withDepositPercentage($request->getDepositPercentage())
            ->withFinalisationRequired($request->getFinalisationRequired())
            ->withMerchantReference($request->getMerchantReference())
            ->withUrls($request->getUrls());

        $response = $sdk->applications()->createApplication($application);

        $applicationResponseBody = $response->getBody()->getContents();
        
        return json_decode($applicationResponseBody);
    }

}