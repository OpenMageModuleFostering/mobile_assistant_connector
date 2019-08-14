<?php
/**
 *    This file is part of Mobile Assistant Connector.
 *
 *   Mobile Assistant Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Mobile Assistant Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Mobile Assistant Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

class Emagicone_Mobassistantconnector_Helper_Access extends Mage_Core_Helper_Abstract
{
    const HASH_ALGORITHM = 'sha256';
    const MAX_LIFETIME = 43200; /* 12 hours */
//    const TABLE_SESSION_KEYS = 'emagicone_mobassistantconnector_sessions';
//    const TABLE_FAILED_ATTEMPTS = 'emagicone_mobassistantconnector_failed_login';

    public static function clearOldData()
    {
        $timestamp = time();
        $date = Mage::getStoreConfig('mobassistantconnectorinfosec/access/cl_date');

        if ($date === false || ($timestamp - (int)$date) > self::MAX_LIFETIME)
        {
            $sessions = Mage::getModel("emagicone_mobassistantconnector/sessions")->getCollection();
            $sessions->addFieldToFilter('date_added', array('lt' => ($timestamp - self::MAX_LIFETIME)));
            foreach ($sessions as $session) {
                $session->delete();
            }

            $attempts = Mage::getModel("emagicone_mobassistantconnector/failed")->getCollection();
            $attempts->addFieldToFilter('date_added', array('lt' => ($timestamp - self::MAX_LIFETIME)));
            foreach ($attempts as $attempt) {
                $attempt->delete();
            }

            Mage::getModel('core/config')->saveConfig('mobassistantconnectorinfosec/access/cl_date', $timestamp );
        }
    }

    public static function clearAllData()
    {
        $timestamp = time();

        $sessions = Mage::getModel("emagicone_mobassistantconnector/sessions")->getCollection();
        foreach ($sessions as $session) {
            $session->delete();
        }

        $attempts = Mage::getModel("emagicone_mobassistantconnector/failed")->getCollection();
        foreach ($attempts as $attempt) {
            $attempt->delete();
        }

        Mage::getModel('core/config')->saveConfig('mobassistantconnectorinfosec/access/cl_date', $timestamp );
    }

    public static function getSessionKey($hash)
    {
        $login = Mage::getStoreConfig('mobassistantconnectorinfosec/emoaccess/login');
        $password = Mage::getStoreConfig('mobassistantconnectorinfosec/emoaccess/password');

        if (hash(self::HASH_ALGORITHM, $login.$password) == $hash)
            return self::generateSessionKey($login);
        else
            self::addFailedAttempt();

        return false;
    }

    public static function checkSessionKey($key)
    {
        if(!empty($key)) {

            $timestamp = time();

            $sessions = Mage::getModel("emagicone_mobassistantconnector/sessions")->getCollection();
            $sessions->addFieldToFilter('date_added', array('gt' => ($timestamp - self::MAX_LIFETIME)));
            $sessions->addFieldToFilter('session_key', array('eq' => $key));

            if($sessions->getSize() > 0) {
                return true;
            } else {
                self::addFailedAttempt();
            }
        }

        return false;
    }

    public static function generateSessionKey($username)
    {
        $timestamp = time();

        $enc_key = (string)Mage::getConfig()->getNode('global/crypt/key');

        $key = hash(self::HASH_ALGORITHM, $enc_key.$username.$timestamp);

        $sessions = Mage::getModel("emagicone_mobassistantconnector/sessions");
        $sessions->setData(array('session_key' => $key, 'date_added' => $timestamp));
        $sessions->save();

        return $key;
    }

    public static function addFailedAttempt()
    {
        $timestamp = time();

        $attempts = Mage::getModel("emagicone_mobassistantconnector/failed");
        $attempts->setData(array('ip' => $_SERVER['REMOTE_ADDR'], 'date_added' => $timestamp));
        $attempts->save();


        $attempts = Mage::getModel("emagicone_mobassistantconnector/failed")->getCollection();
        $attempts->addFieldToFilter('date_added', array('gt' => ($timestamp - self::MAX_LIFETIME)));
        $attempts->addFieldToFilter('ip', array('eq' => $_SERVER['REMOTE_ADDR']));
        $count_failed_attempts = $attempts->getSize();

        self::setDelay((int)$count_failed_attempts);
    }

    private static function setDelay($count_attempts)
    {
        if ($count_attempts <= 10)
            sleep(2);
        elseif ($count_attempts <= 20)
            sleep(5);
        elseif ($count_attempts <= 50)
            sleep(10);
        else
            sleep(20);
    }

}