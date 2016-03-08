        <?php

include ('lib/paysonapi.php');

class Payson_Payson_Helper_Api {
    /*
     * Constants
     */

//    var $invoiceAmountMinLimit = 30;

    const DEBUG_MODE = false;
    const APPLICATION_ID = 'Magento_M1';
    const MODULE_NAME = 'Magento_Embedded';
    const MODULE_VERSION = '1.0.0';
    const DEBUG_MODE_MAIL = '';
    const DEBUG_MODE_AGENT_ID = '4';
    const DEBUG_MODE_MD5 = '2acab30d-fe50-426f-90d7-8c60a7eb31d4';
    const DEBUG_URL = 'https://test-api.payson.se/2.0/Checkouts/';
    const PAYMENT_URL = 'https://api.payson.se/2.0/Checkouts/';
    const STATUS_READY_TO_PAY = 'readyToPay';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CREDITED = 'CREDITED';
    const STATUS_INCOMPLETE = 'INCOMPLETE';
    const STATUS_ERROR = 'ERROR';
    const STATUS_DENIED = 'DENIED';
    const STATUS_ABORTED = 'ABORTED';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_REVERSALERROR = 'REVERSALERROR';
    const GUARANTEE_STATUS_WAITINGFORSEND = 'WAITINGFORSEND';
    const GUARANTEE_STATUS_WAITINGFORACCEPTANCE = 'WAITINGFORACCEPTANCE';
    const GUARANTEE_STATUS_WAITINGFORRETURN = 'WAITINGFORRETURN';
    const GUARANTEE_STATUS_WAITINGFORRETURNACCEPTANCE = 'WAITINGFORRETURNACCEPTANCE';
    const GUARANTEE_STATUS_RETURNNOTACCEPTED = 'RETURNNOTACCEPTED';
    const GUARANTEE_STATUS_NOTRECEIVED = 'NOTRECEIVED';
    const GUARANTEE_STATUS_RETURNNOTRECEIVED = 'RETURNNOTRECEIVED';
    const GUARANTEE_STATUS_MONEYRETURNEDTOSENDER = 'MONEYRETURNEDTOSENDER';
    const GUARANTEE_STATUS_RETURNACCEPTED = 'RETURNACCEPTED';
    const UPDATE_ACTION_CANCELORDER = 'CANCELORDER';
    const UPDATE_ACTION_SHIPORDER = 'SHIPORDER';
    const UPDATE_ACTION_CREDITORDER = 'CREDITORDER';
    const UPDATE_ACTION_REFUNDORDER = 'REFUND';
    const GUARANTEE_REQUIRED = 'REQUIRED';
    const GUARANTEE_OPTIONAL = 'OPTIONAL';
    const GUARANTEE_NO = 'NO';

    //const PMETHOD ='';

    /*
     * Private properties
     */
    private $discountType;
    private $_discounts = array();
    private $numberofItems;
    private $discountVat = 0.0;
    private $_order = null;
    private $response;
    private $order_discount_item = 0.0;
    /* @var $_config Payson_Payson_Model_Config */
    private $_config;
    /* @var $_helper Payson_Payson_Helper_Data */
    private $_helper;
    private $_products = array();
    private $percentage;

    /*
     * Private methods
     */

    public function __construct() {
        $this->_config = Mage::getModel('payson/config');
        $this->_helper = Mage::helper('payson');
    }

    private function getCredentials() {

        $merchantId = $this->_config->get('test_mode') ? self::DEBUG_MODE_AGENT_ID : $this->_config->Get('agent_id');
        $apiKey = $this->_config->get('test_mode') ? self::DEBUG_MODE_MD5 : $this->_config->Get('md5_key');

        $credentials = array('userId' => $merchantId, 'userKey' => $apiKey);
        return $credentials;
    }

