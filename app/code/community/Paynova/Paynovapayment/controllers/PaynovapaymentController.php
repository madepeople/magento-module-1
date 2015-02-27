<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_PaynovapaymentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Retrieve Paynova helper
     *
     * @return Paynova_Paynovapayment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paynovapayment');
    }

    
}
