<?php

class Paynova_Paynovapayment_Block_Backend_Config_Buttons extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setTemplate('paynova/paynovapayment/buttons.phtml');
        return $this->toHtml();
    }
}
