<?php 
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_Model_Observer
{
    /*  Observer method called to send the refund request to Paynova */
    public function orderRefund(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent(); 					//Event observer
        $order = $event->getCreditmemo()->getOrder();
        $orderId = $order->getId();
        $coreOrderObj = Mage::getModel('sales/order')->load($orderId); //Order Object
        $corePaymentObj = $coreOrderObj->getPayment()->getMethodInstance();	// get Payment Object
        $selectedModelCode = $corePaymentObj->getCode(); 				// get payment code for selected method.

        // Check if the order belongs to Paynova
        if (Mage::helper('paynovapayment')->isMethodPaynova($selectedModelCode)) {
            $selectedPaymentId = $corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment.
            $IncrementOrderId = $coreOrderObj->getIncrementId();

            $filterSelectedModelCode=str_replace('_','/',$selectedModelCode);
           $abstractModel = Mage::getModel($filterSelectedModelCode); // create a object for acc class.


            $amount = round($order->getTotalRefunded(),2);

            $res['orderId'] = $orderId;
            $res['transactionId'] = $order->getPayment()->getLastTransId();

            if (empty($res['transactionId'])) {
                Mage::helper('paynovapayment')->log("Error. Missing transaction ID from order ".$orderId." cannot do refund.");
                Mage::throwException('Error. Missing transaction ID.');
            }


            $res['totalAmount'] = $amount;

            // items

            $order->getAllItems();
            $items = $order->getAllVisibleItems();

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
                $refundedqty = round($item->getQtyRefunded());
                if ($refundedqty<>0) {
                    $product = Mage::helper('catalog/product')->getProduct($item->getSku(), Mage::app()->getStore()->getId(), 'sku');
                    $productUrl = Mage::getUrl($product->getUrlPath());
                    $description =  $product->getShortDescription();
                    if (empty($description)){
                        $description = $item->getName();
                    }
                    $res['lineItems'][$itemId]['id'] = $linenumber;
                    $res['lineItems'][$itemId]['articleNumber'] = $item->getSku();
                    $res['lineItems'][$itemId]['name'] = $item->getName();
                    $res['lineItems'][$itemId]['quantity'] = $refundedqty;
                    $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
                    $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($item->getPrice(),2);
                    $res['lineItems'][$itemId]['taxPercent'] = round($item->getTaxPercent(),2);
                    $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($item->getTaxRefunded(),2);
                    $res['lineItems'][$itemId]['totalLineAmount'] =  ($refundedqty * (round($item->getPrice(),2))) + (round($item->getTaxRefunded(),2));
                    $res['lineItems'][$itemId]['description'] =  $description;
                    $res['lineItems'][$itemId]['productUrl'] =  $productUrl;
                    $i++;
                    $linenumber++;
                }
                $refundedqty = NULL;
            }

            if ($order->getShippingRefunded()<>0) {
                $itemId++;
                $linenumber++;
                $res['lineItems'][$itemId]['id'] = $linenumber;
                $res['lineItems'][$itemId]['articleNumber'] =  $shippingsku;
                $res['lineItems'][$itemId]['name'] = $shippingname;
                $res['lineItems'][$itemId]['quantity'] = 1;
                $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] = round($order->getShippingRefunded(),2);
                $res['lineItems'][$itemId]['taxPercent'] = 100 * $order->getShippingTaxAmount() / $order->getShippingAmount();
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = round($order->getShippingTaxAmount(),2);
                $res['lineItems'][$itemId]['totalLineAmount'] =  $order->getShippingRefunded()+$order->getShippingTaxAmount();
                $res['lineItems'][$itemId]['productUrl'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            }


            /* TODO: Test adjustment refund. Does it require tax? */
            if ($order->getAdjustmentNegative() or $order->getAdjustmentPositive()) {
                $itemId++;
                $linenumber++;
                $adjustment_tot = round( ($order->getAdjustmentPositive()-$order->getAdjustmentNegative() ),2);
                $res['lineItems'][$itemId]['id'] = $linenumber;
                $res['lineItems'][$itemId]['articleNumber'] =  "Adjustment";
                $res['lineItems'][$itemId]['name'] = "Adjustment";
                $res['lineItems'][$itemId]['quantity'] = 1;
                $res['lineItems'][$itemId]['unitMeasure'] = $unitMeasure;
                $res['lineItems'][$itemId]['unitAmountExcludingTax'] = $adjustment_tot;
                $res['lineItems'][$itemId]['taxPercent'] = 0;
                $res['lineItems'][$itemId]['totalLineTaxAmount'] = 0;
                $res['lineItems'][$itemId]['totalLineAmount'] =  $adjustment_tot;
            }

            $output = $abstractModel->setCurlCall($res, '/transactions/'.$res['transactionId'].'/refund/'.$res['totalAmount']);

            if(!$output){
                Mage::getSingleton('core/session')->addError('Something went wrong. Refund request not sent to Paynova.');
                $order->addStatusToHistory($order->getStatus(), 'Something went wrong. Refund request not sent to Paynova.', false);
                $order->save();
            }


            Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/sales_order/view', array('order_id' =>$orderId)));

        }

    }


    /* Observer method called to send the annull request to Paynova */
    public function send_paynova_cancel(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $method = $event->getPayment()->getMethodInstance()->getCode();

        if (Mage::helper('paynovapayment')->isMethodPaynova($method)) {


            $entid = $event->getPayment()->getEntityId();

            // Load order via entity ID
            $order = Mage::getModel('sales/order')->load($entid);
            $totalamount =  round($order->getGrandTotal(),2);

            $transid =  $order->getPayment()->getLastTransId();

            if (empty($transid)) {
                Mage::getSingleton('core/session')->addError('Missing transaction ID. Can´t send anull order request to Paynova');
                $order->addStatusToHistory($order->getStatus(), 'Missing transaction ID. Can´t send anull order request to Paynova.', false);
                $order->save();
                $logmsg = "Missing transaction ID from order #".$order->getIncrementId().". Can´t send anull order request to Paynova.";
                Mage::Helper('paynovapayment')->log($logmsg);
            } else {

                $corePaymentObj = $order->getPayment()->getMethodInstance();
                $selectedPaymentId=$corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment.
                $selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
                $filterSelectedModelCode=str_replace('_','/',$selectedModelCode);
                $abstractModel=Mage::getModel($filterSelectedModelCode); // create a object for acc class.


                $res['transactionId'] = $transid;
                $res['totalAmount'] = $totalamount;

                $output=$abstractModel->setCurlCall($res, '/transactions/'.$res['transactionId'].'/annul/'.$res['totalAmount']); 		// post the initialize payment Json through CURL.
                Mage::Helper('paynovapayment')->log($output);

	           	if(!$output){
                    Mage::getSingleton('core/session')->addError('Something went wrong. Anull request not sent to Paynova.');
                    $order->addStatusToHistory($order->getStatus(), 'Something went wrong. Anull request not sent to Paynova.', false);
                    $order->save();
	           	}
            }
        }

    }

    /* Observer method called to remove the government ID from the quote */
    public function removeGovIdFromQuote($observer)
    {
        /*
        $quote = $observer->getEvent()->getQuote();
        if ($quote) {
            if (!$quote->getIsActive()) {
                $payment = $quote->getPayment();
                if ($payment) {
                    if (Mage::helper('paynovapayment')->isMethodPaynova($payment->getMethod())) {
                        if ($payment->getAdditionalInformation('governmentid')) {
                            $payment->setAdditionalInformation('governmentid',NULL);
                            $payment->save();
                        }
                    }
                }
            }
        }
        */
    }


    /* Observer method called to set government ID, paymentmethod ID and Checkoutpath to quote after order save */
    public function afterOrderSaveMethod(Varien_Event_Observer $observer){

        $checksess = Mage::getSingleton('checkout/session');
        $quote = $checksess ->getQuote();
        $payment = $quote->getPayment();
        $paymentMethod = $payment->getMethodInstance()->getCode();

        $postarray = Mage::app()->getRequest()->getPost();
        if (array_key_exists('payment', $postarray)) {
            if (array_key_exists('paynova_paynovapayment_governmentid', $postarray['payment']) || array_key_exists('paynova_paynovapayment_installment_governmentid', $postarray['payment'])) {
                if($paymentMethod == 'paynovapayment_installment'){
                    $postgovid = $postarray['payment']['paynova_paynovapayment_installment_governmentid'];
                }else{
                    $postgovid = $postarray['payment']['paynova_paynovapayment_governmentid'];
                }

                $payment->setAdditionalInformation('governmentid',$postgovid);
                $payment->save();

            }

            if (array_key_exists('paynova_paynovapayment_paymentmethod', $postarray['payment']) || array_key_exists('paynova_paynovapayment_installment_paymentmethod', $postarray['payment'])) {
                if($paymentMethod == 'paynovapayment_installment'){
                    $postpaymentid = $postarray['payment']['paynova_paynovapayment_installment_paymentmethod'];
                }else{
                    $postpaymentid = $postarray['payment']['paynova_paynovapayment_paymentmethod'];
                }



                $checksess = Mage::getSingleton('checkout/session');

                $payment->setAdditionalInformation('productid',$postpaymentid);
                $payment->save();

            }

            if (array_key_exists('paynova_paynovapayment_checkoutpath', $postarray['payment'])) {
                $checkoutpath = $postarray['payment']['paynova_paynovapayment_checkoutpath'];

                $checksess = Mage::getSingleton('checkout/session');
                $quote = $checksess ->getQuote();
                $payment = $quote->getPayment();

                $payment->setAdditionalInformation('checkoutpath',$checkoutpath);
                $payment->save();

            }

        }
    }

    public function setCurrentInvoiceToCapture(Varien_Event_Observer $observer)
    {
        $paymentMethod = $observer->getEvent()
            ->getPayment()
            ->getMethodInstance();

        Mage::helper('paynovapayment')->log("Setting current invoice to capture");


        $payment = $observer->getEvent()
            ->getPayment();

        $invoice = $observer->getEvent()
            ->getInvoice();

        $payment->setCurrentInvoice($invoice);

        $paymentMethod->captureInvoice($payment);
    }

}

		
