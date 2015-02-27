<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Model_Han extends Paynova_Paynovapayment_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paynovapayment_han';
    protected $_paymentMethod	= 'HAN';
    protected $_selectedPaymentId	= '103';	
}