    private function getHttpClient($url) {


        $http_client = new Zend_Http_Client($url);

        $http_client->setMethod(Zend_Http_Client::POST)
                ->setHeaders(array
                    (
                    'PAYSON-SECURITY-USERID' => $this->_config->get('test_mode') ? self::DEBUG_MODE_AGENT_ID : $this->_config->Get('agent_id'),
                    'PAYSON-SECURITY-PASSWORD' => $this->_config->get('test_mode') ? self::DEBUG_MODE_MD5 : $this->_config->Get('md5_key'),
                    'PAYSON-APPLICATION-ID' => self::APPLICATION_ID,
                    'PAYSON-MODULE-INFO' => self::MODULE_NAME . '|' . self::MODULE_VERSION . '|' . Mage::getVersion()
        ));

        return $http_client->resetParameters();
    }

    //Private functions for Swedish discount and vat calculations
    private function setAverageVat($vat) {
        $this->discountVat = $vat;
    }

    private function getAverageVat() {
        return $this->discountVat;
    }

    private function setDiscountType($type) {
        $this->discountType = $type;
    }

    private function getDiscountType() {
        return $this->discountType;
    }

    private function setNumberOfItems($items) {
        $this->numberofItems = $items;
    }

    private function getNumberOfItems() {
        return $this->numberofItems;
    }

    private function getStoreCountry() {
        $countryCode = Mage::getStoreConfig('general/country/default');
        $country = Mage::getModel('directory/country')->loadByCode($countryCode);
        return $country->country_id;
    }

    private function setSwedishDiscountItem($item, &$total, $orderitems) {

        foreach ($orderitems as $items) {
            /*
             * $items[3] is Vat of the article.
             * orderVat either 0 or the Vat * number of ordered articles
             * moms is the total value of the vats for all articles
             * numberOfItems is total quantity of ordered articles
             */
            $orderVat = $items[3];
            ($orderVat == 0) ? $orderVat : $orderVat = ($orderVat * $items[2]);
            $moms += $orderVat;
            $numberOfItems += $items[2];
        }
        $totalMoms = $moms / $numberOfItems;
        $rule = Mage::getModel('salesrule/rule')->load($item->getAppliedRuleIds());
        $total -= $item->getDiscountAmount();
        $discountAmount = $item->getDiscountAmount();
        $this->setNumberOfItems($numberOfItems);
        $this->setDiscountType($rule->simple_action);
        $this->setAverageVat($totalMoms);
        $this->order_discount_item += $discountAmount;
    }

    private function setInternationalDiscountItem($item, &$total) {
        $rule = Mage::getModel('salesrule/rule')->load($item->getAppliedRuleIds());
        $this->setDiscountType($rule->simple_action);
        $total -= $item->getDiscountAmount();

        $this->order_discount_item += $item->getDiscountAmount();
    }

    /**
     * Helper for Pay()
     *
     * @param	Mage_Sales_Model_Order_Item $item
     * @param	int		$total
     * @return	array
     */
    private function prepareOrderItemData($item, &$total) {
        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')
                ->load($item->getProductId());

        $attributesString = "";
        if (($children = $item->getChildrenItems()) != null && !$product->isConfigurable()) {
            $args = array();

            $this->prepareProductData($item->getName(), $item->getQtyOrdered(), $item->getSku(), 0, 0);
            foreach ($children as $child) {
                $this->prepareOrderItemData($child, $total);
            }
            return;
        }

        $productOptions = $item->getProductOptions();

        if (array_key_exists('attributes_info', $productOptions)) {
            foreach ($productOptions['attributes_info'] as $attribute) {
                $attributesString .= $attribute['label'] . ": " . $attribute['value'] . ", ";
            }

            if ($attributesString != "") {
                $attributesString = substr($attributesString, 0, strlen($attributesString) - 2);
            }
        }

        $name = $item->getName() . ($attributesString != "" ? " - " . $attributesString : "");
        $sku = $item->getSku();

        $tax_mod = (float) $item->getTaxPercent();
        $tax_mod /= 100;
        $tax_mod = round($tax_mod, 5);

        $qty = (float) $item->getQtyOrdered();
        $qty = round($qty, 2);


        $price = (float) $item->getRowTotalInclTax();

        $base_price = ($price / $qty);

        $total += ($price * $qty);

        $rule = Mage::getModel('salesrule/rule')->load($item->getAppliedRuleIds());
        switch ($rule->simple_action) {
            case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
            case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                $this->percentage = ($rule->discount_amount / 100);
                break;
            default:
                break;
        }

        $this->prepareProductData($name, $qty, $sku, $tax_mod, $base_price, '');
    }

