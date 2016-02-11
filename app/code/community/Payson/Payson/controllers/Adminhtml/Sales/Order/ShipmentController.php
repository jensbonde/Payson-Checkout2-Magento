<?php

require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';

class Payson_Payson_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController {

    public function saveAction() {

        $order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));

        if (($order->getPayment()->getMethodInstance()->getCode() == "payson_standard")) {



            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);

            //Update Payson with the shipped status
            $entityId = $this->getRequest()->getParam('order_id');
            $tempOrder = Mage::getModel('sales/order')->load($entityId);
            $realOrderId = $tempOrder->increment_id;

            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "Select token from  `payson_order` where order_id = '$realOrderId'";

            $rows = $connection->fetchAll($sql);
            
            foreach ($rows as $inner) {
                $checkoutId = $inner['token'];
            }

            $callPaysonApiConf = Mage::helper('payson/api')->getPaysonApi($checkoutId, '', '');

            if ($callPaysonApiConf->getResponsObject()->status === 'readyToShip') {
                $callPaysonApiConf->getResponsObject()->status = 'shipped';
                $body = $callPaysonApiConf->getResponsObject();
               
                Mage::helper('payson/api')->getPaysonApi($checkoutId, 'PUT', $body);

                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                $transactionSave->save();
            } else {
                $checkoutId == '' ? $this->noId($realOrderId) : $this->wrongState($realOrderId, $callPaysonApiConf->getResponsObject()->status);                

                return $this;
            }
        }
        parent::saveAction();
    }

    private function wrongstate($tempOrder, $currentStatus) {
        $errorMessage = Mage::helper('payson')->__('Unable to ship order: ' . $tempOrder . '. Due to wrong current status, it should be readyToShip but it´s current status is: ' . $currentStatus);
        Mage::getSingleton('core/session')->addError($errorMessage);
        $this->_redirectReferer();
    }

    private function noId($tempOrder) {
        $errorMessage = Mage::helper('payson')->__('No PaysonId was found, the order_id was ' . $tempOrder . '. No shippment was sent');
        Mage::getSingleton('core/session')->addError($errorMessage);
        $this->_redirectReferer();
    }

}
