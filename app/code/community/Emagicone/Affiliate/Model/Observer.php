<?php
/**
 * Created by PhpStorm.
 * User: jarchik
 * Date: 12/2/14
 * Time: 4:42 PM
 */

class Emagicone_Affiliate_Model_Observer
{
    const COOKIE_KEY_SOURCE = 'EmagiconeAffiliateURL';

    public function captureReferral(Varien_Event_Observer $observer)
    {
        $frontController = $observer->getEvent()->getFront();

        $referrer = $frontController->getRequest()->getServer('HTTP_REFERER');
        $pos = strpos($referrer, '?');
        if($pos) {
            $referrer = substr($referrer, 0, $pos);
        }

        $cookieReferrer = $this->getAffiliateURL();
        if(!$cookieReferrer) {
            if ($referrer) {
                Mage::getModel('core/cookie')->set(
                    self::COOKIE_KEY_SOURCE,
                    $referrer,
                    $this->_getCookieLifetime()
                );
            }
        }
    }

    protected function _getCookieLifetime()
    {
        $days = 1;

        // convert to seconds
        return (int)86400 * $days;
    }

    public function getAffiliateURL()
    {
        return Mage::getModel('core/cookie')->get(
            self::COOKIE_KEY_SOURCE
        );
    }
}