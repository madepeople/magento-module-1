<?php
class Paynova_Paynovapayment_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

        $this->setElement($element);

        $timestmp = time();
        $timestmp = substr($timestmp, 0, -4);
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'paynovapayment/index/getLogFile?tid='.$timestmp;
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Download log')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}