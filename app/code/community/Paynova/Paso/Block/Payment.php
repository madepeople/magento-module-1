<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * Return Payment logo src
     *
     * @return string
     */
    public function getPaynovaLogoSrc()
    {
        $locale = Mage::getModel('paso/acc')->getLocale();
        $logoFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'paso' . DS . 'banner_120_' . $locale . '.gif', array('_type' => 'skin'));

        if (file_exists($logoFilename)) {
            return $this->getSkinUrl('images/paso/banner_120_'.$locale.'.gif');
        }

        return $this->getSkinUrl('images/paso/banner_120_int.gif');
    }
}