    private function generateProductDataForPayson() {
        $orderitemslist = array();

        for ($item = 0; $item < sizeof($this->_products); $item++) {
            $orderitemslist[] = new PaysonExpress\OrderItem(
                    $this->_products[$item]['Description'], $this->_products[$item]['Price'], $this->_products[$item]['Quantity'], $this->_products[$item]['Tax'], $this->_products[$item]['Sku'], $this->_products[$item]['discountRate']
            );
        }
        return $orderitemslist;
    }

    private function generateDiscountDataPayson() {
        $orderitemslist = array();
        for ($item = 0; $item < sizeof($this->_products); $item++) {
            $orderitemslist[] = array(
                $this->_products[$item]['Description'], $this->_products[$item]['Price'], $this->_products[$item]['Quantity'], $this->_products[$item]['Tax'], $this->_products[$item]['Sku'], $this->_products[$item]['discountRate']
            );
        }
        return $orderitemslist;
    }

    private function generateDiscountype() {
        $discountitemslist = array();
        for ($i = 0; $i < sizeof($this->_discounts); $i++) {

            $type = new PaysonExpress\OrderItem(
                    $this->_discounts[$i]['Description'], $this->_discounts[$i]['Price'], $this->_discounts[$i]['Quantity'], $this->_discounts[$i]['Tax'], $this->_discounts[$i]['Sku']
            );
        }
        if (isset($type)) {
            $type->setType('discount');
            $discountitemslist[] = $type;
        }

        return $discountitemslist;
    }

    private function prepareProductData($description, $qty, $sku, $tax_mod, $base_price, $productItem) {


        ($productItem == 'Shipping') ? $discount = 0 : $discount = $this->percentage;
        if (!isset($discount)) {
            $discount = 0;
        }

        $description2 = strlen($description) <= 128 ? $description : substr($description, 128);
        $sku2 = strlen($sku) <= 128 ? $sku : substr($sku, 128);
        $this->_products[] = array("Description" => $description2, "Quantity" => $qty, "Sku" => $sku2, "Tax" => $tax_mod, "Price" => $base_price, "discountRate" => $discount);
    }

    /**
     * Helper for Pay()
     *
     * @param	object	$order
     * @param	object	$customer
     * @param	object	$store
     * @param	int		$i
     * @param	int		$total
     */
    private function prepareOrderShippingData($order, $customer, $store, &$total) {
        $tax_calc = Mage::getSingleton('tax/calculation');

        $tax_rate_req = $tax_calc->getRateRequest(
                $order->getShippingAddress(), $order->getBillingAddress(), $customer->getTaxClassId(), $store);


        if (($price = (float) $order->getShippingInclTax()) > 0) {
            $tax_mod = $tax_calc->getRate($tax_rate_req->setProductClassId(
                            Mage::getStoreConfig('tax/classes/shipping_tax_class')));
            $tax_mod /= 100;
            $tax_mod = round($tax_mod, 5);

            $price -= (float) $order->getShippingDiscountAmount();

            $total += ($price * (1 + $tax_mod));



            $this->prepareProductData($order->getShippingDescription(), 1, $order->getShippingMethod(), $tax_mod, $price, 'Shipping');
        }
    }

    /*
     * Public methods
     */

    /**
     * Get API response
     *
     * @return	object
     */
    public function receiptPage() {
        $Config = (int) $this->_config->get('show_receipt_page');
        $reciept2 = 'false';
        if ($Config === 1) {
            $reciept2 = 'true';
        }
        return $reciept2;
    }

    public function colorScheme() {
        $inputValue = (int) $this->_config->Get('colour_theme');
        switch ($inputValue) {
            case 0: {
                    $colour = 'White';
                    break;
                }
            case 1: {
                    $colour = 'Gray';
                    break;
                }
            case 2: {
                    $colour = 'Blue';
                    break;
                }
            default : {
                    $colour = 'Blue';
                }
        }
        return $colour;
    }

