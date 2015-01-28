<?php

class Paynova_Paso_Block_Backend_Config_Buttons extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setTemplate('paynova/paso/buttons.phtml');
        return $this->toHtml();
    }
}
