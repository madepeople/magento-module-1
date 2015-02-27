<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Model_Testlive
{
 	public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Live')),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Test')),
        );
    }

}
