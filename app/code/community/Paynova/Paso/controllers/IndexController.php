<?php

class Paynova_Paso_IndexController extends Mage_Core_Controller_Front_Action
{

    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }


    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getEmailAction()
    {
        $billMailAddress ="";
        $checkout = $this->getQuote();
        $billMailAddress = $checkout->getCustomerEmail();
        echo  $billMailAddress;
    }


    public function getLogFileAction()
    {
        if (isset($_GET['tid'])) {
            $checktimestmp = time();
            $checktimestmp = substr($checktimestmp, 0, -4);
           if ($checktimestmp == $_GET['tid'])
           {

               $logDir  = Mage::getBaseDir('var') . DS . 'log' . DS;
               $logFile = $logDir."paynova.log";

               if( file_exists($logFile) ){
                   header('Content-Description: File Transfer');
                   header('Content-Type: text/plain');
                   header('Content-Disposition: attachment; filename='.basename($logFile));
                   header('Expires: 0');
                   header('Cache-Control: must-revalidate');
                   header('Pragma: public');
                   header('Content-Length: ' . filesize($logFile));
                   readfile($logFile);
                   exit;
               }
               else
               {
                    $this->_redirect('adminhtml/dashboard');
               }
           }
        }
        else
        {
            $this->_redirect('adminhtml/dashboard');
        }

    }

}