    public function iframeSize() {
        $inputValue = (int) $this->_config->Get('iframe_size');
        switch ($inputValue) {
            case 0:
                $iframe = '300px';
                break;
            case 1:
                $iframe = '400px';
                break;
            case 2:
                $iframe = '500px';
                break;
            case 3:
                $iframe = '600px';
                break;
            case 4:
                $iframe = '700px';
                break;
            case 5:
                $iframe = '800px';
                break;
            case 6:
                $iframe = '900px';
                break;
            case 7:
                $iframe = '1000px';
                break;
        }
        return $iframe;
    }

    public function getReviewButtonTemplate($name, $block) {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($quote) {
            $payment = $quote->getPayment();
            if ($payment && in_array($payment->getMethod(), $this->_hssMethods)) {
                return $name;
            }
        }

        if ($blockObject = Mage::getSingleton('core/layout')->getBlock($block)) {
            return $blockObject->getTemplate();
        }

        return '';
    }

    public function GetResponse() {
        return $this->response;
    }

    public function setcheckoutId($paysonRespons) {
        $this->paysonRespons = $paysonRespons;
    }

    public function getcheckoutId() {

        return $this->paysonRespons;
    }

    public function getPaymentMode() {
        $payment_url = ($Config = (int) $this->_config->get('test_mode') == 'true') ? self::PAYMENT_URL : self::DEBUG_URL;
        return $payment_url;
    }

    private function cancelOrder($checkoutId) {
        Mage::getModel('sales/order')->loadByIncrementId($increment_id);
        $order = $this->getOrder();
        if (!is_null($order = $this->getOrder())) {
            $order->cancel();
            if ($message != '') {
                $order->addStatusHistoryComment($message);
            }
        }
        $quoteId = $this->getOrder()->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(true)->save();
        $order->save();
        return $this;
    }
         
    protected function _redirect($path, $arguments = array()) {
        return $this->setRedirectWithCookieCheck($path, $arguments);
    }

    public function GetPayIframeHtml($checkoutid = null, $return = null, $returnedSize= null) {
        $Currentcheckoutid = ($checkoutid === null) ? $this->getcheckoutId() : $checkoutid;
        $userId = $this->getCredentials();
        $callLibary = new PaysonExpress\PaysonApi($userId['userId'], $userId['userKey']);
        if ($Currentcheckoutid) {            
            $callLibary->setCheckoutId($Currentcheckoutid);
            
            $callLibary->doRequest($this->getPaymentMode());
            $test = $callLibary->getResponsObject()->snippet;
            Mage::log($test);
            $str = "url='";
            $url = explode($str, $test);
            $newStr = "'>";
            $checkoutUrl = explode($newStr, $url[1]);
            $edit = '';
            if($return == null){
              $edit = Mage::helper('payson')->__('Do you want to change your cart? Please click on Account then My account here you can choose to edit preferred cart.'); 
            } 
            $frameheight = ($returnedSize==null)? $this->iframeSize(): $returnedSize;
            return "<p>" . $edit . " </p><iframe id='checkoutIframe' name='checkoutIframe' src='" . $checkoutUrl[0] . "' style='width:100%; height:" . $frameheight . "; background-color:white;' frameborder='0'  scrolling='no'></iframe>";
        } else {
            Mage::log('Unable to load Payson payment window due to invalid id ' . $Currentcheckoutid);
            return Mage::helper('payson')->__("A Payson payment window could not be generated. Please inform the store, refresh the page and try another payment option.");
        }
    }

    public function getNewResponceObject($object, $parameter = null, $update = null) {
        switch ($parameter) {
            case 'statusupdate':
                $object->getResponsObject()->status = $update;
                $requestedObject = $object;
                break;
            case 'credit':
                $requestedObject = $object->getResponsObject()->order->totalPriceIncludingTax;
                break;
            case 'response':
                $requestedObject = $object->getResponsObject();
                break;
            default:
                $requestedObject = 'No value';
                break;
        }


        return $requestedObject;
    }

