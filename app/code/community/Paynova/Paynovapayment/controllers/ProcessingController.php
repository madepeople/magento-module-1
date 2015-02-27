<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * LK: Payment processing order
 *
 * @category    Paynova
 * @package     Paynova_Paynovapayment
 */

class Paynova_Paynovapayment_ProcessingController extends Mage_Core_Controller_Front_Action
{
	/**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _autoFinalize()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Iframe page which submits the payment data to Paynova.
     */
    public function placeformAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }

    /**
     * Show orderPlaceRedirect page which contains the Paynova iframe.
     */
    public function paymentAction()
    {


        try {

            $session = $this->_getCheckout();
            $session->setPaynovaRealOrderId($session->getLastRealOrderId());
            $quoteId = $session->getLastQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            $addinfo = $quote->getPayment()->getAdditionalInformation();

            if(!empty($addinfo['productid'])) {
                $paynova_product = $addinfo['productid'];
                $governmentid = $addinfo['governmentid'];
            }
			$order = Mage::getModel('sales/order');

            $paynova_order = Mage::getModel('paynovapayment/order');

            $coreOrderObj = new Mage_Sales_Model_Order();
			$lastIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
			$coreOrderObj->loadByIncrementId($lastIncrementId);

            $corePaymentObj = $coreOrderObj->getPayment()->getMethodInstance();

            //Create a "create order" structured array

            $res = $paynova_order->loadByIncrementId($lastIncrementId);



			$selectedPaymentId=$corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment.
			$selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
			$filterSelectedModelCode=str_replace('_','/',$selectedModelCode);

            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }

            $abstractModel=Mage::getModel($filterSelectedModelCode); // create a object for acc class.

            // call create order

            $res=$abstractModel->setCurlCall($res, '/orders/create/'); 		// post the initialize payment Json through CURL.

            Mage::helper('paynovapayment')->log($res);

           	if(!$res){
            	Mage::throwException('Unable to send the request on Paynova server.');
           	}else if(!isset($res->orderId)){
                Mage::throwException('Unable to send the request on Paynova server.'.$res->status->statusMessage);
            }

            $paynova_order_nr = $res->orderId;
            $paynova_status_key = $res->status->statusKey;
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage::helper('paynovapayment')->__('Order created in Paynova')
            );



            if($filterSelectedModelCode == 'paynovapayment/invoice' || $filterSelectedModelCode == 'paynovapayment/installment') {


                $res = $paynova_order->createAuthorizePaymentCall($order, $paynova_order_nr, $selectedPaymentId, $selectedModelCode, $paynova_product, $governmentid );

                $output = $abstractModel->setCurlCall($res, '/orders/'.$paynova_order_nr.'/authorizePayment');

                $res = $abstractModel->getResponseFromAuthorizePaymentCall($output); // recieved the response from paynova.

                if(!$res){

                    //Mage::throwException($output->status->statusMessage);
                    $this->_getCheckout()->addError($output->status->statusMessage);
                    $order->save();
                    $this->_redirect('checkout/cart');


                    return;

                }

                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    Mage::helper('paynovapayment')->__('Invoice/Installment created at Paynova.')
                );
                //set event data

                if($res && !empty($output->orderId) && !empty($output->transactionId)) {

                    $body['PAYMENT_1_TRANSACTION_ID'] = $output->transactionId;
                    $body['ORDER_ID'] = $output->orderId;
                    $body['ORDER_NUMBER'] = $session->getLastRealOrderId();

                    $event = Mage::getModel('paynovapayment/event')
                        ->setEventData($body);

                    //successevent

                    $quoteId = $event->successEvent(false);
                    $session->setLastSuccessQuoteId($quoteId);
                    $order->save();


                    $payment = $order->getPayment();

                    $trans = $payment->getTransaction($output->transactionId);





                }else{
                    Mage::throwException('order and/or transactionId is empty.');
                }

                //trying to capture

                $capture=Mage::getStoreConfig('paynovapayment/advanced_settings/auto_finalize');

                if($capture){
                    $err = $abstractModel->invoicing($order);


                    $order->save();

                    if($err){
                        foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){

                            Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
                        }
                        $this->_redirect('checkout/onepage/success', array('order_ids' => array($order->getId())));
                        return;
                    }
                }


                $order->save();

                //redirect to success
                $this->_redirect('checkout/onepage/success', array('order_ids' => array($order->getId())));
                return;
            }else{
                $res = $paynova_order->createInitializePaymentCall($order, $paynova_order_nr, $selectedPaymentId, $selectedModelCode);
                $output=$abstractModel->setCurlCall($res, '/orders/'.$res['orderId'].'/initializePayment');
                $redirectURL= $abstractModel->getResponseFromInitialPaymentCall($output); // recieved the response from paynova.
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    Mage::helper('paynovapayment')->__('Redirected to Paynova.')
                );

                $order->save();

                $session->setPaynovaQuoteId($session->getQuoteId());
                $session->setPaynovaRealOrderId($session->getLastRealOrderId());

                $lastQuoteId = $session->getLastQuoteId();
                Mage::getModel('sales/quote')->load($lastQuoteId)->setIsActive(true)->save();
                $session->clear();

                @header("Location: $redirectURL"); // Redirect browser.
                die();

                $this->loadLayout(); //todo check if lines should be removed
                $this->renderLayout();



            }

        } catch (Mage_Core_Exception $e) {

            $order->addStatusToHistory($order->getStatus(), $e->getMessage());   // try to save the exception with the current order.
            $this->_getCheckout()->addError('We are sorry, but an error occurred while attempting to process your payment.');
            $order->save();
            parent::_redirect('checkout/cart');
        }catch (Exception $e){
        	$this->_getCheckout()->addError($e->getMessage());
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
    {

        $body = $this->getRequest()->getParams();
        $capture=Mage::getStoreConfig('paynovapayment/advanced_settings/auto_finalize');

        $session = $this->_getCheckout();

        Mage::helper('paynovapayment')->log($body);

        $order_id = $session->getLastRealOrderId();

        $event = Mage::getModel('paynovapayment/event')
                 ->setEventData($body);

        $order = Mage::getModel('sales/order');

        $order->loadByIncrementId($body['ORDER_NUMBER']);


        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }

        if($order->getState() == 'pending_payment') {
            $order->addStatusHistoryComment(Mage::helper('paynovapayment')->__('Successfully returned from Paynova'));

        }else{
                Mage::helper('paynovapayment')->__('POST was not first exiting');
	            //todo check state of order before sending to success

                $order->save();
                $this->_redirect('checkout/onepage/success', array('order_ids' => array($order_id)));
                return;


        }

        $order->save();


        try {

            $quoteId = $event->successEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);

            $order->save();
            if($capture){


                $coreOrderObj = new Mage_Sales_Model_Order();
                $lastIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

                $coreOrderObj->loadByIncrementId($lastIncrementId);

                $corePaymentObj = $coreOrderObj->getPayment()->getMethodInstance();


                $selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
                $filterSelectedModelCode=str_replace('_','/',$selectedModelCode);

                $abstractModel=Mage::getModel($filterSelectedModelCode); // create a object for acc class.

                $err = $abstractModel->invoicing($order);


                $order->save();

                if($err){
                    foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){

                        Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
                    }
                    $this->_redirect('checkout/onepage/success', array('order_ids' => array($order_id)));
                    return;
                }

            }
            foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){

                Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
            }

            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {

            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {

            Mage::logException($e);
        }

        $this->_redirect('checkout/onepage');

    }

    /**
     * Action to which the customer will be returned if the payment process is
     * cancelled.
     * Cancel order and redirect user to the shopping cart.
     */
    public function cancelAction()
    {

    	$event = Mage::getModel('paynovapayment/event')
                 ->setEventData($this->getRequest()->getParams());
        $message = $event->cancelEvent();

        // set quote to active
        $url = '';
        $session = $this->_getCheckout();
        if ($quoteId = $session->getPaynovaQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
                $addinfo = $quote->getPayment()->getAdditionalInformation();
                if(!empty($addinfo['checkoutpath'])){
                    if(strpos($addinfo['checkoutpath'],'onestepcheckout') != false){
                        //redirect url to onestep
                        $url = 'onestepcheckout';
                    }elseif(strpos($addinfo['checkoutpath'],'firecheckout') != false){
                        //redirect url to firecheckout
                        $url = 'firecheckout';
                    }elseif(strpos($addinfo['checkoutpath'],'onepage') != false){
                        //redirect url to firecheckout
                        $url = 'checkout/onepage';
                    }
                }

            }
        }

        $session->addError($message);

        if(empty($url)) {
            $this->_redirect('checkout/cart');
        }else{
            $this->_redirect($url);
        }

    }

    public function callbackAction()
    {

        $auto_capture = Mage::getStoreConfig('paynovapayment/advanced_settings/auto_finalize');

        /*
         * todo  print some details about payment method to log
         *
         * todo R2 Check for event_type before going into authorize flow
         */
        $body = $this->getRequest()->getParams();

        $session = $this->_getCheckout();

        /*
         * print event to status
         */


        /*
         * todo move to event to process data and return nicer
         */
        if(!isset($body)){
            return;
        }
        if(!is_array($body)) {
            $my_arr = explode('&', $body);

            foreach ($my_arr as $id => $data) {
                $val = explode('=', $data);

                $key = $val[0];
                $value = $val[1];

            }
            $body[$key] = $value;
        }
        $order_id = $body['ORDER_NUMBER'];

        sleep(5);
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);


        if($body['EVENT_TYPE'] == 'SESSION_END'){
        Mage::helper('paynovapayment')->log('CALLBACK: SESSION END');

        exit;
        }

        if($body['EVENT_TYPE'] == 'PAYMENT'){
            Mage::helper('paynovapayment')->log('CALLBACK: PAYMENT');
            $order->save();

        }

        if($order->getState() == 'pending_payment') {
            Mage::helper('paynovapayment')->log('CALLBACK: FIRST');
            $order->save();

        }else{
            Mage::helper('paynovapayment')->log('CALLBACK: NOT FIRST');
            $order->save();
            return;
            exit;
        }


        $order->save();
        exit;
        $event = Mage::getModel('paynovapayment/event')
            ->setEventData($body);

        $order->save();

        Mage::helper('paynovapayment')->log('trying to process');
        try {

            $quoteId = $event->successEvent();

            Mage::helper('paynovapayment')->log('starting to process');
            $order->save();

            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);

            $order->save();

            if($auto_capture){
                Mage::helper('paynovapayment')->log('trying to capture');
                $payment = $order->getPayment();
                $amount = $body['PAYMENT_1_AMOUNT'];

                $coreOrderObj = new Mage_Sales_Model_Order();
                $lastIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

                $coreOrderObj->loadByIncrementId($lastIncrementId);

                $corePaymentObj = $coreOrderObj->getPayment()->getMethodInstance();

                $selectedPaymentId=$corePaymentObj->getSelectedPaymentId();  // get payment id from selected payment.
                $selectedModelCode=$corePaymentObj->getCode(); 				// get payment code for selected method.
                $filterSelectedModelCode=str_replace('_','/',$selectedModelCode);

                $abstractModel=Mage::getModel($filterSelectedModelCode); // create a object for acc class.

                $err = $abstractModel->invoicing($order);

                $res_capture = $abstractModel->capture($payment, $amount, $body['PAYMENT_1_TRANSACTION_ID']);
                $order->save();

                if($res_capture){
                    foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){

                        Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
                    }
                    $this->_redirect('checkout/onepage/success', array('order_ids' => array($order_id)));
                    return;
                }

            }
            foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){

                Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
            }


            return;
        } catch (Mage_Core_Exception $e) {

            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {

            Mage::logException($e);
        }

        $this->_redirect('checkout/cart');
    }

    public function pendingAction()
    {
        // Get the raw request body.
        $body = $this->getRequest()->getRawBody();
        $session = $this->_getCheckout();


        exit;


    }

    /**
     * Action to which the transaction details will be posted after the payment
     * process is complete.
     */
    public function statusAction()
    {

    }

    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('paynovapayment/redirect')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );
        return $this;
    }

}
