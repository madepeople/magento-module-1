<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Model_Gir extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_gir';
    protected $_paymentMethod	= 'GIR';
    protected $_selectedPaymentId		= '118';
}
