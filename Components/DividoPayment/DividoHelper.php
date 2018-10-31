<?php
namespace DividoPayment\Components\DividoPayment;

class DividoHelper
{
    /**
     * Log wrapper which checks to see if Debug is on in the
     * plugin config
     *
     * @param mixed  $msg  A string with the message to debug.
     * @param string $type PHP error level error warning.
     *
     * @return void
     */
    public static function debug($msg, $type = false)
    {
        $debug = self::getDividoDebug();

        if (! $debug) {
            return false;
        }
        self::log($msg, $type);
    }

    /**
     * Log Helper
     *
     * @param mixed  $msg  String to be passed
     * @param string $type Type to be used
     *
     * @return void
     */
    public static function log($msg, $type)
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
    public static function createSignature($payload, $sharedSecret)
    {
        $signature = base64_encode(
            hash_hmac('sha256', $payload, $sharedSecret, true)
        );
        return $signature;
    }

    /**
     * Helper to grab the plugin configuration
     *
     * @return array
     */
    public static function getDividoConfig()
    {

        $config = Shopware()
            ->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DividoPayment');

        return $config;
    }

    /**
     * Helper to grab the conf by key
     *
     * @return string
     */
    public static function getConfByKey($key)
    {
        $config = self::getDividoConfig();
        return $config[$key];
    }

    /**
     * Helper to grab api key
     *
     * @return string
     */
    public static function getDividoApiKey()
    {
        $config = self::getDividoConfig();
        return $config['Api Key'];
    }

    /**
     * Helper to grab debug status
     *
     * @return bool
     */
    public static function getDividoDebug()
    {
        $config = self::getDividoConfig();
        return $config['Debug'];
    }

    /**
     * Helper to grab checkout title
     *
     * @return string
     */
    public static function getDividoTitle()
    {
        $config = self::getDividoConfig();
        return $config['Title'];
    }

    /**
     * Helper to grab description
     *
     * @return string
     */
    public static function getDividoDescription()
    {
        $config = self::getDividoConfig();
        return $config['Description'];
    }
    /**
     * Helper to grab shared secret value
     *
     * @return string
     */
    public static function getDividoSharedSecret()
    {
        $config = self::getDividoConfig();
        return $config['Shared Secret'];
    }
    /**
     * Helper to grab cart threshold value
     *
     * @return int
     */
    public static function getDividoCartThreshold()
    {
        $config = self::getDividoConfig();
        return $config['Cart Threshold'];
    }

    /**
     * Create customer details for divido credit request
     *
     * @return Array
     */
    public function getCustomerDetailsFormatted($user){
        self::debug('Formatting Customer Details');

        $billing = $user['billingaddress'];
        $shipping = $user['shippingaddress'];

        $billingAddress=self::transformShopwareToDividoAddress($billing);
        $shippingAddress=self::transformShopwareToDividoAddress($shipping);
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
        self::debug('CustomerArray:'.serialize($customerArray), 'info');

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
        self::debug('Add array:'.serialize($shopwareAddressArray), 'info');

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
        $formattedArray['currency']  = self::getCurrencyShortName();
        $formattedArray['amount']    = self::getAmount();
        $formattedArray['reference'] = self::getOrderNumber();
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

}