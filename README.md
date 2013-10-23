Sitewards Newsletter
==========================

Extends newsletter functionality according to German law.

Features
------------------
* Added checkbox for a newsletter subscription to onepage checkout
* No notice email is send during subscribing and un subscribing.
* Email confirmation with link is send every time, when customer subscribes to newsletter.

File list
------------------
* app\code\community\Sitewards\Newsletter\etc\config.xml
    * Set-up model declaration
    * Set-up helper declaration
    * Set-up event observers for module
        * checkout_type_onepage_save_order_after
    * Set-up translations
        * Frontend
    * Set-up layout
* app\code\community\Sitewards\Newsletter\Helper\Data.php
    * Check if there is an active newsletter subscription for the current quote.billing address.email
* app\code\community\Sitewards\Newsletter\Model\Observer.php
    * Observe the save order after event and check for a newsletter subscription
* app\code\community\Sitewards\Newsletter\Model\Subscriber.php
    * Notice email is not sent if subscription status changes
    * Confirmation link is always sent when customer subscribes on newsletter
    * Removed sending un subscription email
* app\design\frontend\base\default\layout\sitewards\newsletter.xml
    * Set new template for checkout onepage agreements
* app\design\frontend\base\default\template\sitewards\newsletter\checkout\onepage\agreements.phtml
    * Extends checkout onepage agreements template with checkbox for a newsletter subscription
* app\etc\modules\Sitewards_Newsletter.xml
    * Activate module
    * Specify community code pool
    * Set-up dependencies
        * Mage_Newsletter
* app\locale\de_DE\Sitewards_Newsletter.csv
    * Extension translation