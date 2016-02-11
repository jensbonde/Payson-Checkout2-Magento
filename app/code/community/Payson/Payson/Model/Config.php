<?php

class Payson_Payson_Model_Config {
    /*
     * Constants
     */

    //const PAYMENT_GUARANTEE = 'payment_guarantee';

    const PAYMENT_GUARANTEE = 'NO';
    const DIRECT_PAYMENT = 'direct_payment';
    const CREDIT_CARD_PAYMENT = 'credit_card_payment';
//    const INVOICE_PAYMENT = 'invoice_payment';

    /*
     * Private properties
     */

    /**
     * Default store id used in GetConfig()
     * 
     * @var	int
     */
    private $default_store_id;

    /**
     * Supported currency codes
     * 
     * @var	array
     */
    private $supported_currencies = array
        (
        'SEK', 'EUR', 'NOK', 'DKK'
    );

    /*
     * Public methods
     */

    /**
     * Constructor!
     * 
     * @return	void
     */
    public function __construct() {
        $this->SetDefaultStoreId(Mage::app()->getStore()->getId());
    }
    /**
     * Set default store id
     * 
     * @param	int		$store
     * @return	object			$this
     */
    public function SetDefaultStoreId($store) {

        $this->default_store_id = $store;

        return $this;
    }
    /**
     * Get default store id
     * 
     * @return	int
     */
    public function GetDefaultStoreId() {

        return $this->default_store_id;
    }
    /**
     * Whether $currency is supported
     * 
     * @param	string	$currency
     * @return	bool
     */
    public function IsCurrencySupported($currency) {
        return in_array(strtoupper($currency), $this->supported_currencies);
    }
    /**
     * Get configuration value
     * 
     * @param	mixed		$name
     * @param	int|null	$store		[optional]
     * @param	mixed		$default	[optional]
     * @param	string		$prefix		[optional]
     */
    public function GetConfig($name, $store = null, $default = null, $prefix = 'payment/payson_standard/') {
        if (!isset($store)) {
            $store = $this->GetDefaultStoreId();
        }

        $name = $prefix . $name;
        $value = Mage::getStoreConfig($name, $store);

        return (isset($value) ? $value : $default);
    }
    /**
     * @see GetConfig
     */
    public function Get($name, $store = null, $default = null, $prefix = 'payment/payson_standard/') {
        return $this->GetConfig($name, $store, $default, $prefix);
    }
    /**
     * Does this store support payment guarantee?
     * 
     * @param	int|null	$store	[optional]
     * @return	bool
     */
    public function CanPaymentGuarantee($store = null) {
        return (bool) $this->GetConfig(self::PAYMENT_GUARANTEE, $store, false);
    }
    /**
     * Is standard payment enabled?
     * 
     * @param	int|null	$store	[optional]
     * @return	bool
     */ 
    public function CanStandardPayment($store = null) {
        return $this->GetConfig('active', $store, false, 'payment/payson_standard/');
    }

    public function restoreCartOnCancel($store = null) {
        if (!$store)
            $store = Mage::app()->getStore()->getId();
        $configValue = $this->GetConfig("restore_on_cancel", $store);

        return $configValue == 1;
    }

    public function restoreCartOnError($store = null) {
        if (!$store)
            $store = Mage::app()->getStore()->getId();
        $configValue = $this->GetConfig("restore_on_error", $store);

        return $configValue == 1;
    }

}

