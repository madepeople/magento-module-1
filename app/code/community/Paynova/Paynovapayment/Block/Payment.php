<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * Return Payment logo src
     *
     * @return string
     */
    public function getPaynovaLogoSrc()
    {
        $locale = Mage::getModel('paynovapayment/acc')->getLocale();
        $logoFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'paynovapayment' . DS . 'banner_120_' . $locale . '.gif', array('_type' => 'skin'));

        if (file_exists($logoFilename)) {
            return $this->getSkinUrl('images/paynovapayment/banner_120_'.$locale.'.gif');
        }

        return $this->getSkinUrl('images/paynovapayment/banner_120_int.gif');
    }
}
