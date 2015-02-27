<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * LK: Feeds form.phtml frontend
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Block_Invoiceform extends Mage_Payment_Block_Form
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
        $this->setTemplate('paynova/paynovapayment/invoiceform.phtml');
    }

    /**
     * Return payment logo image src
     * LK: original function
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
    	if ($payment == 'paynovapayment_obt') {
            $payment .= '_'.$this->getInfoLocale();
        }
    
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'paynovapayment' . DS . $payment, array('_type' => 'skin'));

        if (file_exists($imageFilename . '.png')) {
            return $this->getSkinUrl('images/paynovapayment/' . $payment . '.png');
        } else if (file_exists($imageFilename . '.gif')) {
            return $this->getSkinUrl('images/paynovapayment/' . $payment . '.gif');
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
