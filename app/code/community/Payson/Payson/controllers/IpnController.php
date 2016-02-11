<?php

class Payson_Payson_IpnController extends Mage_Core_Controller_Front_Action {
    
    public function notifyAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        
        Mage::log($request->checkout);
        
        if (!$request->isGet()) {
            $response->setHttpResponseCode(503)->setBody('Not vaild');

            return;
        }
        try {
            Mage::helper('payson/api')->Validate($request->checkout);
        } catch (Exception $e) {
            $response->setHttpResponseCode(503)->setBody($e->getMessage());
            Mage::log($e->getMessage());
            return;
        }
    }

}

