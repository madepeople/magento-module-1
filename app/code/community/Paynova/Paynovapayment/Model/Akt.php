<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */
class Paynova_Paynovapayment_Model_Akt extends Paynova_Paynovapayment_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paynovapayment_akt';
    protected $_paymentMethod	= 'AKT';
    protected $_selectedPaymentId	= '114';	
}
