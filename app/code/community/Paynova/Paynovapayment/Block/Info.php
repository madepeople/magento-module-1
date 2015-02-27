<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paynova/paynovapayment/info.phtml');
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
        $this->setTemplate('paynova/paynovapayment/pdf/info.phtml');
        return $this->toHtml();
    }
}