    public function getPaysonApi($checkoutId, $status, $body = null) {
        $userId = $this->getCredentials();
        $callLibary = new PaysonExpress\PaysonApi($userId['userId'], $userId['userKey']);
        if ($status == '') {
            $callLibary->setCheckoutId($checkoutId);
            $callLibary->doRequest($this->getPaymentMode());
            return $callLibary;
        } elseif ($status == 'PUT') {

            $callLibary->doRequest($this->getPaymentMode(), $status, $checkoutId, $body);
        }
        if (count($callLibary->getpaysonResponsErrors()) != 0) {
            foreach ($callLibary->getpaysonResponsErrors() as $value) {
                Mage::throwException('Transaction failed(463). Due to error:' . $value->getErrorId() . ' Message: ' . $value->getMessage() . ' If any, these was the parameters: ' . $value->getParameter());
            }
        }
    }

    /**
     * http://api.payson.se/#title8
     *
     * @param	object	$order
     * @return	object					
     */
    //TODO, not complete function


    public function vatDiscount() {
        $inputValue = (int) $this->_config->Get('vat_discount');
        $enableVatDiscount = 'false';
        if ($inputValue === 1) {
            $enableVatDiscount = 'true';
        }
        return $enableVatDiscount;
    }

    public function Pay(Mage_Sales_Model_Order $order) {
        $payData = new PaysonExpress\PayData();

        /* @var $store Mage_Core_Model_Store */
        $store = Mage::app()->getStore($order->getStoreId());
        $customer = Mage::getModel('customer/customer')
                ->load($order->getCustomerId());
        $billing_address = $order->getBillingAddress();

        // Need a two character locale code
        $locale_code = Mage::getSingleton('core/locale')->getLocaleCode();
        $locale_code = strtoupper(substr($locale_code, 0, 2));

        switch ($locale_code) {
            case 'DK': {
                    $locale_code = 'DK';
                    break;
                }
            case 'NO': {
                    $locale_code = 'NO';
                    break;
                }
            case 'SV': {
                    $locale_code = 'SV';
                    break;
                }
            case 'FI':
            default: {
                    $locale_code = 'EN';
                }
        }

        $merchantId = $this->getCredentials()['userId'];
        $apiKey = $this->getCredentials()['userKey'];
        $paysonApi = new PaysonExpress\PaysonApi($merchantId, $apiKey);
        $confirmationUri = Mage::getUrl('payson/checkout/return', array('_secure' => true));
        $notificationUri = Mage::getUrl('payson/ipn/notify', array('_secure' => true));
        $termsUri = "http://www.google.se/";
        $country = '';
        switch ($billing_address->country) {
            case 'SE': {
                    $country = 'Sverige';
                }
            case 'FI': {
                    $country = 'Finland';
                }
            case 'NO': {
                    $country = 'Norge';
                }
            case 'DK': {
                    $country = 'Danmark';
                }
            case 'EN': {
                    $country = 'England';
                }
            default: {
                    $country = 'Sverige';
                }
        }
        $module = self::MODULE_NAME .' Version ' . self::MODULE_VERSION;
        $paysonMerchant = new PaysonExpress\PaysonMerchant($merchantId, $confirmationUri, $notificationUri, $termsUri, $module);
        $paysonApi->setCustomer(new PaysonExpress\Customer(
                $billing_address->city, $country, '', $billing_address->email, $billing_address->firstname, $billing_address->lastname, $billing_address->telephone, $billing_address->postcode, $billing_address->street
        ));

        $payData->setCurrencyCode(strtoupper(Mage::app()->getStore()->getCurrentCurrencyCode()));
        $total = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $this->prepareOrderItemData($item, $total);
        }

        $productItems = $this->generateDiscountDataPayson();
        $customerCountry = $order->getBillingAddress()->country_id;

