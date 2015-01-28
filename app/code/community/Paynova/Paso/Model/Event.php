<?php
/*
 * This file is part of the Paynova Paso Magento Payment Module, which enables the use of Paynova within the
 * Magento e-commerce platform.
 *
 * @category    Paynova
 * @package     Paynova_Paso
 */

/**
 * Paynova notification processor model
 */
class Paynova_Paso_Model_Event
{
    const PAYNOVA_STATUS_FAIL = -2;
    const PAYNOVA_STATUS_CANCEL = -1;
    const PAYNOVA_STATUS_PENDING = 0;
    const PAYNOVA_STATUS_SUCCESS = 2;

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Enent request data setter
     * @param array $data
     * @return Paynova_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

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
     * Process status notification from Paynova server
     *
     * @return String
     */
    public function processStatusEvent()
    {
        try {
            $params = $this->_validateEventData();
            $msg = '';
            switch($params['status']) {
                case self::PAYNOVA_STATUS_FAIL: //fail
                    $msg = Mage::helper('paso')->__('Payment failed.');
                    $this->_processCancel($msg);
                    break;
                case self::PAYNOVA_STATUS_CANCEL: //cancel
                    $msg = Mage::helper('paso')->__('Payment was canceled.');
                    $this->_processCancel($msg);
                    break;
                case self::PAYNOVA_STATUS_PENDING: //pending
                    $msg = Mage::helper('paso')->__('Pending bank transfer created.');
                    $this->_processSale($params['status'], $msg);
                    break;
                case self::PAYNOVA_STATUS_SUCCESS: //ok
                    $msg = Mage::helper('paso')->__('The amount has been authorized and captured by Paynova.');
                    $this->_processSale($params['status'], $msg);
                    break;
                case self::PAYNOVA_STATUS_APPROVED: //ok
                    $msg = Mage::helper('paso')->__('The amount has been authorized and captured by Paynova.');
                    $this->_processSale($params['status'], $msg);
                    break;
            }
            return $msg;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process cancellation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
            return Mage::helper('paso')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $params = $this->_validateEventData();

        $transactionID = $params['PAYMENT_1_TRANSACTION_ID'];
        $orderID = $params['ORDER_ID'];
        $orderNumber = $params['ORDER_NUMBER'];

        $order = $this->_order;

        $arrInformation = array('order_id' => $orderID,'transID' => $transactionID,'order_number' => $orderNumber);

        $this->directLinkTransaction($order,$transactionID,$transactionID,$arrInformation,'payment',' ');

        $this->_order->save();

        return $this->_order->getQuoteId();
    }
    /**
     * Creates Transactions for directlink activities
     *
     * @param Mage_Sales_Model_Order $order
     * @param int $transactionID - persistent transaction id
     * @param int $subPayID - identifier for each transaction
     * @param array $arrInformation - add dynamic data
     * @param string $typename - name for the transaction exp.: refund
     * @param string $comment - order comment
     *
     * @return Cashu_Helper_DirectLink $this
     */
    public function directLinkTransaction($order,$transactionID, $subPayID, $arrInformation = array(), $typename, $comment, $closed = 0)
    {

        Mage::getSingleton('core/session')->setTransactionID($transactionID);
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionID);
        $transaction = $payment->addTransaction($typename, null, false, $comment);
        $transaction->setParentTxnId($transactionID);
        $transaction->setIsClosed($closed);
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $arrInformation);
        $transaction->save();
        $order->save();
        return $this;
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($status, $msg)
    {
        switch ($status) {
            case self::PAYNOVA_STATUS_SUCCESS:
                $this->_createInvoice();
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('mb_transaction_id'));
                // send new order email
                $this->_order->sendNewOrderEmail();
                $this->_order->setEmailSent(true);
                break;
            case self::PAYNOVA_STATUS_PENDING:
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('mb_transaction_id'));
                break;
        }
        $this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }

    /**
     * Checking returned parameters
     * Thorws Mage_Core_Exception if error
     * @param bool $fullCheck Whether to make additional validations such as payment status, transaction signature etc.
     *
     * @return array  $params request params
     */
    protected function _validateEventData($fullCheck = true)
    {
        // get request variables

        $params = $this->_eventData;

        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }


         if ((empty($params['PAYMENT_1_TRANSACTION_ID']) && $params['SESSION_STATUS'] != 'Cancelled')
             || ($fullCheck == false && $this->_getCheckout()->getPaynovaRealOrderId() != $params['ORDER_NUMBER']))
        {
            Mage::throwException('Missing or invalid order ID.');
        }

        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params['ORDER_NUMBER']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'paso_')) {
            Mage::throwException('Unknown payment method.');
        }

        // make additional validation

        if ($fullCheck) {

            // check payment status
            if (empty($params['SESSION_STATUS'])) {
                Mage::throwException('Unknown payment status.');
            }

            // check transaction signature
            if (empty($params['DIGEST'])) {
                Mage::throwException('Invalid transaction signature.');
            }
            $secretKey = Mage::getStoreConfig(
                Paynova_Paso_Helper_Data::XML_PATH_SECRET_KEY,
                $this->_order->getStoreId()
            );

            $secretKey = Mage::helper('core')->decrypt($secretKey);

            $checkParams = array('ORDER_ID', 'SESSION_ID', 'ORDER_NUMBER', 'SESSION_STATUS','CURRENCY_CODE', 'PAYMENT_1_STATUS', 'PAYMENT_1_TRANSACTION_ID',  'PAYMENT_1_AMOUNT');
            $md5String = '';

            foreach ($checkParams as $key) {
                if (isset($params[$key])) {
                    $md5String .= $params[$key].";";

                }

            }

            $md5String .= $secretKey;


            $md5String = strtoupper(sha1(utf8_encode($md5String)));

            if ($md5String != $params['DIGEST']) {
                Mage::throwException('Hash is not valid.');

            }

            // check transaction amount if currency matches
            if ($this->_order->getOrderCurrencyCode() == $params['CURRENCY_CODE']) {
                if (round($this->_order->getGrandTotal(), 2) != $params['PAYMENT_1_AMOUNT']) {
                    Mage::throwException('Transaction amount does not match.');
                }
            }
        }


        return $params;
    }
    
    
	/**
     * convert transcation id for order
     * @return String
     */
    protected function _convertTransactionID($tid,$orderId){
        if (!$tid) return;
        $magentoId=(int)100000000;	// Since magento create default id staring from this.
        $mainId=$magentoId + (int)$tid;
        if($mainId!=$orderId){
        	$diffId=$mainId-$orderId;
        	if($_SERVER['SERVER_NAME']=='asterix.paynova.com') 
        		return (int)($mainId-$diffId);
        }		
        return $mainId;
    }
    
}
