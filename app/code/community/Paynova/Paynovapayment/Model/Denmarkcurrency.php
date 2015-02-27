<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Model_Denmarkcurrency
{
    /* To display the Denmark currency only in the admin configuration*/
	public function toOptionArray()
    {
        return array(
            array('value' => 'DKK', 'label'=>Mage::helper('adminhtml')->__('Denmark')),
        );
    }
}