        $coupon_code = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
        if (isset($coupon_code)) {


            $description = 'discount';
            if ($this->getStoreCountry() == 'SE' && $customerCountry == 'SE' && $this->vatDiscount() == 'true') {
                foreach ($order->getAllVisibleItems() as $item) {
                    $this->setSwedishDiscountItem($item, $total, $productItems);
                }
                $Tax = 0;
                switch ($this->getDiscountType()) {
                    case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
                        $this->getAverageVat() == '' ? $Tax : $Tax = $this->getAverageVat();

                        $specialDiscount = ($this->order_discount_item / $this->getNumberOfItems()) * (1 + $Tax);
                        $this->_discounts[] = array("Description" => $description, "Quantity" => $this->getNumberOfItems(), "Sku" => 'SKU', "Tax" => $Tax, "Price" => -$specialDiscount);

                        break;
                    case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                    case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                        break;
                    default:
                        $this->getAverageVat() == '' ? $Tax : $Tax = $this->getAverageVat();
                        $discount = $this->order_discount_item * (1 + $Tax);
                        $this->_discounts[] = array("Description" => $description, "Quantity" => 1, "Sku" => 'SKU', "Tax" => $Tax, "Price" => -$discount);

                        break;
                }
            } else {
                foreach ($order->getAllVisibleItems() as $item) {
                    $this->setInternationalDiscountItem($item, $total);
                }
                switch ($this->getDiscountType()) {

                    case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                    case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                        break;
                    default:

                        $this->_discounts[] = array("Description" => $description, "Quantity" => 1, "Sku" => 'SKU', "Tax" => 0.0, "Price" => -$this->order_discount_item);
                        break;
                }
            }
        }


        $roundedTotal = round($total, 2);

        $this->prepareOrderShippingData($order, $customer, $store, $roundedTotal);
        $orderdata = $this->generateProductDataForPayson();
        if ($this->order_discount_item > 0) {
            $discountitems = $this->generateDiscountype();
            $orderdata = array_merge($orderdata, $discountitems);
        }
        $payData->setOrderItems($orderdata);
        $colour = (string) $this->colorScheme();

        $paysonApi->setPayData($payData);

        $paysonApi->setPaysonMerchant($paysonMerchant);
        $paysonApi->setGui(new PaysonExpress\Gui($locale_code, $colour, 'none', 0));
        $paysonApi->doRequest($this->getPaymentMode(), "POST");

        if (count($paysonApi->getpaysonResponsErrors()) == 0) {
            $paysonApi->doRequest($this->getPaymentMode());
        } else {
            //Show the errors
            foreach ($paysonApi->getpaysonResponsErrors() as $value) {
                Mage::log($value->getErrorId() . '  --  ' . $value->getMessage() . '  --  ' . $value->getParameter());
            }
        }
        Mage::log($paysonApi->getRespons());
        $this->setcheckoutId($paysonApi->getCheckoutId());

        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');


        $order_table = $resource->getTableName('payson_order');
        $order_log_table = $resource->getTableName('payson_order_log');


        $db->insert($order_table, array
            (
            'order_id' => $order->getRealOrderId(),
            'added' => new Zend_Db_Expr('NOW()'),
            'updated' => new Zend_Db_Expr('NOW()'),
            'valid' => 1,
            'token' => ($this->getcheckoutId()),
            'store_id' => $order->getStoreId()
        ));

        $payson_order_id = $db->lastInsertId();

        $db->insert(
                $order_log_table, array
            (
            'payson_order_id' => $payson_order_id,
            'added' => new Zend_Db_Expr('NOW()'),
            'api_call' => 'pay',
            'valid' => 1,
            'response' => 'Vaild'
                )
        );

