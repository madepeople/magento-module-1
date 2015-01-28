<?php
/*
 * This file is part of the Paynova Laero Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 * *
 * @category    Paynova
 * @package     Paynova_Laero
 */

class Paynova_Paso_Block_Jsinit extends Mage_Adminhtml_Block_Template
{
    /**
     * Include JS in head if section is paynova
     */
    protected function _prepareLayout()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'paso') {
            $this->getLayout()
                ->getBlock('head')
                ->addJs('mage/adminhtml/paso.js');
        }
        parent::_prepareLayout();
    }

    /**
     * Print init JS script into body
     * @return string
     */
    protected function _toHtml()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'paso') {
            return parent::_toHtml();
        } else {
            return '';
        }
    }
}
