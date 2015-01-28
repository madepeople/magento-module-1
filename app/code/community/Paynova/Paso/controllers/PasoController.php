<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_PasoController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Retrieve Paynova helper
     *
     * @return Paynova_Paso_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paso');
    }

    
}
