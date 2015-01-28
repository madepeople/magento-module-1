<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */
class Paynova_Paso_Model_Allbanks extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_allbanks';
    protected $_paymentMethod	= 'All Banks';
     protected $_selectedPaymentId	= '199';
}
