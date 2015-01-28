<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Constructor. Set template.
     * Lk: original
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paynova/paso/redirect.phtml');
    }
}
