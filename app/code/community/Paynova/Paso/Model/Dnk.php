<?php
/*
 * This file is part of the Paynova Laero Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Laero
 */

class Paynova_Paso_Model_Dnk extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_dnk';
    protected $_paymentMethod	= 'DNK';
    protected $_selectedPaymentId		= '121';
}
