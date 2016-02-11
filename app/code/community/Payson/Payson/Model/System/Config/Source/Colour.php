<?php

class Payson_Payson_Model_System_Config_Source_Colour {
     /**
     * Options getter
     *
     * @return array
     * 
     */

    public function toOptionArray() {

        $this->_config = Mage::getModel('payson/config');
        $colourTheme = array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('White')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Grey')),
            array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Blue'))       
                    );
        return $colourTheme;
    }

}