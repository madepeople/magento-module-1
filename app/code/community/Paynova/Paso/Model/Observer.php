<?php 
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

class Paynova_Paso_Model_Observer
{
	/* Observer method called to send the refund request to paynova */
	public function send_paso_request(Varien_Event_Observer $observer)
	{

        Mage::throwException('Refund disabled - Peter');
		try
		{
			$event = $observer->getEvent(); 					//Event observer
			$creditMemoArr=$event->getCreditmemo()->getData();  // Get creditmemo post details
			$orderId = $creditMemoArr['order_id']; 				// Order ID 
			$amount=$creditMemoArr['grand_total']; 		 		// Amount to be refunded
			$coreOrderObj = Mage::getModel('sales/order')->load($orderId); //Order Object
			$orderArr=$coreOrderObj->load($orderId);	// Get all details about the order by order Id
			$corePaymentObj = $coreOrderObj->getPayment()->getMethodInstance();	// get Payment Object	 
			$selectedPaymentId=$corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment. 
			$selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
			$IncrementOrderId = $coreOrderObj->getIncrementId();
			//Check whether the order belongs to Paynova payment module or any other payment module.
			if (!strpos($selectedModelCode, 'paso_')) {
				$filterSelectedModelCode=str_replace('_','/',$selectedModelCode);
				$abstractModel=Mage::getModel("$filterSelectedModelCode"); // create a object for acc class.
				$sceretKey=Mage::getStoreConfig('paso/settings/secret_key'); // secret_key as given in the Admin area.
		    		$merchantID= Mage::getStoreConfig('paso/settings/customer_id'); // marchant Id as given in the Admin area.
			    	$messageId= $abstractModel->_messageIdForIP();	// Message Id for each server request.
			 	   // $write = Mage::getSingleton('core/resource')->getConnection('core_write'); 	//get db object to execute custom query
			     	//$readresult=$write->query("SELECT * from paso_notification_details where order_id=$IncrementOrderId");

			     	//get the transaction Id and currency


				// Calculate checksum 
				$checksum= $merchantID.$messageId.$sceretKey;
				$checksum=sha1($checksum);
				$contractText = 'Refund for Store: '.Mage::app()->getStore()->getName(). ' and URL: '.Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB);
				//$contractText = 'Refund for test store';
				// Create XML for refund request 
		     	$xml= '<?xml version="1.0" encoding="utf-8"?>
						<envelope>
							<header>
								<merchantID>'.$merchantID.'</merchantID>
							    <messageID>'.$messageId.'</messageID>
							</header>
							<refundRequest>
							   <originalTransactionID>'.$transactionId.'</originalTransactionID>
							    <amount currency="'.$currency.'">'.$amount.'</amount>
							    <contractText>'.$contractText.'</contractText>
							    <checksum>'.$checksum.'</checksum>
							</refundRequest>
						</envelope>';
				
	         	//$output=$abstractModel->setCurlCall($xml); 		//post the refund request XML through CURL.

	           	// End the execution 
	           	if(!$output){
		            	Mage::throwException('Unable to send the request on Paynova server.');
	           	}

				// Message of the log
				$msg='/n/r'.date('Y-m-d H:i:s').' INPUT XML /n/r'.$xml;
	           	$msg.='/n/r'.date('Y-m-d H:i:s').' OUTPUT XML /n/r'.$output.'/n/r';

	           	// Write the log of the received xml
	           	$this->_writeStatusFile($msg);

	           	//Process the response.
	           	$this->getRefundResponse($output, $orderId);
	           	Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/sales_order/view', array('order_id' =>$orderId)));
	        }
	   		else{
				Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/sales_order/view', array('order_id' => $orderId)));
			}
		}
		 catch (MageCore_Exception $e) {

	        $this->_getSession()->addError($e->getMessage());
	      	$this->_redirectToOrderPage($orderId);
	    } catch (Exception $e) {
	        Mage::logException($e);
	        $this->_redirectToOrderPage($orderId);
	    }
	}

	/* Method to create the log of the refund request and refund response */
	public function _writeStatusFile($msg)
	{
		$fp=fopen(Mage::getBaseDir('base').'/var/observer.txt','a+');
		fwrite($fp, $msg);
		fclose($fp);
	}

	/* Method to decode the xml response obtained by Paynova */ 

	public function getRefundResponse($output, $orderId)
	{
		// Create an array of the xml by encoding it to JSON and then decode it.
	       	$xml = simplexml_load_string($output);
   		$json = json_encode($xml);
		$responseArray = json_decode($json,TRUE);

		if(array_key_exists('error',$responseArray)){ // check if response XML return any error.
			$error=$responseArray['error']['errorMessage'];

			$this->_redirectToOrderPage($orderId);

		}

		$refundStatus=0;
		$statusMsg='';
		if(is_array($responseArray['refundResponse'])){
			$refundStatus = $responseArray['refundResponse']['status']['result'];
			$statusMsg = $responseArray['refundResponse']['status']['message'];
		}

		if($refundStatus==0){
			$this->_writeStatusFile('Cannot save the credit memo.');
			$this->_redirectToOrderPage($orderId);
			return false;
			}
			else{
				return true;
			}
	}

	public function _redirectToOrderPage($orderId)
	{
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Can not process the Refund, try again'));
		$this->_writeStatusFile('Cannot save the credit memo.');
		header("Location: ".Mage::getUrl('*/sales_order/view', array('order_id' => $orderId)));
		die();
	}

    /* Observer method called to send the annull request to paynova */
    public function send_paynova_cancel(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $method = $event->getPayment()->getMethodInstance()->getCode();

        if (Mage::helper('paso')->isMethodPaynova($method)) {

            //OBS! Kan vi köra entity id? är det alltid samma som order id?
            $entid = $event->getPayment()->getEntityId();

            // LADDA ORDER VIA $entid;
            $order = Mage::getModel('sales/order')->load($entid);
            $totalamount =  round($order->getGrandTotal(),2);

            $transid =  $order->getPayment()->getLastTransId();

            if (empty($transid)) {
                Mage::getSingleton('core/session')->addError('Missing transaction ID. Can´t send anull order request to Paynova');
                $order->addStatusToHistory($order->getStatus(), 'Missing transaction ID. Can´t send anull order request to Paynova.', false);
                $order->save();
                $logmsg = "Missing transaction ID from order #".$order->getIncrementId().". Can´t send anull order request to Paynova.";
                Mage::Helper('paso')->log($logmsg);
            } else {

                $corePaymentObj = $order->getPayment()->getMethodInstance();
                $selectedPaymentId=$corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment.
                $selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
                $filterSelectedModelCode=str_replace('_','/',$selectedModelCode);
                $abstractModel=Mage::getModel($filterSelectedModelCode); // create a object for acc class.


                $res['transactionId'] = $transid;
                $res['totalAmount'] = $totalamount;

                $output=$abstractModel->setCurlCall($res, '/transactions/'.$res['transactionId'].'/annul/'.$res['totalAmount']); 		// post the initialize payment Json through CURL.
                Mage::Helper('paso')->log($output);

	           	if(!$output){
                    Mage::getSingleton('core/session')->addError('Something went wrong. Anull request not sent to Paynova.');
                    $order->addStatusToHistory($order->getStatus(), 'Something went wrong. Anull request not sent to Paynova.', false);
                    $order->save();
	           	}
            }
        }

    }

    /* Observer method called to remove the goverment ID from the quote */
    public function removeGovIdFromQuote($observer)
    {
        /*
        $quote = $observer->getEvent()->getQuote();
        if ($quote) {
            if (!$quote->getIsActive()) {
                $payment = $quote->getPayment();
                if ($payment) {
                    if (Mage::helper('paso')->isMethodPaynova($payment->getMethod())) {
                        if ($payment->getAdditionalInformation('govermentid')) {
                            $payment->setAdditionalInformation('govermentid',NULL);
                            $payment->save();
                        }
                    }
                }
            }
        }
        */
    }


    /* Observer method called to set goverment ID to quote */
    public function afterBillingMethod(Varien_Event_Observer $observer){

        $postarray = Mage::app()->getRequest()->getPost();
        if (array_key_exists('payment', $postarray)) {
            if (array_key_exists('paynova_paso_govermentid', $postarray['payment'])) {
                $postgovid = $postarray['payment']['paynova_paso_govermentid'];

                $checksess = Mage::getSingleton('checkout/session');
                $quote = $checksess ->getQuote();
                $payment = $quote->getPayment();

                $payment->setAdditionalInformation('govermentid',$postgovid);
                $payment->save();

            }
        }

    }

    /* Observer method called to set goverment ID to quote after order save*/
    public function afterOrderSaveMethod(Varien_Event_Observer $observer){

        $postarray = Mage::app()->getRequest()->getPost();
        if (array_key_exists('payment', $postarray)) {
            if (array_key_exists('paynova_paso_govermentid', $postarray['payment'])) {
                $postgovid = $postarray['payment']['paynova_paso_govermentid'];

                $checksess = Mage::getSingleton('checkout/session');
                $quote = $checksess ->getQuote();
                $payment = $quote->getPayment();

                $payment->setAdditionalInformation('govermentid',$postgovid);
                $payment->save();

            }
        }


    }
    public function setCurrentInvoiceToCapture(Varien_Event_Observer $observer)
    {
        $paymentMethod = $observer->getEvent()
            ->getPayment()
            ->getMethodInstance();

        $payment = $observer->getEvent()
            ->getPayment();

        $invoice = $observer->getEvent()
            ->getInvoice();

        $payment->setCurrentInvoice($invoice);

        $paymentMethod->captureInvoice($payment);
    }

}

		
