<?php

class Payson_Payson_Model_System_Config_Source_Iframe {
    /**
     * Options getter
     *
     * @return array
     * 
     */

    public function toOptionArray() {

        $this->_config = Mage::getModel('payson/config');
        $iframe = array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('300px')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('400px')),
            array('value' => 2, 'label' => Mage::helper('adminhtml')->__('500px')),
            array('value' => 3, 'label' => Mage::helper('adminhtml')->__('600px')),
            array('value' => 4, 'label' => Mage::helper('adminhtml')->__('700px')),
            array('value' => 5, 'label' => Mage::helper('adminhtml')->__('800px')),
            array('value' => 6, 'label' => Mage::helper('adminhtml')->__('900px')),
            array('value' => 7, 'label' => Mage::helper('adminhtml')->__('1000px'))
        );
        return $iframe;
    }

}
