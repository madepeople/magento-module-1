<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Model_Swedishcurrency
{
    
	public function toOptionArray()
    {
        return array(
            array('value' => 'SEK', 'label'=>Mage::helper('adminhtml')->__('Swedish Kronor')),
        );
    }
}
