<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * LK: Feeds form.phtml frontend
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Available locales for content URL generation
     * LK: original function
     * @var array
     */
    protected $_supportedInfoLocales = array('de');

    /**
     * Default locale for content URL generation
     * LK: Original function
     * @var string
     */
    protected $_defaultInfoLocale = 'en';

    /**
     * Constructor. Set template
     * LK: throws payment method
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paynova/paso/form.phtml');
    }

    /**
     * Return payment logo image src
     * LK: original function
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
    	if ($payment == 'paso_obt') {
            $payment .= '_'.$this->getInfoLocale();
        }
    
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'paso' . DS . $payment, array('_type' => 'skin'));

        if (file_exists($imageFilename . '.png')) {
            return $this->getSkinUrl('images/paso/' . $payment . '.png');
        } else if (file_exists($imageFilename . '.gif')) {
            return $this->getSkinUrl('images/paso/' . $payment . '.gif');
        }

        return false;
    }

    /**
     * Return supported locale for information text
     * LK original function,
     *
     * @return string
     */
    public function getInfoLocale()
    {
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), 0 ,2);
        if (!in_array($locale, $this->_supportedInfoLocales)) {
            $locale = $this->_defaultInfoLocale;
        }
        return $locale;
    }

    /**
     * Return info URL for eWallet payment
     *
     * LK: Original function
     * @return string
     */
    public function getWltInfoUrl()
    {
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), 0 ,2);
        return 'http://www.aero.com/app/?l=' . strtoupper($locale);
    }
   	
}
