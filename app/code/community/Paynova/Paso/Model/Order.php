<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * LK: Abstract model for handling communication
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */
class Paynova_Paso_Model_Order extends Mage_Payment_Model_Method_Abstract
{
    public function loadByIncrementId($orderid)
    {
        //store config
        $sales_location_id = Mage::getStoreConfig('paso/settings/sales_location_id');
        $sales_channel = Mage::getStoreConfig('paso/settings/sales_channel');


        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);

        $paymentcode = $order->getPayment()->getMethodInstance()->getCode();

        $res['orderNumber'] = $orderid;


        $res['totalAmount'] = round($order->getGrandTotal(),2);


        $res['currencyCode'] = $order->getOrderCurrencyCode();

        $res['salesChannel'] = $sales_channel;
        $res['salesLocationId'] = $sales_location_id;
        $res['customer']['name']['firstName'] = $order->getCustomerFirstname();
        $res['customer']['name']['lastName'] = $order->getCustomerLastname();


        // Goverment ID
        $quoteid = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteid);
        if ($quote) {
            if ($quote->getPayment()) {
                $payment = $quote->getPayment();
                if ($payment->getAdditionalInformation('govermentid')){
                    $res['customer']['governmentId'] = $payment->getAdditionalInformation('govermentid');
                }
            }
        }

        if($order->getCustomerId() === NULL)
        {
           // $res['customer']['customerId'] =  "No customer ID";
        }
        else
        {
            $res['customer']['customerId'] = $order->getCustomerId();
        }
        $res['customer']['emailAddress'] = $order->getCustomerEmail();

        $res['billTo']['name']['firstName'] = $order->getCustomerFirstname();
        $res['customer']['name']['lastName'] = $order->getCustomerLastname();


        // bill to
        $billingid = $order->getBillingAddress()->getId();
        $address = Mage::getModel('sales/order_address')->load($billingid);
        $res['billTo']['name']['firstName'] = $address['firstname'];
        $res['billTo']['name']['lastName'] = $address['lastname'];
        $res['billTo']['address']['street1'] = $address['street'];
        $res['billTo']['address']['city'] = $address['city'];
        $res['billTo']['address']['postalCode'] = $address['postcode'];
        $res['billTo']['address']['countryCode'] = $address['country_id'];

        // ship to
        if ($order->getShippingAddress()){
            $shippingId = $order->getShippingAddress()->getId();
            $address = "";
            $address = Mage::getModel('sales/order_address')->load($shippingId);
            $res['shipTo']['name']['firstName'] = $address['firstname'];
            $res['shipTo']['name']['lastName'] = $address['lastname'];
            $res['shipTo']['address']['street1'] = $address['street'];
            $res['shipTo']['address']['city'] = $address['city'];
            $res['shipTo']['address']['postalCode'] = $address['postcode'];
            $res['shipTo']['address']['countryCode'] = $address['country_id'];
        } else if(!$order->getShippingAddress() AND $paymentcode=="paso_invoice") {
            $res['shipTo']['name']['firstName'] = $address['firstname'];
            $res['shipTo']['name']['lastName'] = $address['lastname'];
            $res['shipTo']['address']['street1'] = $address['street'];
            $res['shipTo']['address']['city'] = $address['city'];
            $res['shipTo']['address']['postalCode'] = $address['postcode'];
            $res['shipTo']['address']['countryCode'] = $address['country_id'];
        }

        // items
        $order->getAllItems();
        $items = $order->getAllVisibleItems();

        $itemcount = count($items);
        $data = array();
        $i=0;
        $linenumber=1;

        $unitMeasure = Mage::getStoreConfig('paso/settings/unitMeasure');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paso/settings/shippingname');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paso/settings/shippingsku');
        if (empty($shippingsku)){
            $shippingsku = "Shipping";
        }

        foreach ($items as $itemId => $item)
        {
            $product = Mage::helper('catalog/product')->getProduct($item->getSku(), Mage::app()->getStore()->getId(), 'sku');
            $productUrl = Mage::getUrl($product->getUrlPath());
            $description =  $product->getShortDescription();
            if (empty($description)){
                $description = $item->getName();
            }

            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] = $item->getId();
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = $item->getQtyToInvoice();
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($item->getPrice(),2);
            $res['lineItems'][$itemId]['taxPercent'] = round($item->getTaxPercent(),2);
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($item->getTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($item->getRowTotalInclTax(),2);
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
            $i++;
            $linenumber++;
        }

        if ($order->getShippingAmount()>0) {
            $itemId++;
            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] =  $shippingsku;
            $res['lineItems'][$itemId]['name'] = $shippingname;
            $res['lineItems'][$itemId]['quantity'] = 1;
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = $order->getShippingAmount()-$order->getShippingTaxAmount();
            $res['lineItems'][$itemId]['taxPercent'] = 100 * $order->getShippingTaxAmount() / $order->getShippingAmount();
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingAmount(),2);
        }

        return $res;

    }
    public function createInitializePaymentCall($order, $orderId, $selectedPaymentId, $selectedModelCode)
    {
        //store config
        $salesLocationId = Mage::getStoreConfig('paso/settings/sales_location_id');
        $salesChannel = Mage::getStoreConfig('paso/settings/sales_channel');
        $sessionTimeout = Mage::getStoreConfig('paso/settings/session_timeout');
        $themeName = Mage::getStoreConfig('paso/settings/theme');
        $layoutName = Mage::getStoreConfig('paso/settings/layout');
        if (!$sessionTimeout)
        {
            $sessionTimeout = 600;
        }
        else if($sessionTimeout<180)
        {
            $sessionTimeout = 180;
        }

        $displayLineItems = Mage::getStoreConfig('paso/settings/display_line_items');
        $paymentChannelId = Mage::getStoreConfig('paso/settings/paymentChannelId');
        if (!$paymentChannelId)
        {
            $paymentChannelId = 1;
        }

        $interfaceId = 5;
        $layoutName = 'Paynova_FullPage_1';

        $redirectPendingUrl=Mage::getUrl('paso/processing/pending');
        $redirectOKUrl=Mage::getUrl('paso/processing/success');
        $redirectCancelUrl=Mage::getUrl('paso/processing/cancel');
        $redirectCallbackUrl=Mage::getUrl('paso/processing/callback');

        //get iso3 language code
        $iso2 = $order->getBillingAddress()->getCountry();
        $iso3 = Mage::getModel('directory/country')->load($iso2)->getIso3Code();;


        $res['orderId'] = $orderId;
        $res['totalAmount'] = round($order->getGrandTotal(),2);
        $res['paymentChannelId'] = $paymentChannelId;

        $res['paymentMethods'] = array('id' => $selectedPaymentId);
        $res['sessionTimeout'] = $sessionTimeout;
        //interface options

        $res['interFaceOptions']['interfaceId'] = $interfaceId;
        $res['interFaceOptions']['displayLineItems'] = $displayLineItems;

        if(!empty($themeName)){$res['interFaceOptions']['$themeName'] = $themeName;}

        if(!empty($layoutName)){$res['interFaceOptions']['$layoutName'] = $layoutName;}
        $res['interFaceOptions']['customerLanguageCode'] = $iso3; //
        $res['interFaceOptions']['urlRedirectSuccess'] = $redirectOKUrl;
        $res['interFaceOptions']['urlRedirectCancel'] = $redirectCancelUrl;
        $res['interFaceOptions']['urlRedirectPending'] = $redirectPendingUrl;
        $res['interFaceOptions']['urlCallback'] = $redirectCallbackUrl;
        //profilepaymentoptions (optional)

        if(!empty($variable)){$res['profilePaymentOptions']['profileId'] = $variable;}
        if(!empty($variable)){$res['profilePaymentOptions']['profileCard']['cardId'] = $variable;}
        if(!empty($variable)){$res['profilePaymentOptions']['profileCard']['cvc'] = $variable;}
        if(!empty($variable)){$res['profilePaymentOptions']['displaySaveProfileCardOption'] = $variable;}


        // items
        $order->getAllItems();
        $items = $order->getAllVisibleItems();

        $itemcount= count($items);
        $data = array();
        $i=1;

        $unitMeasure = Mage::getStoreConfig('paso/settings/unitMeasure');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paso/settings/shippingname');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paso/settings/shippingsku');
        if (empty($shippingsku)){
            $shippingsku = "Shipping";
        }

        foreach ($items as $itemId => $item)
        {
            $product = Mage::helper('catalog/product')->getProduct($item->getSku(), Mage::app()->getStore()->getId(), 'sku');
            $productUrl = Mage::getUrl($product->getUrlPath());
            $description =  $product->getShortDescription();
            if (empty($description)){
                $description = $item->getName();
            }
            $res['lineItems'][$itemId]['id'] = $i;
            $res['lineItems'][$itemId]['articleNumber'] = $item->getId();
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = $item->getQtyToInvoice();
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($item->getPrice(),2);
            $res['lineItems'][$itemId]['taxPercent'] = round($item->getTaxPercent(),2);
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($item->getTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($item->getRowTotalInclTax(),2);
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;

            $i++;
        }
        if ($order->getShippingAmount()>0) {
            $itemId++;
            $res['lineItems'][$itemId]['id'] = $i;
            $res['lineItems'][$itemId]['articleNumber'] = $shippingsku;
            $res['lineItems'][$itemId]['name'] = $shippingname;
            $res['lineItems'][$itemId]['quantity'] = 1;
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] = $order->getShippingAmount()-$order->getShippingTaxAmount();
            $res['lineItems'][$itemId]['taxPercent'] = 100 * $order->getShippingTaxAmount() / $order->getShippingAmount();
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingAmount(),2);
        }

        return $res;
    }



}