        return $this;
    }

    /**
     * Implements the IPN procedure
     *
     * http://api.payson.se/#title11
     *
     * @param	string	$http_body
     * @param	string	$content_type
     * @return	object	$this
     */
    public function Validate($checkout) {

        // Get the database connection
        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');

        $order_table = $resource->getTableName('payson_order');
        $order_log_table = $resource->getTableName('payson_order_log');


        /* Save data sent by Payson, log entry as invalid by default, this
          value will be changed later in this method if successful. No payson
          order id is set, because we dont have one yet */
        $Paysonorder = $this->getPaysonApi($checkout, '', '');
        $tranactionStatus = strtoupper($Paysonorder->getResponsObject()->status);
        
        Mage::LOG($Paysonorder->getResponsObject());
        //input JSON as array
        $db->insert($order_log_table, array
            (
            'added' => new Zend_Db_Expr('NOW()'),
            'api_call' => 'validate',
            'valid' => 0,
            'response' => ($tranactionStatus)
        ));

        $order_log_id = $db->lastInsertId();

        /* Save fetch mode so that we can reset it and not mess up Magento
          functionality */
        $old_fetch_mode = $db->getFetchMode();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        // Get payson order information and validate token
        $payson_order = $db->fetchRow(
                'SELECT
	id,
	order_id,
        store_id
FROM
	`' . $order_table . '`
WHERE
	valid = 1
AND
	token = ?
LIMIT
	0,1', $checkout);

        if ($payson_order === false) {
            Mage::throwException('Invalid token');
        }

        // Update order log with payson order id
        $db->update($order_log_table, array
            (
            'payson_order_id' => $payson_order->id
                ), array
            (
            'id = ?' => $order_log_id
        ));

        // the order model does not expect FETCH_OBJ!
        $db->setFetchMode($old_fetch_mode);

        /**
         * @var Mage_Sales_Model_Order
         */
        $order = Mage::getModel('sales/order')
                ->loadByIncrementId($payson_order->order_id);

        // Stop if order dont exist
        if (is_null($order->getId())) {
            Mage::throwException('Invalid order');
        }

        if ($order->getState() === Mage_Sales_Model_Order::STATE_COMPLETE) {
            Mage::throwException('Order is no longer active');
        }
        $customerAdress = $Paysonorder->getResponsObject()->customer;

        switch ($tranactionStatus) {
            case 'CREATED': {
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    break;
                }
            case 'READYTOSHIP': {
                    $order->setState(
                            Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, $this->_config->get('test_mode') ? $this->_helper->__('Payson test completed the order payment') : $this->_helper->__('Payson completed the order payment'));
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                    $transactionSave->save();
                    if (isset($customerAdress)) {
                        $address = $order->getShippingAddress();
                        $address->setFirstname($customerAdress->firstName);
                        $address->setLastname($customerAdress->lastName);
                        $address->setStreet($customerAdress->street);
                        $address->setPostcode($customerAdress->postalCode);
                        $address->setCity($customerAdress->city);

                        $country = new Payson_Payson_Helper_Data;

                        if (array_key_exists($customerAdress->country, $country->getCountry())) {
                            $countryId = strtolower($country->getCountry()[$customerAdress->country]);
                            $address->setCountryId($countryId);
                        }
                    }
                    $address->save();

                    break;
                }

            case 'DENIED': {

                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                    $order->addStatusHistoryComment($this->_helper->__('The order was denied by Payson.'));

                    break;
                }
            case 'EXPIRED': {
                    $order->cancel();
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                    break;
                }

            case 'CANCELED': {
                    $order->cancel();
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);

                    break;
                }
            case self::STATUS_REVERSALERROR:
            default: {
                    $order->cancel();
                }
        }

        $order->save();
        $db->update($order_log_table, array
            (
            'valid' => 1
                ), array
            (
            'id = ?' => $order_log_id
        ));

        $db->update($order_table, array
            (
            'ipn_status' => $tranactionStatus
                ), array
            (
            'id = ?' => $payson_order->id
        ));


        return $this;
    }

    public function PaymentDetails($order_id) {

        // Get the database connection
        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');

        $order_table = $resource->getTableName('payson_order');
        $order_log_table = $resource->getTableName('payson_order_log');
        /* Save fetch mode so that we can reset it and not mess up Magento
          functionality */
        $old_fetch_mode = $db->getFetchMode();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        // Get payson order information and validate token
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $payson_order = "Select token from  " . $order_table . " where order_id = '$order_id'";

        $rows = $connection->fetchAll($payson_order);


        foreach ($rows as $inner) {

            $checkoutId = $inner->token;
        }
        $fail = 'false';
        try {
            $payson_order !== false;
        } catch (Exception $e) {
            //$fail = 'true';
            Mage::throwException('Failed to get Payment details (' . $order_id . ')' . $e->getMessage());
        }
        $db->setFetchMode($old_fetch_mode);
        //$message = 'Payment call successfull';
        try {
            $order = $this->getPaysonApi($checkoutId, '', '');
        } catch (Exception $e) {
            Mage::throwException('Failed to get Payson transaction details (' . $order_id . ')' . $e->getMessage());
        }

        $new_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        /*
        if($fail == 'true'){
            $db->insert($order_log_table, array
                (
                'payson_order_id' => $payson_order->id,
                'added' => new Zend_Db_Expr('NOW()'),
                'api_call' => 'payment_details',
                'valid' => (int) 1,
                'responce' => $message
            ));
        }*/
        $orderStatus = $order->getResponsObject()->status;
        if (($new_order->getPayment()->getMethod() == 'payson_standard') && (($orderStatus == 'canceled' || $orderStatus == 'expired'))) {

            if ($order_id !== null && $payson_order !== false) {
                $new_order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Cancel Transaction.');
                $new_order->setStatus("canceled");
                $new_order->save();
            }
        } else {
            $redirectUrl = Mage::getUrl('checkout/cart');
            Mage::getSingleton('checkout/session')->setRedirectUrl($redirectUrl);
        }

        return $this;
    }

    /**
     * http://api.payson.se/#title13
     *
     * @params	int		$order_id	Real order id
     * @params	string	$action
     * @return	object				$this
     */
    public function PaymentUpdate($order_id, $action) {

        // Get the database connection
        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');
        $order_table = $resource->getTableName('payson_order');
        $order_log_table = $resource->getTableName('payson_order_log');
        /* Save fetch mode so that we can reset it and not mess up Magento
          functionality */
        $old_fetch_mode = $db->getFetchMode();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        // Get payson order information and validate token
        $payson_order = $db->fetchRow('SELECT id, token, store_id FROM `' . $order_table . '` WHERE valid = 1 AND order_id = ? LIMIT 0,1', $order_id);
        try {
            $payson_order !== false;
        } catch (Exception $e) {
            Mage::throwException('Invalid order id (' . $order_id . ')' . $e->getMessage());
        }
        $db->setFetchMode($old_fetch_mode);
        $db->insert($order_log_table, array
            (
            'payson_order_id' => $payson_order->id,
            'added' => new Zend_Db_Expr('NOW()'),
            'api_call' => 'payment_update',
            'valid' => (int) 1
        ));
        return $this;
    }

    private function getFormatIfTest($storeID = null, $isForwardURL = FALSE) {

        $stack = array();
        /* @var $isTest bool */
        $isTest = ($this->_config->get('test_mode', $storeID) == "1");

        array_push($stack, self::DEBUG_MODE ? "http" : "https");
        array_push($stack, $isTest && !self::DEBUG_MODE ? "test-" : (self::DEBUG_MODE && !$isForwardURL ? "mvc" : ""));

        if ($isForwardURL == true) {
            array_push($stack, self::DEBUG_MODE ? "app" : "www");
        }

        array_push($stack, self::DEBUG_MODE ? "local" : "se");
        array_push($stack, self::DEBUG_MODE ? "Payment" : "1.0");

        array_push($stack, self::DEBUG_MODE ? "" : "Payment");
        return $stack;
    }

    public function getIpnStatus($order_id) {
        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');
        $order_table = $resource->getTableName('payson_order');
        $query = 'SELECT ipn_status FROM `' . $order_table . '` WHERE order_id = ' . $order_id;
        return $db->fetchRow($query);
    }

    public function paysonApiError($error) {
        $error_code = '<html>
                            <head>
                            <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
				<script type="text/javascript"> 
                                    alert("' . $error . '");
                                    window.location="' . ('/index.php') . '";
				</script>
                            </head>
                           </html>';
        echo $error_code;
        exit;
    }

}
