<?php
namespace PaysonExpress{
    class PaysonMerchant {
        /** @var int $merchantId */
        private $merchantId;
        /** @var url $checkoutUri URI to the merchants checkout page.*/
        private $checkoutUri;
        /** @var url $confirmationUri URI to the merchants confirmation page. */
        private $confirmationUri;
        /** @var url $notificationUri Notification URI which receives CPR-status updates. */
        private $notificationUri;
        /** @var url $verificationUri Validation URI which is called to verify an order before it can be paid. */
        private $verificationUri = NULL;
        /** @var url $termsUri URI som leder till säljarens villkor. */
        private $termsUri;
        /** @var string $reference Merchants own reference of the checkout.*/
        private $reference = NULL;
        /** @var int $partnerId Partners unique identifier */
        private $partnerId = NULL;
        /** @var string $integrationInfo Information about the integration. */
        private $integrationInfo = NULL;


        public function __construct($merchantId, $confirmationUri, $notificationUri, $termsUri, $integrationInfo = null, $partnerId = null) {
            $this->merchantId = $merchantId;
            $this->checkoutUri = $confirmationUri;
            $this->confirmationUri = $confirmationUri;
            $this->notificationUri = $notificationUri;
            $this->termsUri = $termsUri;
            $this->partnerId = $partnerId;
            $this->integrationInfo = $integrationInfo;
        }

        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getMerchantObject(){
            return get_object_vars($this);      
        }
    }
}
