<?php

abstract class Payson_Payson_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract {

    /**
     * @inheritDoc
     */
    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false; // true
    protected $_canUseCheckout = true; // true
    protected $_canUseForMultishipping = false; // true
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false; // true

    /**
     * @inheritDoc
     */
//    protected $_canCancelInvoice = true;

    /*
     * Protected methods
     */

    protected function GetCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function GetQuote() {
        return $this->GetCheckout()->getQuote();
    }

    /*
     * Public methods
     */

    /**
     * Redirect url when user place order
     *
     * @return	string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payson/checkout/redirect', array('_secure' => true));
    }

    /**
     * @inheritDoc
     */
    /* public function initialize($payment_action, $state_object)
      {
      $state_object->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
      $state_object->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
      $state_object->setIsNotified(false);

      return $this;
      } */

    /**
     * Whether this paymend method is available for specified currency
     *
     * @param	string	$currency
     * @return	bool
     */
    public function canUseForCurrency($currency) {
        return Mage::getModel('payson/config')->IsCurrencySupported(Mage::app()->getStore()->getCurrentCurrencyCode());
    }

    /**
     * @inheritDoc
     */
//  Not currently used
    public function refund(Varien_Object $payment, $amount) {
        Mage::log(' Det sker en riktig kreditering ');
        $api = Mage::helper('payson/api');
        $order = $payment->getOrder();
        $method = $payment->getMethod();
        $helper = Mage::helper('payson');
        $order_id = $order->getData('increment_id');
        $checkoutId = $this->checkoutId($order_id);
        $paysonOrder = $api->getPaysonApi($checkoutId, '', '');
        $Message = 'Payment was credited at Payson';
        if ($order->getBaseGrandTotal() != $amount || $api->getNewResponceObject($paysonOrder, 'credit') != $amount) {
            Mage::throwException('Invalid amount');
        }
        if ($method == "payson_standard") {
            if ($api->getNewResponceObject($paysonOrder, 'status') == 'PaidToAccount') {
                
                $updateStatus = $api->getNewResponceObject($paysonOrder, 'statusupdate', 'credited');
                $object = $api->getNewResponceObject($updateStatus, 'response');
                print_r($object);
                die;
                $api->getPaysonApi($checkoutId, 'PUT', $object);
                $order->addStatusHistoryComment($helper->__($Message));
                Mage::getSingleton('core/session')->addSuccess($Message);
            } else {
                $errorMessage = Mage::helper('payson')->__('Unable to ship order: ' . $order_id . '. Due to wrong current status, it should be PaidToAccount but it´s current status is: ' . $paysonOrder->getResponsObject()->status);
                Mage::getSingleton('core/session')->addError($errorMessage);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function void(Varien_Object $payment) {
        $payment->setTransactionId('auth')
                ->setIsTransactionClosed(0);
        return $this;
    }

    /**
     * @inheritDoc
     */
    private function checkoutId($realOrderIs) {
        $resource = Mage::getSingleton('core/resource');
        $order_table = $resource->getTableName('payson_order');
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $payson_order = "Select token from  " . $order_table . " where order_id = '$realOrderIs'";

        $rows = $connection->fetchAll($payson_order);

        foreach ($rows as $inner) {
            $checkoutId = $inner['token'];
        }
        return $checkoutId;
    }

    public function cancel(Varien_Object $payment) {
        $order = $payment->getOrder();
        $order_id = $order->getData('increment_id');

        $api = Mage::helper('payson/api');
        $helper = Mage::helper('payson');
        $api->PaymentDetails($order_id);


        if (($order->getPayment()->getMethod() == 'payson_standard') && ($order->getState() === Mage_Sales_Model_Order::STATE_PROCESSING || $order->getState() === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)) {
            $checkoutId = $this->checkoutId($order_id);
            $paysonOrder = $api->getPaysonApi($checkoutId, '', '');

            $updateStatus = $api->getNewResponceObject($paysonOrder, 'statusupdate', 'canceled');


            $object = $api->getNewResponceObject($updateStatus, 'response');
            $api->getPaysonApi($checkoutId, 'PUT', $object);

            $order->addStatusHistoryComment($helper->__('Order was canceled at Payson'));

            $payment->setTransactionId('auth')->setIsTransactionClosed(1);
        } else {
            Mage::throwException($helper->__('Payson is not ready to cancel the order. Please try again later.'));
        }
        return $this;
    }

}
