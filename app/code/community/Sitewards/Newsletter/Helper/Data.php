<?php
/**
 * Sitewards_Newsletter_Helper_Data
 * - check if there is an active newsletter subscription
 *   for the current quote.billing address.email
 * @category    Sitewards
 * @package     Sitewards_Newsletter
 * @copyright   Copyright (c) 2013 Sitewards GmbH (http://www.sitewards.com/)
 */
class Sitewards_Newsletter_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns true if there is an active newsletter subscription
     * for the current quote.billing address.email
     *
     * This works even for _guest_ checkouts as the check is not done on the customer
     * but rather on the quote.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        $email = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getBillingAddress()
            ->getEmail();
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        $bShowSubscribingCheckbox = in_array(
            $subscriber->getStatus(),
            array(
                Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED,
                Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED,
                Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE
            )
        );
        if ($subscriber->getId() && $bShowSubscribingCheckbox) {
            return true;
        } else {
            return false;
        }
    }
}
