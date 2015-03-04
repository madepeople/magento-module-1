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
            if(!empty($addinfo)){
                $this->setAddinfoAddresstoOrder($addinfo, $order);
            }

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
                if(!empty($addinfo)){
                    $this->setAddinfoAddresstoOrder($addinfo, $order, 'shipping');
                }

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
            $itemtype = $item->getProductType();


            $lineqty = intval($item->getQty());
            // if product has parent - get parent qty


            if ($item->getParentItemID() AND $item->getParentItemID()>0){
                $parentQuoteItem = Mage::getModel("sales/quote_item")->load($item->getParentItemID());
                $parentqty = intval($parentQuoteItem->getQty());
                $lineqty = $lineqty * $parentqty;
            }


            $lineprice = $item->getPrice();
            $linetax = $item->getTaxPercent();
            $unitAmountExcludingTax =  $item->getPrice();
            $linetaxamount = ($lineqty*$lineprice)*($linetax/100);
            $linetotalamount =  $lineqty*$unitAmountExcludingTax+$linetaxamount;

            // If item has discount
            if ($item->getDiscountAmount() AND $item->getDiscountAmount()>0 )
            {
                $linediscountamount = $item->getDiscountAmount();
                $itemdiscount = $linediscountamount/$lineqty;
                $unitAmountExcludingTax = $lineprice-$itemdiscount;
                $linetaxamount = ($lineqty*$unitAmountExcludingTax)*($linetax/100);
                $total1 = $lineqty*$unitAmountExcludingTax;
                $linetotalamount = $total1+$linetaxamount;
                $linetax1 = $lineqty*$unitAmountExcludingTax;
                $linetax2 = $linetax/100;
                $linetaxamount = $linetax1*$linetax2;
            }




            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] = substr($item->getSku(),0,50);
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = $lineqty;
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;


            if ($itemtype =="bundle") {
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] =0;
                $res['lineItems'][$itemId]['taxPercent'] = 0;
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = 0;
                $res['lineItems'][$itemId]['totalLineAmount'] =  0;
            } else {
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($unitAmountExcludingTax,2);
                $res['lineItems'][$itemId]['taxPercent'] =  round($linetax,2);
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($linetaxamount,2);
                $res['lineItems'][$itemId]['totalLineAmount'] = round($linetotalamount,2);
            }

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
                $res['lineItems'][$itemId]['taxPercent'] = Mage::helper('paynovapayment')->getShippingTaxPercentFromQuote($quote);
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
                $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingInclTax(),2);
                $res['lineItems'][$itemId]['description'] =  $description;
                $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
            }

        $res['orderDescription'] =  Mage::helper('paynovapayment')->__('Order for store: ').Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        return $res;

    }
    public function setAddinfoAddresstoOrder($addinfo, $order, $type = 'order'){

        if($type == 'shipping') {
            $orderAddress = $order->getShippingAddress();
        }else{
            $orderAddress = $order->getBillingAddress();
        }

        $address = $addinfo['CustomerAddress'];

        if($orderAddress->getFirstname() != $addinfo['CustomerName']['firstName']){
            $orderAddress->setFirstname($addinfo['CustomerName']['firstName']);
        };

        if($orderAddress->getLastname() != $addinfo['CustomerName']['lastName']){
            $orderAddress->setLastname($addinfo['CustomerName']['lastName']);
        };

        if($orderAddress->getStreet() != $address['Street']){
            $orderAddress->setStreet($address['Street']);
        };

        if($orderAddress->getPostcode() != $address['postalCode']){
            $orderAddress->setPostcode($address['postalCode']);
        };

        if($orderAddress->getCity() != $address['City']){
            $orderAddress->setCity($address['City']);
        };

        if($orderAddress->getCity() != $address['City']){
            $orderAddress->setCity($address['City']);
        };


        $orderAddress->save();
        return ;
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
        $items = $order->getAllItems();


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

        $linenumber=1;
        foreach ($items as $itemId => $item)
        {
            $product = Mage::helper('catalog/product')->getProduct($item->getSku(), Mage::app()->getStore()->getId(), 'sku');
            $productUrl = Mage::getUrl($product->getUrlPath());
            $description =  $product->getShortDescription();
            if (empty($description)){
                $description = $item->getName();
            }
            $itemtype = $item->getProductType();


            $lineqty = intval($item->getQtyOrdered());





            $lineprice = $item->getPrice();
            $linetax = $item->getTaxPercent();
            $unitAmountExcludingTax =  $item->getPrice();
            $linetaxamount = ($lineqty*$lineprice)*($linetax/100);
            $linetotalamount =  $lineqty*$unitAmountExcludingTax+$linetaxamount;

            // If item has discount
            if ($item->getDiscountAmount() AND $item->getDiscountAmount()>0 )
            {
                $linediscountamount = $item->getDiscountAmount();
                $itemdiscount = $linediscountamount/$lineqty;
                $unitAmountExcludingTax = $lineprice-$itemdiscount;
                $linetaxamount = ($lineqty*$unitAmountExcludingTax)*($linetax/100);
                $total1 = $lineqty*$unitAmountExcludingTax;
                $linetotalamount = $total1+$linetaxamount;
                $linetax1 = $lineqty*$unitAmountExcludingTax;
                $linetax2 = $linetax/100;
                $linetaxamount = $linetax1*$linetax2;
            }



            $res['lineItems'][$itemId]['id'] = $linenumber;
            $res['lineItems'][$itemId]['articleNumber'] = substr($item->getSku(),0,50);
            $res['lineItems'][$itemId]['name'] = $item->getName();
            $res['lineItems'][$itemId]['quantity'] = $lineqty;
            $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;


            if ($itemtype =="bundle") {
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] =0;
                $res['lineItems'][$itemId]['taxPercent'] = 0;
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = 0;
                $res['lineItems'][$itemId]['totalLineAmount'] =  0;
            } else {
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($unitAmountExcludingTax,2);
                $res['lineItems'][$itemId]['taxPercent'] =  round($linetax,2);
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($linetaxamount,2);
                $res['lineItems'][$itemId]['totalLineAmount'] = round($linetotalamount,2);
            }

            $i++;
            $linenumber++;
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
            $res['lineItems'][$itemId]['taxPercent'] = Mage::helper('paynovapayment')->getShippingTaxPercentFromQuote($quote);
            $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
            $res['lineItems'][$itemId]['totalLineAmount'] =  round($order->getShippingInclTax(),2);
            $res['lineItems'][$itemId]['description'] =  $description;
            $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
        }


        return $res;
    }
    public function createAuthorizePaymentCall($order, $orderId, $selectedPaymentId, $selectedModelCode, $paynova_product = null, $governmentid = null)
    {
        //store config
        $salesLocationId = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_location_id');
        $salesChannel = Mage::getStoreConfig('paynovapayment/advanced_settings/sales_channel');

        $paymentChannelId = Mage::getStoreConfig('paynovapayment/advanced_settings/payment_channel_id');

        if(empty($governmentid)){
            Mage::throwException(Mage::helper('paynovapayment')->__('No governmentid.'));
        }

        if(empty($paynova_product)){
            Mage::throwException(Mage::helper('paynovapayment')->__('No payment product.'));
        }

        if (!$paymentChannelId)
        {
            $paymentChannelId = 1;
        }
        $iso2 = $order->getBillingAddress()->getCountry();
        $iso3 = Mage::getModel('directory/country')->load($iso2)->getIso3Code();;

        $res['totalAmount'] = round($order->getGrandTotal(),2);
        $res['paymentChannelId'] = $paymentChannelId;

        $res['paymentMethodId'] = $selectedPaymentId;
        $res['PaymentMethodProductId'] = $paynova_product;

        $res['AuthorizationType'] = 'InvoicePayment';


        return $res;
    }


}
