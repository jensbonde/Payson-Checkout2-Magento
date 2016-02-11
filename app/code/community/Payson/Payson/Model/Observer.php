<?php
//include('Cart/Update.php');
class Payson_Payson_Model_Observer {

    public function saveOrderAfterSubmit(Varien_Event_Observer $observer) {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getData('order');
        Mage::register('payson', $order, true);
        return $this;
    }

    /**
     * Set data for response of frontend saveOrder action
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Paypal_Model_Observer
     */
    public function setResponseAfterSaveOrder(Varien_Event_Observer $observer) {
        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::registry('payson');
        if (($order && $order->getId()) && ($order->getPayment()->getMethod() == 'payson_standard')) {



            /* @var $controller Mage_Core_Controller_Varien_Action */
            $controller = $observer->getEvent()->getData('controller_action');
            $result = Mage::helper('core')->jsonDecode($controller->getResponse()->getBody('default'), Zend_Json::TYPE_ARRAY);
            $api = Mage::helper('payson/api');
           
           
            $api->Pay($order);
            $result['update_section'] = array(
                'name' => 'review',
                'html' => $api->GetPayIframeHtml()
            );
            $result['redirect'] = false;
            $result['success'] = false;
            $controller->getResponse()->clearHeader('Location');
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            
            $cartContent = Mage::helper('checkout/cart');
 
		//Get all items from cart
		$items = $cartContent->getCart()->getItems();
		//Loop through cart and remove each item
		foreach ($items as $item) {
			$itemId = $item->getItemId();
			$cartContent->getCart()->removeItem($itemId);
		}
               //Save changes to cart
               $cartContent->getCart()->save();
               //Update Minicart
               //$Update = new Payson_Payson_Block_Cart_Update();
               //$Update->updateMinicart();
        }

        return $this;
    }

}
