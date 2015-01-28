<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */
class Paynova_Paso_Model_Did extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_did';
    protected $_paymentMethod	= 'DID';
    protected $_selectedPaymentId		= '111';
}
