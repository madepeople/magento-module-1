<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */
class Paynova_Paso_Model_Invoice extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_invoice';
    protected $_paymentMethod	= 'Resurs Bank (Invoice)';
    protected $_selectedPaymentId	= '311';
}
