<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Model_Swedishcurrency
{
    
	public function toOptionArray()
    {
        return array(
            array('value' => 'SEK', 'label'=>Mage::helper('adminhtml')->__('Swedish Kronor')),
        );
    }
}
