<?php

require_once 'app/Mage.php';

class Payson_Payson_CheckoutController extends Mage_Core_Controller_Front_Action {
    /*
     * Private properties
     */

    private $_session;
    private $_order = null;
    /* @var $_config Payson_Payson_Model_Config */
    private $_config;
    /* @var $_helper Payson_Payson_Helper_Data */
    private $_helper;

    /*
     * Private methods
     */

    public function _construct() {
        $this->_config = Mage::getModel('payson/config');
        $this->_helper = Mage::helper('payson');
    }

//    /*
//     * Private methods
//     */

    private function getSession() {
        if (!isset($this->_session)) {
            $this->_session = Mage::getSingleton('checkout/session');
        }

        return $this->_session;
    }

    /**
     * 
     * @return Mage_Sales_Model_Order
     */
    private function getOrder() {
        if (!isset($this->_order)) {
            $increment_id = $this->getSession()->getData('last_real_order_id');

            if ($increment_id) {
                $this->_order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);

                if (is_null($this->_order->getId())) {
                    $this->_order = null;
                }
            }
        }

        return $this->_order;
    }

    public function returnAction() {
        $tempOrder = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($tempOrder);

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "Select token from  `payson_order` where order_id = '$tempOrder'";
        $rows = $connection->fetchAll($sql);
        $checkoutId = '';
        foreach ($rows as $inner) {
            $checkoutId = $inner['token'];
        }
        
        $callPaysonApiConf = Mage::helper('payson/api')->getPaysonApi($checkoutId, '', '');       
        $receipt =  Mage::helper('payson/api')->receiptPage();
        $return = 'true';
        $returnMessage =  Mage::helper('payson/api')->GetPayIframeHtml($checkoutId, $return, '500px');
        $receiptSuccess = 'checkout/onepage/success';
        $receiptFail = 'checkout/onepage/failure';
        $status = strtoupper($callPaysonApiConf->getResponsObject()->status);
        //Mage::log('ID -> '.$checkoutId.' Status -> '. $status);
        switch ($status) {
            case 'READYTOSHIP': {
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                    $order->sendNewOrderEmail()->save();
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                    $transactionSave->save();
                    $SuccessMessage = Mage::helper('payson')->__('The payment was successfully completed at Payson.'); 
                    $receipt === 'true' ? Mage::getSingleton('core/session')->addSuccess($returnMessage) : Mage::getSingleton('core/session')->addSuccess($SuccessMessage);
                    $this->_redirect($receiptSuccess);
                    break;
                }
            case 'CREATED': {
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    $receipt === 'true' ? Mage::getSingleton('core/session')->addNotice($returnMessage) :'';
                    $this->_redirect($receiptSuccess);
                    break;
                }
            case 'EXPIRED': {
                    $errorMessage = Mage::helper('payson')->__('The payment was expired by Payson. Please, try a different payment method');
                    $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true);
                    
                    $receipt === 'true' ? Mage::getSingleton('core/session')->addNotice($returnMessage) :  Mage::getSingleton('core/session')->addError($errorMessage);
                    $this->_redirect($receiptFail);

                    break;
                }
            case 'CANCELED': {
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                    $errorMessage = Mage::helper('payson')->__('Order was canceled at Payson');
                    if ($this->_config->restoreCartOnCancel()) {
                        $this->restoreCart();
                    }
                    $receipt === 'true' ? Mage::getSingleton('core/session')->addNotice($returnMessage) :  Mage::getSingleton('core/session')->addError($errorMessage);
                    $this->_redirect($receiptFail);


                    break;
                }
            case 'DENIED': {
                    $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true);
                    $errorMessage = Mage::helper('payson')->__('Order was denied at Payson');
                    if ($this->_config->restoreCartOnCancel()) {
                        $this->restoreCart();
                    }
                    $receipt === 'true' ? Mage::getSingleton('core/session')->addNotice($returnMessage) :  Mage::getSingleton('core/session')->addError($errorMessage);
                    $this->_redirect($receiptFail);


                    break;
                }


            default: {
                    Mage::getSingleton('core/session')->addError(sprintf(Mage::helper('payson')->__('Something went wrong with the payment. Please, try a different payment method')));
                    $this->_redirect('checkout');
                    break;
                }
        }
    }

    private function cancelOrder($message = '') {
        $order = $this->getOrder();
        if (!is_null($order = $this->getOrder())) {
            $order->cancel();
            if ($message != '') {
                $order->addStatusHistoryComment($message);
            }
        }
        $order->save();
        return $this;
    }

    private function restoreCart() {

        $quoteId = $this->getOrder()->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(true)->save();
    }

}
