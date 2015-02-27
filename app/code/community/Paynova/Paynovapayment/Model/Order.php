<?php
/*
 * This file is part of the Paynova Paynovapayment Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * LK: Abstract model for handling communication
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */
class Paynova_Paynovapayment_Model_Order extends Mage_Payment_Model_Method_Abstract
{
    public function loadByIncrementId($orderid)
    {
        //store config
        $sales_location_id = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_location_id');
        $sales_channel = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_channel');


        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);

        $paymentcode = $order->getPayment()->getMethodInstance()->getCode();



        $res['orderNumber'] = $orderid;


        $res['totalAmount'] = round($order->getGrandTotal(),2);


        $res['currencyCode'] = $order->getOrderCurrencyCode();

        $res['salesChannel'] = $sales_channel;
        $res['salesLocationId'] = $sales_location_id;
        $quoteid = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteid);
        //if invoice or installment pickup from additionalinfo

        if($paymentcode == 'paynovapayment_invoice' || $paymentcode == 'paynovapayment_installment'){


            $addinfo = $quote->getPayment()->getAdditionalInformation();

            $res['customer']['name']['firstName'] = $addinfo['CustomerName']['firstName'];
            $res['customer']['name']['lastName'] = $addinfo['CustomerName']['lastName'];
        }else {

            $res['customer']['name']['firstName'] = $order->getCustomerFirstname();
            $res['customer']['name']['lastName'] = $order->getCustomerLastname();
        }

        // government ID

        if ($quote) {
            if ($quote->getPayment()) {
                $payment = $quote->getPayment();
                if ($payment->getAdditionalInformation('governmentid')){
                    $res['customer']['governmentId'] = $payment->getAdditionalInformation('governmentid');
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




        // bill to

        if($paymentcode == 'paynovapayment_invoice' || $paymentcode == 'paynovapayment_installment'){
            $address = $addinfo['CustomerAddress'];

            $res['billTo']['name']['firstName'] =  $addinfo['CustomerName']['firstName'];
            $res['billTo']['name']['lastName'] =  $addinfo['CustomerName']['firstName'];
            $res['billTo']['address']['street1'] = $address['Street'];
            $res['billTo']['address']['city'] = $address['City'];
            $res['billTo']['address']['postalCode'] = $address['postalCode'];
            $res['billTo']['address']['countryCode'] = $address['countryCode'];

        }else{
            $billingid = $order->getBillingAddress()->getId();
            $address = Mage::getModel('sales/order_address')->load($billingid);
            $res['billTo']['name']['firstName'] = $address['firstname'];
            $res['billTo']['name']['lastName'] = $address['lastname'];
            $res['billTo']['address']['street1'] = $address['street'];
            $res['billTo']['address']['city'] = $address['city'];
            $res['billTo']['address']['postalCode'] = $address['postcode'];
            $res['billTo']['address']['countryCode'] = $address['country_id'];
        }



        // ship to
        if ($order->getShippingAddress()){

            $shippingId = $order->getShippingAddress()->getId();
            $address = "";
            if($paymentcode == 'paynovapayment_invoice' || $paymentcode == 'paynovapayment_installment'){
                $address = $addinfo['CustomerAddress'];
                $res['shipTo']['name']['firstName'] = $addinfo['CustomerName']['firstName'];
                $res['shipTo']['name']['lastName'] = $addinfo['CustomerName']['firstName'];
                $res['shipTo']['address']['street1'] = $address['Street'];
                $res['shipTo']['address']['city'] = $address['City'];
                $res['shipTo']['address']['postalCode'] = $address['postalCode'];
                $res['shipTo']['address']['countryCode'] = $address['countryCode'];
            }else{

                $address = Mage::getModel('sales/order_address')->load($shippingId);
                $res['shipTo']['name']['firstName'] = $address['firstname'];
                $res['shipTo']['name']['lastName'] = $address['lastname'];
                $res['shipTo']['address']['street1'] = $address['street'];
                $res['shipTo']['address']['city'] = $address['city'];
                $res['shipTo']['address']['postalCode'] = $address['postcode'];
                $res['shipTo']['address']['countryCode'] = $address['country_id'];
            }


        } else if(!$order->getShippingAddress() AND $paymentcode=="paynovapayment_invoice") {
            if($paymentcode == 'paynovapayment_invoice' || $paymentcode == 'paynovapayment_installment'){
                $address = $addinfo['CustomerAddress'];
                $res['shipTo']['name']['firstName'] = $addinfo['CustomerName']['firstName'];
                $res['shipTo']['name']['lastName'] = $addinfo['CustomerName']['firstName'];
                $res['shipTo']['address']['street1'] = $address['Street'];
                $res['shipTo']['address']['city'] = $address['City'];
                $res['shipTo']['address']['postalCode'] = $address['postalCode'];
                $res['shipTo']['address']['countryCode'] = $address['countryCode'];
            }else{

                $res['shipTo']['name']['firstName'] = $address['firstname'];
                $res['shipTo']['name']['lastName'] = $address['lastname'];
                $res['shipTo']['address']['street1'] = $address['street'];
                $res['shipTo']['address']['city'] = $address['city'];
                $res['shipTo']['address']['postalCode'] = $address['postcode'];
                $res['shipTo']['address']['countryCode'] = $address['country_id'];
            }
        }

        // items
        $order->getAllItems();
        $items = $quote->getAllItems();

        $itemcount = count($items);
        $data = array();
        $i=0;
        $linenumber=1;

        $unitMeasure = Mage::getStoreConfig('paynovapayment/advanced_settings/product_unit');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_name');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_sku');
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
            $res['lineItems'][$itemId]['articleNumber'] = substr($item->getSku(),0,50);
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = $item->getQty();
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

        if ($order->getShippingAmount() AND $order->getShippingAmount()>0) {
                $itemId++;
                $linenumber++;
                $res['lineItems'][$itemId]['id'] = $linenumber;
                $res['lineItems'][$itemId]['articleNumber'] =  substr($shippingsku,0,50);
                $res['lineItems'][$itemId]['name'] = $shippingname;
                $res['lineItems'][$itemId]['quantity'] = 1;
                $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] =  round($order->getShippingAmount(),2);
                $res['lineItems'][$itemId]['taxPercent'] = $this->getShippingTaxPercentFromQuote($quote);
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
                $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingInclTax(),2);
                $res['lineItems'][$itemId]['description'] =  $description;
                $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
            }

        $res['orderDescription'] =  Mage::helper('paynovapayment')->__('Order for store: ').Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);


        return $res;

    }
    public function createInitializePaymentCall($order, $orderId, $selectedPaymentId, $selectedModelCode)
    {
        //store config
        $salesLocationId = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_location_id');
        $salesChannel = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_channel');
        $sessionTimeout = Mage::getStoreConfig('paynovapayment/advanced_settings/session_timeout');
        $themeName = Mage::getStoreConfig('paynovapayment/advanced_settings/theme');
        $layoutName = Mage::getStoreConfig('paynovapayment/advanced_settings/layout');
        if (!$sessionTimeout)
        {
            $sessionTimeout = 600;
        }
        else if($sessionTimeout<180)
        {
            $sessionTimeout = 180;
        }

        $displayLineItems = Mage::getStoreConfig('paynovapayment/advanced_settings/display_line_items');
        $paymentChannelId = Mage::getStoreConfig('paynovapayment/advanced_settings/payment_channel_id');
        if (!$paymentChannelId)
        {
            $paymentChannelId = 1;
        }

        $interfaceId = 5;

        $redirectPendingUrl=Mage::getUrl('paynovapayment/processing/pending');
        $redirectOKUrl=Mage::getUrl('paynovapayment/processing/success');
        $redirectCancelUrl=Mage::getUrl('paynovapayment/processing/cancel');
        $redirectCallbackUrl=Mage::getUrl('paynovapayment/processing/callback');

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

        if(!empty($themeName)){$res['interFaceOptions']['themeName'] = $themeName;}

        if(!empty($layoutName)){$res['interFaceOptions']['layoutName'] = $layoutName;}
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

        $unitMeasure = Mage::getStoreConfig('paynovapayment/advanced_settings/product_unit');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_name');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_sku');
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
            $res['lineItems'][$itemId]['articleNumber'] = substr($item->getSku(),0,50);
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
        if ($order->getShippingAmount() AND $order->getShippingAmount()>0) {
            //load quote
            $quoteid = $order->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteid);

            $itemId++;
            $linenumber++;
            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] =  substr($shippingsku,0,50);
            $res['lineItems'][$itemId]['name'] = $shippingname;
            $res['lineItems'][$itemId]['quantity'] = 1;
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['unitAmountExcludingTax'] =  round($order->getShippingAmount(),2);
            $res['lineItems'][$itemId]['taxPercent'] = $this->getShippingTaxPercentFromQuote($quote);
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingInclTax(),2);
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
        }

        return $res;
    }
    public function createAuthorizePaymentCall($order, $orderId, $selectedPaymentId, $selectedModelCode, $paynova_product, $governmentid)
    {
        //store config
        $salesLocationId = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_location_id');
        $salesChannel = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_channel');

        $paymentChannelId = Mage::getStoreConfig('paynovapayment/advanced_settings/payment_channel_id');

        if(empty($governmentid)){
            Mage::throwException('No governmentid.');
        }

        if(empty($paynova_product)){
            Mage::throwException('No payment product.');
        }

        if (!$paymentChannelId)
        {
            $paymentChannelId = 1;
        }
        $iso2 = $order->getBillingAddress()->getCountry();
        $iso3 = Mage::getModel('directory/country')->load($iso2)->getIso3Code();;


        //$res['orderId'] = $orderId;
        $res['totalAmount'] = round($order->getGrandTotal(),2);
        $res['paymentChannelId'] = $paymentChannelId;

        $res['paymentMethodId'] = $selectedPaymentId;
        $res['PaymentMethodProductId'] = $paynova_product;
        //$res['sessionTimeout'] = $sessionTimeout;
        $res['AuthorizationType'] = 'InvoicePayment';

        //$sessionTimeout = Mage::getStoreConfig('paynovapayment/advanced_settings/session_timeout');
        //$themeName = Mage::getStoreConfig('paynovapayment/advanced_settings/theme');
        //$layoutName = Mage::getStoreConfig('paynovapayment/advanced_settings/layout');
        /*if (!$sessionTimeout)
        {
            $sessionTimeout = 600;
        }
        else if($sessionTimeout<180)
        {
            $sessionTimeout = 180;
        }

        $displayLineItems = Mage::getStoreConfig('paynovapayment/advanced_settings/display_line_items');*/

        /*
                $interfaceId = 5;
        */
        /*      $redirectPendingUrl=Mage::getUrl('paynovapayment/processing/pending');
              $redirectOKUrl=Mage::getUrl('paynovapayment/processing/success');
              $redirectCancelUrl=Mage::getUrl('paynovapayment/processing/cancel');
              $redirectCallbackUrl=Mage::getUrl('paynovapayment/processing/callback');

              //get iso3 language code*/

        //interface options

        /*$res['interFaceOptions']['interfaceId'] = $interfaceId;
        $res['interFaceOptions']['displayLineItems'] = $displayLineItems;

        if(!empty($themeName)){$res['interFaceOptions']['themeName'] = $themeName;}

        if(!empty($layoutName)){$res['interFaceOptions']['layoutName'] = $layoutName;}
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

        $unitMeasure = Mage::getStoreConfig('paynovapayment/advanced_settings/product_unit');
        if (empty($unitMeasure)){
            $unitMeasure = "pcs";
        }
        $shippingname = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_name');
        if (empty($shippingname)){
            $shippingname = "Shipping";
        }
        $shippingsku = Mage::getStoreConfig('paynovapayment/advanced_settings/shipping_sku');
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
*/
        return $res;
    }

    public function getShippingTaxPercentFromQuote($quote){
        
            
            $store = $quote->getStore();
            $taxCalculation = Mage::getModel('tax/calculation');
            $request = $taxCalculation->getRateRequest(null, null, null, $store);
            $taxRateId = Mage::getStoreConfig('tax/classes/shipping_tax_class', $store);
            
            //taxRateId is the same model id as product tax classes, so you can do this:
            return $taxCalculation->getRate($request->setProductClassId($taxRateId));
    }

}
