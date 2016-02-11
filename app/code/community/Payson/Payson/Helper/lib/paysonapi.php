<?php

namespace PaysonExpress {
    require_once "paysonapiexception.php";
    require_once "paysonapierror.php";
    require_once "paydata.php";
    require_once "paysonmerchant.php";
    require_once "customer.php";
    require_once "gui.php";
}

namespace PaysonExpress {

    class CurrencyCode {

        const SEK = "SEK";
        const EUR = "EUR";

        public static function ConstantToString($value) {
            switch (strtoupper($value)) {
                case "SEK":
                    return "SEK";
                case "EUR":
                    return "EUR";
                default:
                    throw new PaysonApiException("Invalid currency code: $value");
            }
        }

    }

}

namespace PaysonExpress {

    class PaysonApi {

        private $paysonMerchant = NULL;
        private $payData = NULL;
        private $customer = NULL;
        private $allOrderData = array();
        private $gui = NULL;
        private $useTestEnvironment = NULL;
        private $checkoutId = NULL;
        private $paysonRespons = NULL;
        public $paysonResponsErrors = array();
        private $merchantId;
        private $apiKey;
        private $url = null;
        private $response ='';
        public function __construct($merchantId, $apiKey, $useTestEnvironment = false) {
            $this->useTestEnvironment = $useTestEnvironment;
            $this->apiKey = $apiKey;
            $this->merchantId = $merchantId;
        }

        public function setPaysonMerchant(PaysonMerchant $paysonMerchant) {
            if (!($paysonMerchant instanceof PaysonMerchant)) {
                throw new PaysonApiException("Parameter must be an object of type PaysonMerchant");
            }
            $this->allOrderData['merchant'] = $paysonMerchant->getMerchantObject();
        }

        public function getPaysonMerchant() {
            return $this->paysonMerchant;
        }

        public function setPayData(PayData $payData) {
            if (!($payData instanceof PayData)) {
                throw new PaysonApiException("Parameter must be an object of type payData");
            }
            $this->allOrderData['order'] = $payData->getPaydata();
        }

        public function getPayData() {
            return $this->payData;
        }

        public function setCustomer(Customer $customer) {
            if (!($customer instanceof Customer)) {
                throw new PaysonApiException("Parameter must be an object of type Customer");
            }
            $this->allOrderData['customer'] = $customer->getCustomerObject();
        }

        public function getCustomer() {
            return $this->customer;
        }

        public function setGui(Gui $gui) {
            if (!($gui instanceof Gui)) {
                throw new PaysonApiException("Parameter must be an object of type Gui");
            }
            $this->allOrderData['gui'] = $gui->getGuiObject();
        }

        public function getGui() {
            return $this->gui;
        }

        /**
         * Sets the API mode
         * 
         * @param bool $isTestMode Indicates if we are using the test environment or not
         */
        public function setMode($isTestMode) {
            $this->useTestEnvironment = $isTestMode;
        }

        public function doRequest($server, $curlAction = 'GET', $checkoutId = NULL, $body = null) {
;
            if ($checkoutId != NULL && $this->checkoutId == NULL) {
                $this->checkoutId = $checkoutId;
            }

            if (!function_exists('curl_exec')) {
                $this->paysonResponsErrors[] = new PaysonApiError('Curl info: ', 'Curl not installed', NULL);
            }

            $this->doCurlRequest(strtoupper($curlAction), $server, $body);
        }
        private function saveResponse($response){
            $this->response = $response;
        }
        public function getRespons(){
            return $this->response;
        }
        private function doCurlRequest($curlAction, $server, $body = null) {
            $newBody = $body == null ? $this->allOrderData : $body;

            $ch = curl_init($server . $this->checkoutId);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->authorizationHeader($curlAction));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $curlAction);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlAction == "GET" ? NULL :  json_encode($newBody));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, $curlAction == "GET" ? FALSE : TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $result = curl_exec($ch);

            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response_code >= 200 && $response_code <= 203) {
                if ($curlAction == "POST") {
                    $this->extractCheckoutId($result);
                } else {
                    $this->setResponsObject($result);
                }
            } elseif ($result == false) {
                $this->paysonResponsErrors[] = new PaysonApiError('Curl error: ', curl_error($ch), NULL);
            } else {
                $this->parseErrors(explode("\r\n\r\n", $result), $response_code);
            }
            $this->saveResponse($result);
            curl_close($ch);
        }

        private function authorizationHeader($curlAction) {
            $curlAction == "GET" ? '' : json_encode($this->allOrderData);
            $authHashPayson = base64_encode($this->merchantId . ':' .$this->apiKey);
            $header = array();
            $header[] = 'Content-Type: application/json';
            $header[] = 'Authorization: Basic ' . $authHashPayson;

            return $header;
        }
        public function updateStatus($body) {
            
        }

        public function setResponsObject($result) {
            $this->paysonRespons = json_decode($result);
        }

        public function getResponsObject() {
            return $this->paysonRespons;
        }

        private function extractCheckoutId($result) {
            $checkoutId = null;
            if (preg_match('#Location: (.*)#', $result, $res)) {
                $checkoutId = trim($res[1]); 
            }
            
            $checkoutId = explode('/', $checkoutId);
            $checkoutId = $checkoutId[count($checkoutId) - 1];
            $this->setCheckoutId($checkoutId);
        }
        public function setCheckoutId($checkout) {
            $this->checkoutId = $checkout;
        }

        public function getCheckoutId() {
            return $this->checkoutId;
        }

         private function parseErrors($responseError, $response_code) {
            $apiResponseErrors = json_decode($responseError[count($responseError) - 1]);
            //Error 5xx Indicate cases in which the server is aware that it has encountered an error or is otherwise incapable of performing the request (Server Error).
            if ($response_code >= 500 && $response_code <= 599)
                $this->paysonResponsErrors[] = new PaysonApiError('HTTP status codes: ' . $response_code, $responseError[count($responseError) - 1], NULL);
            else
                $this->paysonResponsErrors[] = new PaysonApiError('HTTP status codes: ' . $response_code, $apiResponseErrors->message, NULL);
            //The 4xx class of status code is intended for cases in which the client seems to have erred (Client Error)
            if (isset($response_code) && $response_code == 400) {
                foreach ($apiResponseErrors->errors as $errors) {
                    $this->paysonResponsErrors[] = new PaysonApiError('HTTP status codes: ' . $response_code, $errors->message, $errors->field);
                }
            }
        }

        public function getpaysonResponsErrors() {
            return $this->paysonResponsErrors;
        }

        public function setStatus($status) {
            $this->allOrderData['status'] = $status;
        }

    }

}    