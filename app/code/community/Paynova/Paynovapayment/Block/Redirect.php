<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Constructor. Set template.
     * Lk: original
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paynova/paynovapayment/redirect.phtml');
    }
}
