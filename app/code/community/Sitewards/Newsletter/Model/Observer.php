<?php
/**
 * Sitewards_Newsletter_Model_Observer
 *
 * Observe the save order after event and check for a newsletter subscription
 *
 * @category    Sitewards
 * @package     Sitewards_Newsletter
 * @copyright   Copyright (c) 2013 Sitewards GmbH (http://www.sitewards.com/)
 */
class Sitewards_Newsletter_Model_Observer
{
    /**
     * On all save order after events check for the 'is_subscribed' param
     * Either update the customer or if guest registration that create a subscriber
     *
     * @param Varien_Event_Observer $oObserver
     */
    public function saveOrderAfter(Varien_Event_Observer $oObserver)
    {
        /* @var $oOrder Mage_Sales_Model_Order */
        $oOrder = $oObserver->getData('order');

        $oRequest = Mage::app()->getRequest();
        $bNewsletter = $oRequest->getParam('is_subscribed', false);

        if ($bNewsletter == true) {
            $iCustomerId = $oOrder->getCustomerId();
            if ($iCustomerId) {
                $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
                $oCustomer->setIsSubscribed(1);
                $oCustomer->save();
            } else {
                $sEmailAddress = $oOrder->getCustomerEmail();
                /* @var $oNewsletterSubscriber Mage_Newsletter_Model_Subscriber */
                $oNewsletterSubscriber = Mage::getModel('newsletter/subscriber');
                $oNewsletterSubscriber->subscribe($sEmailAddress);
            }
        }
    }
}
