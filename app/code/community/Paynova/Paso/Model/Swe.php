<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */
class Paynova_Paso_Model_Swe extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_swe';
    protected $_paymentMethod	= 'SWE';
    protected $_selectedPaymentId	= '102';	
}
