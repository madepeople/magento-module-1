<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */
class Paynova_Paynovapayment_Model_Invoice extends Paynova_Paynovapayment_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paynovapayment_invoice';
    protected $_paymentMethod	= 'Resurs Bank (Invoice)';
    protected $_selectedPaymentId	= '311';
	protected $_formBlockType = 'paynovapayment/invoiceform';
}
