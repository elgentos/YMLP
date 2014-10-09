<?php
class Elgentos_Ymlp_Model_Observer
{
    public function __construct()
    {
        if(Mage::getStoreConfig('ymlp/general/enabled',Mage::app()->getStore())) {
        	require_once(Mage::getBaseDir('lib') . '/ymlp/YMLP_API.class.php');
            $this->groupID = Mage::getStoreConfig('ymlp/general/ymlpgroupid',Mage::app()->getStore());
            $this->API_key = Mage::getStoreConfig('ymlp/general/ymlpapikey',Mage::app()->getStore());
            $this->API_user = Mage::getStoreConfig('ymlp/general/ymlpuser',Mage::app()->getStore());
            $this->api = new YMLP_API($this->API_key,$this->API_user);
        }
    }

    private function log($output,$email,$fromFunction=null) {
        if ($this->api->FoutMelding){
            $message = 'Connectie probleem: ' . $this->api->FoutMelding;
        } else {
            $message = "{$output["Code"]} => {$output["Output"]}";
            Mage::log($fromFunction.' - '.$message.' - '.$email, null, 'newsletter_subscriptions.log');
        }
    }

    public function dispatcher($observer) {
        if(Mage::getStoreConfig('ymlp/general/enabled',Mage::app()->getStore())) {

            $event = $observer->getEvent();
            $subscriber = $event->getSubscriber();

            $status = $subscriber->getSubscriberStatus();
            if($status==1) {
                $this->subscribe($observer);
            } else {
                $this->unsubscribe($observer);
            }
        }
    }

    public function unsubscribe($observer)
    {
        if(Mage::getStoreConfig('ymlp/general/enabled',Mage::app()->getStore())) {

            $event = $observer->getEvent();
            $subscriber = $event->getSubscriber();

            $output=$this->api->ContactenVerwijderen($subscriber->getEmail(),$this->groupID);
            $this->log($output,$subscriber->getEmail(),'unsubscribe');
        }
    }

    public function subscribe($observer)
    {
        if(Mage::getStoreConfig('ymlp/general/enabled',Mage::app()->getStore())) {

            $event = $observer->getEvent();
            $subscriber = $event->getSubscriber();

            try {
                $customer = Mage::getModel('customer/customer')->loadByEmail($subscriber->getEmail());
                $OverigeVelden = array('Voornaam'=>$customer->getFirstname(),'Achternaam'=>$customer->getLastname());
                $OverruleUnsubscribedBounced = '0';
                $output=$this->api->ContactenToevoegen($subscriber->getEmail(),$OverigeVelden,$this->groupID,$OverruleUnsubscribedBounced);
                $this->log($output,$subscriber->getEmail());
            } catch(Exception $e) {
                // user has no account
                $OverigeVelden = null;
                $OverruleUnsubscribedBounced = '0';
                $output=$this->api->ContactenToevoegen($subscriber->getEmail(),$OverigeVelden,$this->groupID,$OverruleUnsubscribedBounced);
                $this->log($output,$subscriber->getEmail(),'subscribe');
            }
        }
    }
}