<?php

use DividoPayment\Components\DividoPayment\DividoPaymentService;
use Shopware\Components\CSRFWhitelistAware;

//Include Divido PHP SDK
require_once __DIR__ . '../../../lib/Divido.php'; 

class Shopware_Controllers_Frontend_DividoPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    const
        DIVIDO_PLUGIN_VERSION = "0.0.0.1",
        PAYMENTSTATUSPAID = 12,
        PAYMENTSTATUSOPEN = 17,
        PAYMENTREVIEWNEEDED = 21,
        PAYMENTCANCELLED = 35,
        STATUS_PROPOSAL      = 'PROPOSAL',
        STATUS_ACCEPTED      = 'ACCEPTED',
        STATUS_ACTION_LENDER = 'ACTION-LENDER',
        STATUS_CANCELED      = 'CANCELED',
        STATUS_COMPLETED     = 'COMPLETED',
        STATUS_DEFERRED      = 'DEFERRED',
        STATUS_DECLINED      = 'DECLINED',
        STATUS_DEPOSIT_PAID  = 'DEPOSIT-PAID',
        STATUS_FULFILLED     = 'FULFILLED',
        STATUS_REFERRED      = 'REFERRED',
        STATUS_SIGNED        = 'SIGNED',
        STATUS_READY         = 'READY';


     /**
      * Order History Mesaages 
      *
      * @var array
      */
     public $historyMessages = array(
        self::STATUS_ACCEPTED      => 'Credit request accepted',
        self::STATUS_ACTION_LENDER => 'Lender notified',
        self::STATUS_CANCELED      => 'Application canceled',
        self::STATUS_COMPLETED     => 'Application completed',
        self::STATUS_DEFERRED      => 'Application deferred by Underwriter, waiting for new status',
        self::STATUS_DECLINED      => 'Applicaiton declined by Underwriter',
        self::STATUS_DEPOSIT_PAID  => 'Deposit paid by customer',
        self::STATUS_FULFILLED     => 'Credit request fulfilled',
        self::STATUS_REFERRED      => 'Credit request referred by Underwriter, waiting for new status',
        self::STATUS_SIGNED        => 'Customer have signed all contracts',
        self::STATUS_READY         => 'Order ready to Ship',

    );

    /**
     * getWhitelistedCSRFActions allows webhooks to reach server
     *
     * @return void
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'return',
            'webhook'
        ];
    }

    /**
     * preDispatch  method.
     *
     * https://developers.shopware.com/developers-guide/event-guide/
     */
    public function preDispatch()
    {
       /**
        * @var \Shopware\Components\Plugin $plugin 
        */
        $plugin = $this->get('kernel')->getPlugins()['DividoPayment'];
        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
        $this->debug('Index view','info');
        return $this->redirect(['action' => 'finance', 'forceSecure' => false]);
    }

    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     * See
     * http://developer.divido.com/#resources-credit-request
     */
    public function directAction()
    {
        $this->debug('Direct Action','info');

        $service = $this->container->get('divido_payment.divido_payment_service');
        $router = $this->Front()->Router();
        $apiKey = $this->getDividoApiKey();
        $customer = $this->getCustomerDetailsFormatted();
        $basket = $this->getBasket();
        $products = $this->getOrderProducts($basket);
        $details = $this->getOrderDetails($basket);
        $basketSignature = $this->persistBasket();

        if($_POST['divido_deposit']) {
            $deposit = $this->getDepositAmount($_POST['divido_deposit'], $details['amount']);
        }else{
            $deposit='';
        }
 
        Divido::setMerchant($apiKey);

        $apiKey = $config['Api Key'];
        $planId= $_POST['divido_plan'];

        $response_url= $router->assemble(['action' => 'webhook', 'forceSecure' => true]);
        $checkout_url= $router->assemble(['action' => 'cancel', 'forceSecure' => true]);
        $redirect_url = $router->assemble(['action' => 'return', 'forceSecure' => true]);
        $token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);

        $requestData = [
            'merchant' => $apiKey,
            'deposit'  => $deposit,
            'finance'  => $planId,
            'language' => $language,
            'metadata' => [
                'token'   => $token,
                'signature' =>  $basketSignature,
                'amount' => $details['amount'],
            ],
            'products'     => $products,
            'response_url' => $response_url,
            'redirect_url' => $redirect_url,
        ];

        //save order and get unique identifire
       /*
        if (! empty($sharedSecret)) {
            Divido::setSharedSecret($sharedSecret);
        }
        */
        $requestData = array_merge($customer, $details, $requestData);
        $response = \Divido_CreditRequest::create($requestData);

        //persist basket if good create order 
        //return with basket if fails
        if ($response->status == 'ok') {

            //save order as processing
             $this->saveOrder(
                $response->id,
                $token,
                self::PAYMENTSTATUSOPEN
            );

            $orderID = $this->_getOrderId($response->id);
            $attributePersister = $this->container->get('shopware_attribute.data_persister');

            $attributeData = array(
                'divido_finance_id' => $planId,
                'divido_deposit_value' => $deposit,
            );

            $attributePersister->persist($attributeData, 's_order_attributes', $orderID);


            //save depost and finance plan as attribute
            
        } else {
            if ($response->status === 'error') {    
                // Log the error
                $this->forward('cancel');
            }
        }


        //Customer
        //Redirect to returned application or if fail killit 
        $this->redirect($response->url);
    }

    /**
     * Finance Action Method
     *
     * Allows user to select finance before redirecting
     */
    public function financeAction()
    {
        $this->debug('Finance view','info');
        header('Access-Control-Allow-Origin: *');
        $basket = $this->getBasket();
        $products = $this->getOrderProducts($basket);
        $details = $this->getOrderDetails($basket);
        $title = $this->getDividoTitle();   
        $description = $this->getDividoDescription();
        $minCartAmount =$this->getDividoCartThreshold();
        $customer = $this->getCustomerDetailsFormatted();

        $displayForm='';
        $displayWarning='';

        $apiKey = $this->getDividoApiKey();
        $key = preg_split("/\./",$apiKey);

        $displayFinance = false;
      
        if($details['amount'] >= $minCartAmount){
            $displayFinance = true;
        }else{
            $displayWarning=' Cart does not meet minimum Finance Requirement.';
        }

        if($customer['address']!=$customer['shippingAddress']){
            $displayFinance = false;
            $displayWarning =$displayWarning." Shipping and billing address must match.";
        }

        if($apiKey === ''){
            $displayFinance = false;
            $displayWarning =$displayWarning." No Api Key Detected. Please contact the merchant.";
        }

        if(!$displayFinance){
            $displayForm='style="display:none";';
        }

        $this->View()->assign('title', $title);
        $this->View()->assign('apiKey', $key[0]);
        $this->View()->assign('amount', $details['amount']);
        $this->View()->assign('prefix', '');
        $this->View()->assign('suffix', '');
        $this->View()->assign('displayForm', $displayForm);
        $this->View()->assign('displayWarning', $displayWarning);

    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        $this->debug('Return action','info');
        //TODO if declined return to basket
        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);

    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {
    }

    /**
     * webhookAction
     * 
     * A listener that can receive calls from Divido to update an order in shopware
     * In the shopware documentation this webhook=notify 
     *
     * @return void
     */
    public function webhookAction()
    {
        $this->debug('Webhook','info');

        /**
         * @var DividoPaymentService $service 
         */
        $service = $this->container->get('divido_payment.divido_payment_service');
        //get webhook
        $response = $service->createWebhookResponse($this->Request());      
            
        if (!$response->status) {
            //Logging
            $this->debug('No Response Status','error');
            echo 'no response';
            die();
        }

        /***
         * HMAC SIGNING
         * 
         */
        /*
        //Not working
        if (isset($_SERVER['HTTP_RAW_POST_DATA']) && $_SERVER['HTTP_RAW_POST_DATA']) {
            $this->debug('Raw Data :','info');

            $data = file_get_contents($_SERVER['HTTP_RAW_POST_DATA']);
        } else {
            $this->debug('PHP input:','info');
            $data = file_get_contents('php://input');
        }

        $this->debug('Shared Secret:'.$this->getDividoSharedSecret(),'info');

        if($this->getDividoSharedSecret() != "")
        {
            $callback_sign = $_SERVER['HTTP_X_DIVIDO_HMAC_SHA256'];

            $this->debug('Callback Sign: '.$callback_sign , 'info');
            $this->debug('Callback DATA: '.$data,'info');

            $sign = $this->createSignature( $data, $this->getDividoSharedSecret() );

            $this->debug('Created Signature: '.$sign,'info');

            if ( $callback_sign !== $sign ) {
                $this->debug('ERROR: Hash error','error');
                //$this->send_json( 'error', 'Hash error.' );
                return;
        
            }

        }
        */
        /* from other plugins
        if ( $this->secret != "" ) {
            $callback_sign = $_SERVER['HTTP_X_DIVIDO_HMAC_SHA256'];
            $sign = $this->createSignature( $data, $this->secret );

            if ( $callback_sign !== $sign ) {
                $this->logger->add('Divido', 'ERROR: Hash error');
                $this->send_json( 'error', 'Hash error.' );
                return;

            }
        }
        */
        /***
         * HMAC SIGNING
         * 
         */


        $transactionId=$response->proposal;
        $paymentUniqueId=$response->token;

        $this->debug('Webhook data:'.serialize($response),'error');
        $this->debug('Webhook TransactionID:'.$transactionId,'info');
        $this->debug('Webhook Unique Payment ID:'.$paymentUniqueId,'info');
        $message ='';

        switch ($response->status) 
        {
            case self::STATUS_PROPOSAL:
                $this->debug('Webhook: Proposal','info');
                $message ='Proposal Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTSTATUSOPEN);
                break;

            case self::STATUS_ACCEPTED:
                $this->debug('Webhook: Accepted','info');
                $message ='Accepted Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTSTATUSOPEN);
                break;

            case  self::STATUS_SIGNED:
                $this->debug('Webhook: Signed','info');
                $message ='Signed Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTSTATUSPAID);    
                break;

            case  self::STATUS_DECLINED:
                $this->debug('Webhook: Declined','info');
                $message ='Declined Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTREVIEWNEEDED);
                break;

            case  self::STATUS_CANCELED:
                $this->debug('Webhook: Canceled','info');
                $message ='Canceled Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTCANCELLED);
                break;

            case  self::STATUS_DEPOSIT_PAID:
                $this->debug('Webhook: Deposit Paid','info');
                $message ='Deposit Paid Hook Success';
                $this->savePaymentStatus($transactionId, $paymentUniqueId, self::PAYMENTSTATUSOPEN);
                break;

            case self::STATUS_ACTION_LENDER:
                $this->debug('Webhook: Deposit Paid','info');
                break;
            
            case self::STATUS_COMPLETED:
                $message ='Completed';
                $this->debug('Webhook: Completed','info');
                break;

            case self::STATUS_DEFERRED:
                $message ='Deferred Success';
                $this->debug('Webhook: STATUS_DEFERRED','info');
                break;

            case self::STATUS_FULFILLED:
                $message ='STATUS_FULFILLED Success';
                $this->debug('Webhook: STATUS_FULFILLED','info');
                break;

            case self::STATUS_REFERRED:
                $message ='Order Referred Success';
                $this->debug('Webhook: Referred','info');
                break;

            default:
                $message ='Empty Hook';
                $this->debug('Webhook: Empty webook','warning');
                break;
        }

            //update order based on whats sent through
            //use signmature to determin if basket is set 
            //create order on signed
            $this->respond(true, $message , false);
            return ;

    }

    /**
     * HELPERS
     */

    /**
     * Debug Helper
     *
     * @param mixed $msg
     * @param string $level
     * @return void
     */
    public function debug($msg, $type = false)
    {
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('DividoPayment');
        $debug = $config['Debug'];

        if (! $debug ) {
            return;
        }

        $this->log($msg, $type);
    }  

    /**
     * log Helper
     *
     * @param mixed $msg
     * @param string $level
     * @return void
     */
    public function log($msg, $type) {

        switch ($type) {
            case 'warning':
                Shopware()->PluginLogger()->warning("Warning: ". $msg);
                break;
            
            case 'info':
                Shopware()->PluginLogger()->info("Info: " . $msg);
                break;

            case 'error':
                Shopware()->PluginLogger()->error("Error: " . $msg);
                break;
            
            default:
                Shopware()->PluginLogger()->info("Default info: " . $msg);
                break;
        }
        return;
    }


    /**
	 * Create HMAC SIGNATURE
	 */
	public function createSignature ($payload, $sharedSecret) {
        $signature = base64_encode(hash_hmac('sha256', $payload, $sharedSecret, true));
	    return $signature;
	}

    /**
     * Create customer details for divido credit request
     */

    public function getCustomerDetailsFormatted()
    {

        $this->debug('Formatting Customer Details');

        $user = $this->getUser();

        $billing = $user['billingaddress'];
        $shipping = $user['shippingaddress'];

        $billingAddress=$this->transformShopwareToDividoAddress($billing);
        $shippingAddress=$this->transformShopwareToDividoAddress($shipping);
        $country = $user['additional']['country']['countryiso'];
 
        $customerArray=array();
        $customerArray['country']=$country;
        $customerArray['customer'] = array(
           'firstName' => $billing['firstname'],
           'lastName' => $billing['lastname'],
           'email' => $user['additional']['user']['email'],
           'address'=> $billingAddress,
           'shippingAddress'=> $shippingAddress,
        );
        $this->debug('CustomerArray:'.serialize($customerArray), 'info');

        return $customerArray;
    }

    public function transformShopwareToDividoAddress($shopwareAddressArray)
    {
        $this->debug('Add array:'.serialize($shopwareAddressArray), 'info');

        $addressText = $shopwareAddressArray['buildingNumber'] .' '. $shopwareAddressArray['street'] . ' ' . $shopwareAddressArray['city'] . ' ' . $shopwareAddressArray['zipcode']; 
        $dividoAddressArray = array();
        $dividoAddressArray['postcode']=$shopwareAddressArray['zipcode'];
        $dividoAddressArray['street']=$shopwareAddressArray['street'];
        $dividoAddressArray['flat']=$shopwareAddressArray['flat'];
        $dividoAddressArray['buildingNumber']=$shopwareAddressArray['buildingNumber'];
        $dividoAddressArray['buildingName']=$shopwareAddressArray['buildingName'];
        $dividoAddressArray['town']=$shopwareAddressArray['city'];
        $dividoAddressArray['text']=$addressText;

        return $dividoAddressArray;

    }

     /**
      * Create Order detail for divido credit request
      */
    public function getOrderProducts($shopwareBasketArray)
    {
        $dividoProductsArray = array();

        //echo "<pre>";
        //var_dump($shopwareBasketArray);
        //echo "</pre>";

            //Add tax
            $dividoProductsArray['1']['name']='Shipping';
            $dividoProductsArray['1']['quantity']='1';
            $dividoProductsArray['1']['price']=$shopwareBasketArray['sShippingcosts'];
            $i=2;
            
        foreach($shopwareBasketArray['content'] as $id => $product){
            $dividoProductsArray[$i]['name']     = $product['articlename'];
            $dividoProductsArray[$i]['quantity'] = $product['quantity'];
            $dividoProductsArray[$i]['price']    = $product['price'];
            $i++;
        }

        return $dividoProductsArray;
    }

    public function getOrderDetails($shopwareBasketArray)
    {
        $formattedArray = array();
        $formattedArray['currency']  = $this->getCurrencyShortName();
        $formattedArray['amount']    = $this->getAmount();
        $formattedArray['reference'] = $this->getOrderNumber();  
        return $formattedArray;
    }

    public function getDepositAmount($total, $deposit)
    {
        $depositPercentage = $deposit / 100;
        return round($depositPercentage * $total, 2);
    }

    public function getDividoConfig(){

        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('DividoPayment');

        return $config;
    }

    public function getDividoApiKey()
    {
        $config=$this->getDividoConfig();
        return $config['Api Key'];
    }

    public function getDividoDebug()
    {
        $config=$this->getDividoConfig();
        return $config['Debug'];
    }

    public function getDividoTitle()
    {
        $config=$this->getDividoConfig();
        return $config['Title'];
    }

    public function getDividoDescription()
    {
        $config=$this->getDividoConfig();
        return $config['Description'];
    }

    public function getDividoSharedSecret()
    {
        $config=$this->getDividoConfig();
        return $config['Shared Secret'];
    }

    public function getDividoCartThreshold()
    {
        $config=$this->getDividoConfig();
        return $config['Cart Threshold'];
    }

    /**
     * Respond function
     *
     * @param boolean $ok
     * @param string $message
     * @param boolean $bad_request
     * @return void
     */
    public function respond ($ok = true, $message = '', $bad_request = false)
    {
        
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        $this->debug('RESPOND ATTEMPT');
        if ($ok) {
            $code = 200;
        } elseif ($bad_request) {
            $code = 400;
        } else {
            $code = 500;
        }

        $status = $ok ? 'ok' : 'error';

        $response = array(
            'status'           => $status,
            'message'          => $message,
            'platform'         => 'Shopware',
            'plugin_version'   => self::DIVIDO_PLUGIN_VERSION,
        );

        $body = json_encode($response, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $this->Response()->clearHeaders()
        ->clearRawHeaders()
        ->clearBody();

        $this->Response()->setBody($body);
        $this->Response()->setHeader('Content-type', 'application/json', true);
        $this->Response()->setHttpResponseCode($code);
        
        return $this->Response();

    }

    /**
     * Selects order id by transaction id.
     *
     * @param integer $transactionId corresponding transaction id
     * @return order id (integer)
     */
    protected function _getOrderId($transactionId)
    {
        $sql = 'SELECT id FROM s_order WHERE transactionID=?';
        $orderId = Shopware()->Db()->fetchOne($sql, array($transactionId));
        return $orderId;
    }
}
