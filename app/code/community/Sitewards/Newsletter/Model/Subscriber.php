<?php
/**
 * Sitewards_Newsletter_Model_Subscriber
 *
 * Update the function for checking newsletter subscription
 *
 * @category    Sitewards
 * @package     Sitewards_Newsletter
 * @copyright   Copyright (c) 2013 Sitewards GmbH (http://www.sitewards.com/)
 */
class Sitewards_Newsletter_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    /**
     * Rewrote parent method:
     * - notice email is not sent if subscription status changes
     * - confirmation link is always sent when customer subscribes on newsletter
     * @see Mage_Newsletter_Model_Subscriber::subscribeCustomer()
     *
     * @param   Mage_Customer_Model_Customer $oCustomer
     * @return  Mage_Newsletter_Model_Subscriber
     */
    public function subscribeCustomer($oCustomer)
    {
        $this->loadByCustomer($oCustomer);

        if ($oCustomer->getImportMode()) {
            $this->setImportMode(true);
        }

        if (!$oCustomer->getIsSubscribed() && !$this->getId()) {
            // If subscription flag not set or customer is not a subscriber
            // and no subscribe below
            return $this;
        }

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        /*
         * Logical mismatch between customer registration confirmation code and customer password confirmation
         */
        $iConfirmation = null;
        if ($oCustomer->isConfirmationRequired() && ($oCustomer->getConfirmation() != $oCustomer->getPassword())) {
            $iConfirmation = $oCustomer->getConfirmation();
        }

        $bSendInformationEmail = false;
        if ($oCustomer->hasIsSubscribed()) {
            $iCustomerStatus = $oCustomer->getIsSubscribed()
                ? (($this->getStatus() == self::STATUS_SUBSCRIBED)
                    ? self::STATUS_SUBSCRIBED
                    : self::STATUS_UNCONFIRMED
                )
                : self::STATUS_UNSUBSCRIBED;
            /**
             * If subscription status has been changed then send email to the customer
             */
            if ($iCustomerStatus != $this->getStatus()) {
                $bSendInformationEmail = true;
            }
        } elseif (($this->getStatus() == self::STATUS_UNCONFIRMED) && (is_null($iConfirmation))) {
            $iCustomerStatus = self::STATUS_UNCONFIRMED;
            $bSendInformationEmail = true;
        } else {
            $iCustomerStatus = ($this->getStatus() == self::STATUS_NOT_ACTIVE ? self::STATUS_UNSUBSCRIBED : $this->getStatus());
        }
        if ($iCustomerStatus != $this->getStatus()) {
            $this->setIsStatusChanged(true);
        }

        $this->setStatus($iCustomerStatus);

        if (!$this->getId()) {
            $iStoreId = $oCustomer->getStoreId();
            if ($oCustomer->getStoreId() == 0) {
                $iStoreId = Mage::app()->getWebsite($oCustomer->getWebsiteId())->getDefaultStore()->getId();
            }
            $this->setStoreId($iStoreId)
                ->setCustomerId($oCustomer->getId())
                ->setEmail($oCustomer->getEmail());
        } else {
            $this->setStoreId($oCustomer->getStoreId())
                ->setEmail($oCustomer->getEmail());
        }

        $this->save();
        $bSendSubscription = $oCustomer->getData('sendSubscription') || $bSendInformationEmail;
        if (is_null($bSendSubscription) xor $bSendSubscription) {
            if ($this->getIsStatusChanged() && $iCustomerStatus == self::STATUS_SUBSCRIBED) {
                $this->sendConfirmationSuccessEmail();
            } elseif ($this->getIsStatusChanged() && $iCustomerStatus == self::STATUS_UNCONFIRMED) {
                $this->sendConfirmationRequestEmail();
            }
        }
        return $this;
    }

    /**
     * Removed sending unsubscription email
     * @see Mage_Newsletter_Model_Subscriber::unsubscribe()
     *
     * @return  Mage_Newsletter_Model_Subscriber
     */
    public function unsubscribe()
    {
        if ($this->hasCheckCode() && $this->getCode() != $this->getCheckCode()) {
            Mage::throwException(Mage::helper('newsletter')->__('Invalid subscription confirmation code.'));
        }

        $this->setSubscriberStatus(self::STATUS_UNSUBSCRIBED)->save();
        return $this;
    }

    /**
     * Rewrote parent method:
     * - notice email is not sent if subscription status changes
     * - confirmation link is always sent when customer subscribes on newsletter
     * @see Mage_Newsletter_Model_Subscriber::subscribe()
     *
     * @param string $sEmail
     * @throws Exception
     * @return int
     */
    public function subscribe($sEmail)
    {
        $this->loadByEmail($sEmail);
        $oCustomerSession = Mage::getSingleton('customer/session');

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        $bIsConfirmNeed   = (Mage::getStoreConfig(self::XML_PATH_CONFIRMATION_FLAG) == 1) ? true : false;
        $bIsOwnSubscribes = false;
        $iOwnerId = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($sEmail)
            ->getId();
        $bIsSubscribeOwnEmail = $oCustomerSession->isLoggedIn() && $iOwnerId == $oCustomerSession->getId();
        $iCurrentStatus = $this->getStatus();

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED
            || $this->getStatus() == self::STATUS_NOT_ACTIVE
        ) {
            if ($bIsConfirmNeed === true) {
                // if user subscribes own login email - confirmation is not needed
                $bIsOwnSubscribes = $bIsSubscribeOwnEmail;
                if ($bIsOwnSubscribes == true) {
                    $this->setStatus(self::STATUS_SUBSCRIBED);
                } else {
                    $this->setStatus(self::STATUS_NOT_ACTIVE);
                }
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
            $this->setSubscriberEmail($sEmail);
        }

        if ($bIsSubscribeOwnEmail) {
            $this->setStoreId($oCustomerSession->getCustomer()->getStoreId());
            $this->setCustomerId($oCustomerSession->getCustomerId());
        } else {
            $this->setStoreId(Mage::app()->getStore()->getId());
            $this->setCustomerId(0);
        }

        $this->setIsStatusChanged(true);

        $this->save();
        if ($iCurrentStatus != self::STATUS_SUBSCRIBED) {
            if ($bIsConfirmNeed === true
                && $bIsOwnSubscribes === false
            ) {
                $this->sendConfirmationRequestEmail();
            } else {
                $this->sendConfirmationSuccessEmail();
            }
        } elseif ($iCurrentStatus == self::STATUS_SUBSCRIBED) {
            $this->sendConfirmationSuccessEmail();
        }

        return $this->getStatus();
    }
}
