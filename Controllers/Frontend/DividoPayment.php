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
use DividoPayment\Components\DividoPayment\DividoRequestService;
use DividoPayment\Components\DividoPayment\DividoOrderService;
use DividoPayment\Components\DividoPayment\DividoHelper;
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
        $apiKey = DividoHelper::getApiKey();

        $user = $this->getUser();
        $customer = DividoHelper::getCustomerDetailsFormatted($user);
        
        $basket = $this->getBasket();
        $amount = $this->getAmount();

        $deposit = (isset($_POST['divido_deposit'])) 
            ? DividoHelper::getDepositAmount(
                $_POST['divido_deposit'],
                $amount
            )
            : '';
        
        $token = $service->createPaymentToken(
            $amount,
            $user['additional']['user']['customernumber']
        );

        \Divido::setMerchant($apiKey);
        
        $planId = filter_var($_POST['divido_plan'], FILTER_SANITIZE_EMAIL);
        /* Needed?
        $checkout_url= $router->assemble(
            ['action' => 'cancel', 'forceSecure' => true]
        );
        */
        $session = new \DividoPayment\Models\DividoSession;
        $session->setKey($token);
        $session->setStatus(self::PAYMENTSTATUSOPEN);
        $session->setDataFromShopwareSession();
        $session->setPlan($planId);
        $session->setDeposit($deposit);

        $connection = $this->container->get('dbal_connection');
        $sessionId = $session->store($connection);
        
        $metadata = [
            'token' => $service->createPaymentToken(
                $amount, 
                $user['additional']['user']['customernumber']
            ),
            'amount' => $amount
        ];
        
        $dividoRequest = new DividoRequestService;
        $dividoRequest->setRequestField('merchant', $apiKey);
        $dividoRequest->setRequestField('deposit', $deposit);
        $dividoRequest->setRequestField('finance', $planId);
        //$dividoRequest->setRequestField('language', $language);
        $dividoRequest->setRequestField('metadata', $metadata);
        $dividoRequest->setRequestField('products', DividoHelper::getOrderProducts($basket));
        $dividoRequest->setRequestField(
            'response_url', 
            $router->assemble(['action' => 'webhook', 'forceSecure' => true])
        );
        $redirect_url = $router->assemble(['action' => 'return', 'forceSecure' => true]);
        $dividoRequest->setRequestField(
            'redirect_url', 
            $redirect_url."?sid={$sessionId}&token={$token}"
        );
        $dividoRequest->setRequestField('currency', $this->getCurrencyShortName());
        $dividoRequest->setRequestField('amount', $amount);
        $dividoRequest->setRequestField('reference', $this->getOrderNumber());

        $dividoRequest->setRequestFieldsByArray($customer);

        $dividoRequest->dumpRequest();

        $response = $dividoRequest->makeRequest();

        // Create divido session if request is okay and forward to the divido payment platform
        if ($response->status == 'ok') {
            $session->setTransactionID($response->id);
            $session->update($connection);
            
            $this->redirect($response->url);
        } else {
            if ($response->status === 'error') {
                // Log the error
                $this->forward('cancel');
            }
            $this->forward('cancel');
        }
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
        $products = DividoHelper::getOrderProducts($basket);

        $title = DividoHelper::getTitle();
        $description = DividoHelper::getDescription();

        $user = $this->getUser();
        $customer = DividoHelper::getCustomerDetailsFormatted($user);

        $displayWarning = [];
        $displayFinance = false;
        $amount = $this->getAmount();
        $minCartAmount = DividoHelper::getCartThreshold();
        if ($amount >= $minCartAmount) {
            $displayFinance = true;
        } else {
            $displayWarning[] = 'Cart does not meet minimum Finance Requirement.';
        }

        if ($customer['address']!=$customer['shippingAddress']) {
            $displayFinance = false;
            $displayWarning[] = "Shipping and billing address must match.";
        }

        $apiKey = DividoHelper::getApiKey();
        if (empty($apiKey)) {
            $displayFinance = false;
            $displayWarning[] =  "No Api Key Detected. Please contact the merchant.";
        }
        
        list($key,$stuff) = preg_split("/\./", $apiKey);
        $this->View()->assign('apiKey', $key);
        $this->View()->assign('title', $title);
        $this->View()->assign('amount', $amount);
        $this->View()->assign('prefix', '');
        $this->View()->assign('suffix', '');
        $this->View()->assign('displayForm', $displayFinance);
        $this->View()->assign('displayWarning', $displayWarning);
        $this->View()->assign('basket_plans', implode(",", DividoHelper::getBasketPlans($products)));
    }

    /**
     * Return action method
     *
     * Gets the PaymentResponse,
     * Fetches the corresponding session,
     * Checks to see if the response token is valid for the session
     * Checks to see if the order is already complete
     * Completes the order or displays the completed order
     * or returns an appropriate response on failure
     * (Probably a bit too busy!)
     *
     * @return void
     */
    public function returnAction()
    {
        $paymentService = $this->container->get('divido_payment.divido_payment_service');
        $orderService = $this->container->get('divido_payment.divido_order_service');
        
        /** @var DividoPayment\Components\PaymentResponse $response */
        $response = $paymentService->createPaymentResponse($this->Request());

        if(isset($response->sessionId) && isset($response->token)){
            $sessionId = filter_var($response->sessionId,FILTER_SANITIZE_NUMBER_INT);
            $connection = $this->container->get('dbal_connection');
            $session = new \DividoPayment\Models\DividoSession;
            
            if($session->retrieveFromDb($sessionId, $connection)){
                $data = $session->getData();
                
                $customer_number = $data['sUserData']['additional']['user']['customernumber'];
                $amount = $data['sBasket']['sAmount'];
                /*
                /   If response token matches the information in the divido session 
                /   $service = /Components/DividoPayment/DividoPaymentService.php 
                */
                if ($paymentService->isValidToken($amount, $customer_number, $response->token)) {
                    // If we haven't already generated the order already:
                    if(is_null($session->getOrderNumber())){
                        $device = $this->Request()->getDeviceType();
                        $order = $session->createOrder($device);

                        $orderNumber = $orderService->saveOrder();
                        if($orderNumber){
                            $orderID = $connection->lastInsertId();

                            $order->setPaymentStatus($orderID, self::PAYMENTSTATUSPAID);
                            
                            $data['ordernumber'] = $orderNumber;
                            $data['cleared'] = self::PAYMENTSTATUSPAID;
                            
                            // Persist divido information to display on order in backend
                            $attributePersister = $this->container->get(
                                'shopware_attribute.data_persister'
                            );

                            $attributeData = array(
                                'divido_finance_id' => $session->getPlan(),
                                'divido_deposit_value' => $session->getDeposit()
                            );
                            
                            $attributePersister->persist(
                                $attributeData,
                                's_order_attributes',
                                $orderID
                            );

                            $session->setOrderNumber($orderNumber);
                            $session->update();

                            /*
                            /   Close the open session, in case we create an order with the same
                            /   session ID as the order we're currently closing
                            */
                            session_write_close();
                        }else{
                            $this->View()->assign('error', 'Could not create order');
                            $this->View()->assign('template', 'frontend/divido_payment/error.tpl');
                        }
                    }else{
                        $data['ordernumber'] = $session->getOrderNumber();
                    }

                    /*
                    /   Assign the relevant stored session information to the appropriate Smarty variables
                    */
                    $this->sendDataToSmarty($data);
                    $this->View()->assign('template', 'frontend/divido_payment/success.tpl');
                }else{
                    $this->View()->assign('error', 'Invalid token.');
                    $this->View()->assign('template', 'frontend/divido_payment/error.tpl');
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
        DividoHelper::debug('Webhook', 'info');

        /*
         * @var DividoPaymentService $service 
         */
        $service = $this->container->get('divido_payment.divido_payment_service');
        $response = $service->createWebhookResponse($this->Request());
            
        if (!$response->status) {
            DividoHelper::debug('No Response Status', 'error');
            echo 'no response';
            die();
        }

        //DividoHelper::hmacSign();

        $transactionID = $response->proposal;
        $paymentUniqueID = $response->token;

        DividoHelper::debug('Webhook data:'.serialize($response), 'error');
        DividoHelper::debug('Webhook TransactionID:'.$transactionId, 'info');
        DividoHelper::debug('Webhook Unique Payment ID:'.$paymentUniqueId, 'info');
        $message ='';

        switch ($response->status) {
            case self::STATUS_PROPOSAL:
                DividoHelper::debug('Webhook: Proposal', 'info');
                $message ='Proposal Hook Success';
                $session_status = self::PAYMENTSTATUSOPEN;
                break;

            case self::STATUS_ACCEPTED:
                DividoHelper::debug('Webhook: Accepted', 'info');
                $message ='Accepted Hook Success';
                $session_status = self::PAYMENTSTATUSOPEN;
                break;

            case self::STATUS_SIGNED:
                DividoHelper::debug('Webhook: Signed', 'info');
                $message ='Signed Hook Success';
                $session_status = self::PAYMENTSTATUSPAID;
                break;

            case self::STATUS_DECLINED:
                DividoHelper::debug('Webhook: Declined', 'info');
                $message ='Declined Hook Success';
                $order_status = self::PAYMENTREVIEWNEEDED;
                $session_status = self::PAYMENTREVIEWNEEDED;
                break;

            case self::STATUS_CANCELED:
                DividoHelper::debug('Webhook: Canceled', 'info');
                $message ='Canceled Hook Success';
                $order_status = self::PAYMENTCANCELLED;
                $session_status = self::PAYMENTCANCELLED;
                break;

            case self::STATUS_DEPOSIT_PAID:
                DividoHelper::debug('Webhook: Deposit Paid', 'info');
                $message ='Deposit Paid Hook Success';
                $session_status = self::PAYMENTSTATUSOPEN;
                break;

            case self::STATUS_ACTION_LENDER:
                DividoHelper::debug('Webhook: Deposit Paid', 'info');
                break;
            
            case self::STATUS_COMPLETED:
                $message ='Completed';
                DividoHelper::debug('Webhook: Completed', 'info');
                break;

            case self::STATUS_DEFERRED:
                $message ='Deferred Success';
                DividoHelper::debug('Webhook: STATUS_DEFERRED', 'info');
                break;

            case self::STATUS_FULFILLED:
                $message ='STATUS_FULFILLED Success';
                DividoHelper::debug('Webhook: STATUS_FULFILLED', 'info');
                break;

            case self::STATUS_REFERRED:
                $message ='Order Referred Success';
                DividoHelper::debug('Webhook: Referred', 'info');
                break;

            default:
                $message ='Empty Hook';
                DividoHelper::debug('Webhook: Empty webook', 'warning');
                break;
        }

        if(isset($order_status)){
            $this->savePaymentStatus(
                $transactionID,
                $paymentUniqueID,
                $order_status
            );
        }
        
        if(isset($session_status)){
            $connection = $this->container->get('dbal_connection');
            $session = new \DividoPayment\Models\DividoSession;
            $update = [
                "status" => $session_status,
                "transactionID" => $transactionID,
                "temporaryID" => $paymentUniqueID
            ];
            $session->updateByReference($connection, $update, 'temporaryID');
        }

        //update order based on whats sent through
        //use signmature to determine if basket is set
        //create order on signed
        $this->respond(true, $message, false);
        return;
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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

     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
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
     // sent to DividoHelper
    protected function removeDividoSessionsByOrderNumber($orderNumber){
        $session_delete_sql = "DELETE FROM `s_divido_sessions` WHERE `orderID`=? LIMIT 1";
        $success = Shopware()->Db()->query($session_delete_sql, [$orderNumber]);
        return $success;
    }

    /**
     * Generate an order based on the session data stored in the s_divido_sessions table
     * 
     * @param string $transactionId The ID generated when making a Divido Credit Request
     * @param array $session The session information stored in `s_divido_sessions` table `data` column
     * 
     * @return orderNumber (string) The order number of the new Order stored in s_order
     */
     // sent to DividoSession model
    protected function createOrder($transactionId, $token, $session){
        $basket = $session['sBasket'];
        $order = Shopware()->Modules()->Order();
        $order->sUserData = $session['sUserData'];
        $order->sComment = "";
        $order->sBasketData = $basket;
        $order->sAmount = $basket['sAmount'];
        $order->sAmountWithTax = 
            !empty($basket['AmountWithTaxNumeric']) ? $basket['AmountWithTaxNumeric'] : $basket['AmountNumeric'];
        $order->sAmountNet = $basket['AmountNetNumeric'];
        $order->sShippingcosts = $basket['sShippingcosts'];
        $order->sShippingcostsNumeric = $basket['sShippingcostsWithTax'];
        $order->sShippingcostsNumericNet = $basket['sShippingcostsNet'];
        $order->bookingId = $transactionId;
        $order->dispatchId = Shopware()->Session()->sDispatch;
        $order->sNet = empty($session['sUserData']['additional']['charge_vat']);
        $order->uniqueID = $token;
        $order->deviceType = $this->Request()->getDeviceType();
        
        $order->sCreateTemporaryOrder();
        $orderNumber = $order->sSaveOrder();
        
        return $orderNumber;
    }

    /**
     * Take order information as received from s_divido_sessions table
     * and assign the data to the relevant Smarty variables
     * 
     * @param array $order The session information stored in `s_divido_sessions` table `data` column
     * 
     * @return void
     */
    protected function sendDataToSmarty($order){
        foreach($order as $key=>$value){
            $this->View()->assign($key,$value);
        }
        $addresses['billing'] = $order['sUserData']['billingaddress'];
        $addresses['shipping'] = $order['sUserData']['shippingaddress'];
        $addresses['equal'] = 
            ($order['sUserData']['billingaddress'] == $order['sUserData']['shippingaddress']);
        $this->View()->assign('sAddresses', $addresses);
        $this->View()->assign('sOrderNumber', $order['ordernumber']);
        $this->View()->assign('sShippingcosts', $order['sBasket']['sShippingcosts']);
        $this->View()->assign('sAmountNet', $order['sBasket']['AmountNetNumeric']);
    }
}
