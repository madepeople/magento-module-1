<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paynova/paso/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }
	
	/**
     * Returns infotext of payment method
     *
     * @return string
     */
	public function getInfotext()
	{
		$infotext = Mage::getStoreConfig('payment/'.$this->getMethodCode().'/infotext');		
		
		return $infotext;
	}
	
    /**
     * Build PDF content of info block
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('paynova/paso/pdf/info.phtml');
        return $this->toHtml();
    }
}
