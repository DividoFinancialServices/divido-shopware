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
use DividoPayment\Components\DividoPayment\DividoPaymentService;
use Shopware\Components\CSRFWhitelistAware;

//Include Divido PHP SDK
require_once __DIR__ . '../../../lib/Divido.php';

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
class Shopware_Controllers_Frontend_DividoPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware //
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
     * Allows webhooks to reach server
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
     * Hooks into the pre dispatch method.
     *
     * Look into this for more info
     * https://developers.shopware.com/developers-guide/event-guide/
     *
     * @return void
     */
    public function preDispatch()
    {
        /*
        * @var \Shopware\Components\Plugin $plugin 
        */
        $plugin = $this->get('kernel')->getPlugins()['DividoPayment'];
        $this->get('template')->addTemplateDir(
            $plugin->getPath() . '/Resources/views/'
        );
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->debug('Index view', 'info');
        return $this->redirect(['action' => 'finance', 'forceSecure' => false]);
    }

    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     * See
     * http://developer.divido.com/#resources-credit-request
     *
     * @return void
     */
    public function directAction()
    {
        $this->debug('Direct Action', 'info');

        $service = $this->container->get('divido_payment.divido_payment_service');
        $router = $this->Front()->Router();
        $apiKey = $this->getDividoApiKey();
        $customer = $this->getCustomerDetailsFormatted();
        $basket = $this->getBasket();
        $products = $this->getOrderProducts($basket);
        $details = $this->getOrderDetails($basket);

        if ($_POST['divido_deposit']) {
            $deposit = $this->getDepositAmount(
                $_POST['divido_deposit'],
                $details['amount']
            );
        } else {
            $deposit='';
        }
 
        Divido::setMerchant($apiKey);

        $apiKey = $config['Api Key'];
        $planId= $_POST['divido_plan'];

        $response_url= $router->assemble(
            ['action' => 'webhook', 'forceSecure' => true]
        );
        $checkout_url= $router->assemble(
            ['action' => 'cancel', 'forceSecure' => true]
        );
        $redirect_url = $router->assemble(
            ['action' => 'return', 'forceSecure' => true]
        );
        $token = $service->createPaymentToken(
            $this->getAmount(),
            $billing['customernumber']
        );
        
        $now = time();

        $add_session_query = $this->container->get('dbal_connection')->createQueryBuilder();
        $add_session_query
            ->insert('s_divido_sessions')
            ->values(
                [
                    'orderID'    => '?',
                    'data'       => '?',
                    'ip_address' => '?',
                    'created_on' => '?'
                ]
            )
            ->setParameter(0,null)
            ->setParameter(1,serialize(Shopware()->Session()->sOrderVariables))
            ->setParameter(2,$_SERVER['REMOTE_ADDR'])
            ->setParameter(3,$now);
        $add_session_query->execute();
        
        $get_session_query = $this->container->get('dbal_connection')->createQueryBuilder();
        $get_session_query
            ->select('id','orderID')
            ->from('s_divido_sessions')
            ->orderBy('id','DESC')
            ->setMaxResults(1);
        $divido_session = $get_session_query->execute()->fetch();

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
            'redirect_url' => $redirect_url."?dsid=".$divido_session['id'],
        ];
        
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

            $orderID = $this->getOrderId($response->id);
            $attributePersister = $this->container->get(
                'shopware_attribute.data_persister'
            );

            $attributeData = array(
                'divido_finance_id' => $planId,
                'divido_deposit_value' => $deposit,
            );

            $attributePersister->persist(
                $attributeData,
                's_order_attributes',
                $orderID
            );

            //save depost and finance plan as attribute
        } else {
            if ($response->status === 'error') {
                // Log the error
                $this->forward('cancel');
            }
        }
        
        if($divido_session['orderID'] === null){
            $order_number = $this->getOrderNumber();
            $this->removeDividoSessionsByOrderNumber($order_number);
            $query_builder = $this->container->get('dbal_connection')->createQueryBuilder();
            $query_builder
                ->update('s_divido_sessions')
                ->set('orderID', '?')
                ->where('id','?')
                ->setParameter(0, $order_number)
                ->setParameter(1, $divido_session['id']);
            $query_builder->execute();
        }

        session_write_close();
        //Customer
        //Redirect to returned application or if fail killit
        $this->redirect($response->url);
    }

    /**
     * Finance Action Method
     *
     * Allows user to select finance before redirecting
     *
     * @return void
     */
    public function financeAction()
    {
        $this->debug('Finance view', 'info');
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
        $key = preg_split("/\./", $apiKey);

        $displayFinance = false;

        $basket_plans = [];
        foreach($products as $product){
            if(isset($product['plans'])){
                $product_plans = explode("|",$product['plans']);
                if(empty($basket_plans)){
                    foreach($product_plans as $plan){
                        if(!empty($plan)) $basket_plans[] = $plan;
                    }
                }else{
                    if(!empty($product_plans)){
                        foreach($basket_plans as $k=>$listed){
                            if(!in_array($listed,$product_plans)){
                                unset($basket_plans[$k]);
                            }
                        }
                    }
                }
            }
        }
      
        if ($details['amount'] >= $minCartAmount) {
            $displayFinance = true;
        } else {
            $displayWarning=' Cart does not meet minimum Finance Requirement.';
        }

        if ($customer['address']!=$customer['shippingAddress']) {
            $displayFinance = false;
            $displayWarning
                = $displayWarning . " Shipping and billing address must match.";
        }

        if (empty($apiKey)) {
            $displayFinance = false;
            $displayWarning
                = $displayWarning
                    . " No Api Key Detected. Please contact the merchant.";
        }

        if (!$displayFinance) {
            $displayForm='style="display:none";';
        }

        $this->View()->assign('title', $title);
        $this->View()->assign('apiKey', $key[0]);
        $this->View()->assign('amount', $details['amount']);
        $this->View()->assign('prefix', '');
        $this->View()->assign('suffix', '');
        $this->View()->assign('displayForm', $displayForm);
        $this->View()->assign('displayWarning', $displayWarning);
        $this->View()->assign('basket_plans', implode(",",$basket_plans));
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     *
     * @return void
     */
    public function returnAction()
    {
        //Create order
        
        if(isset($_GET['dsid'])){
            $session_id = filter_var($_GET['dsid'],FILTER_SANITIZE_STRING);
            
            $session_sql = "SELECT `orderID`,`data` FROM `s_divido_sessions` WHERE `id`=? LIMIT 1";
            $session = Shopware()->Db()->fetchRow($session_sql,[$session_id]);
            
            if($session){
                $data = unserialize($session['data']);
                $order_sql = "SELECT * FROM `s_order` WHERE `ordernumber`=? LIMIT 1";
                $order = Shopware()->Db()->fetchRow($order_sql, [$session['orderID']]);
                if($order){
                    $order_cleared = $order['cleared'];
                    switch($order_cleared){
                        case self::PAYMENTSTATUSOPEN:
                            foreach($data as $key=>$value){
                                $this->View()->assign($key,$value);
                            }
                            $addresses['billing'] = $data['sUserData']['billingaddress'];
                            $addresses['shipping'] = $data['sUserData']['shippingaddress'];
                            $addresses['equal'] = 
                                ($data['sUserData']['billingaddress'] == $data['sUserData']['shippingaddress']);
                            $this->View()->assign('sAddresses', $addresses);
                            $this->View()->assign('template', 'frontend/divido_payment/success.tpl');
                            $this->removeDividoSessionById($session_id);
                            break;
                        case self::PAYMENTCANCELLED:
                            //$this->forward('cancel');
                            $this->View()->assign('template', 'frontend/divido_payment/cancel.tpl');
                            $this->removeDividoSessionById($session_id);
                            break;
                        default:
                            $this->View()->assign('template', 'frontend/divido_payment/404.tpl');
                            break;
                    }
                }else{
                    $this->View()->assign('template', 'frontend/divido_payment/404.tpl');
                }
            }else{
                $this->View()->assign('template', 'frontend/divido_payment/404.tpl');
            }
        }
        $this->debug('Return action', 'info');
    }

    /**
     * Cancel action method
     *
     * @return void
     */
    public function cancelAction()
    {
    }

    /**
     * Call back
     *
     * A listener that can receive calls from Divido to update an order in shopware
     * In the shopware documentation this webhook=notify
     *
     * @return void
     */
    public function webhookAction()
    {
        $this->debug('Webhook', 'info');

        /*
         * @var DividoPaymentService $service 
         */
        $service = $this->container->get('divido_payment.divido_payment_service');
        $response = $service->createWebhookResponse($this->Request());
            
        if (!$response->status) {
            $this->debug('No Response Status', 'error');
            echo 'no response';
            die();
        }

        /***
         * HMAC SIGNING
         */
        /*
        //Not working
        if (isset($_SERVER['HTTP_RAW_POST_DATA']) 
            && $_SERVER['HTTP_RAW_POST_DATA']) {
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
         */

        $transactionId=$response->proposal;
        $paymentUniqueId=$response->token;

        $this->debug('Webhook data:'.serialize($response), 'error');
        $this->debug('Webhook TransactionID:'.$transactionId, 'info');
        $this->debug('Webhook Unique Payment ID:'.$paymentUniqueId, 'info');
        $message ='';

        switch ($response->status) {
            case self::STATUS_PROPOSAL:
                $this->debug('Webhook: Proposal', 'info');
                $message ='Proposal Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTSTATUSOPEN
                );
                break;

            case self::STATUS_ACCEPTED:
                $this->debug('Webhook: Accepted', 'info');
                $message ='Accepted Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTSTATUSOPEN
                );
                break;

            case self::STATUS_SIGNED:
                $this->debug('Webhook: Signed', 'info');
                $message ='Signed Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTSTATUSPAID
                );
                break;

            case self::STATUS_DECLINED:
                $this->debug('Webhook: Declined', 'info');
                $message ='Declined Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTREVIEWNEEDED
                );
                break;

            case self::STATUS_CANCELED:
                $this->debug('Webhook: Canceled', 'info');
                $message ='Canceled Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTCANCELLED
                );
                break;

            case self::STATUS_DEPOSIT_PAID:
                $this->debug('Webhook: Deposit Paid', 'info');
                $message ='Deposit Paid Hook Success';
                $this->savePaymentStatus(
                    $transactionId,
                    $paymentUniqueId,
                    self::PAYMENTSTATUSOPEN
                );
                break;

            case self::STATUS_ACTION_LENDER:
                $this->debug('Webhook: Deposit Paid', 'info');
                break;
            
            case self::STATUS_COMPLETED:
                $message ='Completed';
                $this->debug('Webhook: Completed', 'info');
                break;

            case self::STATUS_DEFERRED:
                $message ='Deferred Success';
                $this->debug('Webhook: STATUS_DEFERRED', 'info');
                break;

            case self::STATUS_FULFILLED:
                $message ='STATUS_FULFILLED Success';
                $this->debug('Webhook: STATUS_FULFILLED', 'info');
                break;

            case self::STATUS_REFERRED:
                $message ='Order Referred Success';
                $this->debug('Webhook: Referred', 'info');
                break;

            default:
                $message ='Empty Hook';
                $this->debug('Webhook: Empty webook', 'warning');
                break;
        }

            //update order based on whats sent through
            //use signmature to determin if basket is set
            //create order on signed
            $this->respond(true, $message, false);
            return ;
    }

    /**
     * HELPERS
     */

    /**
     * Debug Helper
     *
     * @param mixed  $msg  A string with the message to debug.
     * @param string $type PHP error level error warning.
     *
     * @return void
     */
    public function debug($msg, $type = false)
    {
        $config = $this->container->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');
        $debug = $config['Debug'];

        if (! $debug) {
            return;
        }

        $this->log($msg, $type);
    }

    /**
     * Log Helper
     *
     * @param mixed  $msg  String to be passed
     * @param string $type Type to be used
     *
     * @return void
     */
    public function log($msg, $type)
    {

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
     *
     * @param string $payload      The payload for the signature
     * @param string $sharedSecret The secret stored on divido portal
     *
     * @return $signature
     */
    public function createSignature($payload, $sharedSecret)
    {
        $signature = base64_encode(
            hash_hmac('sha256', $payload, $sharedSecret, true)
        );
        return $signature;
    }

    /**
     * Create customer details for divido credit request
     *
     * @return Array
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

    /**
     * Helper Function to transform shopware address array to divido format
     *
     * @param array $shopwareAddressArray shopware address
     *
     * @return array
     */
    public function transformShopwareToDividoAddress($shopwareAddressArray)
    {
        $this->debug('Add array:'.serialize($shopwareAddressArray), 'info');

        $addressText = $shopwareAddressArray['buildingNumber'] .' '.
         $shopwareAddressArray['street'] . ' ' .
         $shopwareAddressArray['city'] . ' ' .
         $shopwareAddressArray['zipcode'];
         
        $dividoAddressArray = array();
        $dividoAddressArray['postcode']=$shopwareAddressArray['zipcode'];
        $dividoAddressArray['street']=$shopwareAddressArray['street'];
        $dividoAddressArray['flat']=$shopwareAddressArray['flat'];
        $dividoAddressArray['buildingNumber']
            = $shopwareAddressArray['buildingNumber'];
        $dividoAddressArray['buildingName']=$shopwareAddressArray['buildingName'];
        $dividoAddressArray['town']=$shopwareAddressArray['city'];
        $dividoAddressArray['text']=$addressText;

        return $dividoAddressArray;
    }

    /**
     * Create order detail for divido credit request
     *
     * @param array $shopwareBasketArray The array from shopwares basket
     *
     * @return void
     */
    public function getOrderProducts($shopwareBasketArray)
    {
        $dividoProductsArray = array();
        //Add tax
        $dividoProductsArray['1']['name']='Shipping';
        $dividoProductsArray['1']['quantity']='1';
        $dividoProductsArray['1']['price']=$shopwareBasketArray['sShippingcosts'];
        $i=2;
            
        foreach ($shopwareBasketArray['content'] as $id => $product) {
            $dividoProductsArray[$i]['name']     = $product['articlename'];
            $dividoProductsArray[$i]['quantity'] = $product['quantity'];
            $dividoProductsArray[$i]['price']    = $product['price'];
            if ($product['modus'] == '0') {
                $dividoProductsArray[$i]['plans']
                = $product['additional_details']['attributes']['core']->get('divido_finance_plans');
            }
            $i++;
        }

        return $dividoProductsArray;
    }

    /**
     * Helper function to grab data for credit request
     *
     * @param array $shopwareBasketArray passed in array
     *
     * @return void
     */
    public function getOrderDetails($shopwareBasketArray)
    {
        $formattedArray = array();
        $formattedArray['currency']  = $this->getCurrencyShortName();
        $formattedArray['amount']    = $this->getAmount();
        $formattedArray['reference'] = $this->getOrderNumber();
        return $formattedArray;
    }

    /**
     * Work out the total deposit amount from  the percentage and round it
     *
     * @param float $total   total of the order
     * @param float $deposit deposit amount
     *
     * @return float
     */
    public function getDepositAmount($total, $deposit)
    {
        $depositPercentage = $deposit / 100;
        return round($depositPercentage * $total, 2);
    }

    /**
     * Helper to grab the plugin configuration
     *
     * @return array
     */
    public function getDividoConfig()
    {

        $config = $this->container
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');

        return $config;
    }

    /**
     * Helper to grab api key
     *
     * @return string
     */
    public function getDividoApiKey()
    {
        $config=$this->getDividoConfig();
        return $config['Api Key'];
    }

    /**
     * Helper to grab debug status
     *
     * @return bool
     */
    public function getDividoDebug()
    {
        $config=$this->getDividoConfig();
        return $config['Debug'];
    }

    /**
     * Helper to grab checkout title
     *
     * @return string
     */
    public function getDividoTitle()
    {
        $config=$this->getDividoConfig();
        return $config['Title'];
    }

    /**
     * Helper to grab description
     *
     * @return string
     */
    public function getDividoDescription()
    {
        $config=$this->getDividoConfig();
        return $config['Description'];
    }
    /**
     * Helper to grab shared secret value
     *
     * @return string
     */
    public function getDividoSharedSecret()
    {
        $config=$this->getDividoConfig();
        return $config['Shared Secret'];
    }
    /**
     * Helper to grab cart threshold value
     *
     * @return int
     */
    public function getDividoCartThreshold()
    {
        $config=$this->getDividoConfig();
        return $config['Cart Threshold'];
    }

    /**
     * Respond function
     *
     * @param boolean $ok          Good request
     * @param string  $message     Message recieved
     * @param boolean $bad_request bad request
     *
     * @return void
     */
    public function respond($ok = true, $message = '', $bad_request = false)
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

        $body = json_encode(
            $response,
            JSON_PRETTY_PRINT |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        );

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
     *
     * @return order id (integer)
     */
    protected function getOrderId($transactionId)
    {
        $sql = 'SELECT id FROM s_order WHERE transactionID=?';
        $orderId = Shopware()->Db()->fetchOne($sql, array($transactionId));
        return $orderId;
    }

    /**
     * Removes Divido session based on id.
     *
     * @param integer $sessionId corresponding divido session id
     *
     * @return success (boolean)
     */
    protected function removeDividoSessionById($sessionId){
        $session_delete_sql = "DELETE FROM `s_divido_sessions` WHERE `id`=? LIMIT 1";
        $success = Shopware()->Db()->query($session_delete_sql, [$sessionId]);
        return $success;
    }

    /**
     * Removes Divido sessions based on Order Number.
     *
     * @param integer $orderNumber corresponding order number
     *
     * @return success (boolean)
     */
    protected function removeDividoSessionsByOrderNumber($orderNumber){
        $session_delete_sql = "DELETE FROM `s_divido_sessions` WHERE `orderID`=? LIMIT 1";
        $success = Shopware()->Db()->query($session_delete_sql, [$orderNumber]);
        return $success;
    }
}
