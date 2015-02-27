<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */
class Paynova_Paynovapayment_Model_Sam extends Paynova_Paynovapayment_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paynovapayment_sam';
    protected $_paymentMethod	= 'SAM';
    protected $_selectedPaymentId	= '115';	
}
