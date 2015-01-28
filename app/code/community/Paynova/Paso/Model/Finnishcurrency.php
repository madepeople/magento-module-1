<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */


class Paynova_Paso_Model_Finnishcurrency
{
     /* To display the Finnish currency only in the admin configuration*/
     
	public function toOptionArray()
    {
        return array(
            array('value' => 'EUR', 'label'=>Mage::helper('adminhtml')->__('Euro')),
        );
    }
}
