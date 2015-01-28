<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Aero
 */
class Paynova_Paso_Model_Poh extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_poh';
    protected $_paymentMethod	= 'POH';
    protected $_selectedPaymentId	= '117';	
}
