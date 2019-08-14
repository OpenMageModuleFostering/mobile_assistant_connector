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

class Emagicone_Mobassistantconnector_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function sendPushMessage($messageContent) {
        Mage::app()->cleanCache();
        $apiKey = Mage::getStoreConfig('mobassistantconnectorinfosec/access/api_key');
        $headers = array('Authorization: key=' . $apiKey, 'Content-Type: application/json');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageContent);
        $result = curl_exec( $ch );

        if(curl_errno($ch)) {
            Mage::log(
                "Push message error while sending CURL request: {$result}",
                null,
                'emagicone_mobassistantconnector.log'
            );
        }

        curl_close($ch);
        
        return $result;
    }

    public function pushSettingsUpgrade() {
        Mage::app()->getCacheInstance()->cleanType('config');
        $deviceIds = Mage::getStoreConfig('mobassistantconnectorinfosec/access/google_ids');

        if(strlen($deviceIds) > 0) {
            $deviceIds = unserialize($deviceIds);
        } else $deviceIds = array();

        foreach(array_keys($deviceIds) as $key) {
            if (!is_int($key)) {
                $deviceIds[$key]['push_device_id'] = $key;
                if(empty($deviceIds[$key]['push_store_group_id'])) {
                    $deviceIds[$key]['push_store_group_id'] = -1;
                }
                if(empty($deviceIds[$key]['push_currency_code'])) {
                    $deviceIds[$key]['push_currency_code'] = 'base_currency';
                }
                if(empty($deviceIds[$key]['app_connection_id'])) {
                    $deviceIds[$key]['app_connection_id'] = -1;
                }
                array_push($deviceIds, $deviceIds[$key]);
                unset($deviceIds[$key]);
            }
        }

        //Check for duplicated records
        foreach ($deviceIds as $a1 => $firstDevice) {
            if(empty($firstDevice['push_currency_code'])) {
                $deviceIds[$a1]['push_currency_code'] = 'base_currency';
            }
            if(empty($deviceIds[$key]['app_connection_id'])) {
                $deviceIds[$key]['app_connection_id'] = -1;
            }
            foreach($deviceIds as $a2 => $secondDevice){
                if(($firstDevice === $secondDevice) && ($a1 != $a2)) {
                    unset($deviceIds[$a1]);
                }
            }
        }

        return $deviceIds;
    }

    public function proceedGoogleResponse($response, $current_device) {
        if ($response) {
            $json = json_decode($response, true);
            if (function_exists('json_last_error')) {
                if (json_last_error() != JSON_ERROR_NONE) {
                    $json = array();
                }
                    // $d_r = Mage::helper('core')->jsonDecode($response, Zend_Json::TYPE_OBJECT);
                if (!empty($json)) {
                    $json_response = var_export($json, true);
                    Mage::log(
                        "Google response: ({$json_response})",
                        null,
                        'emagicone_mobassistantconnector.log'
                    );
                }
            }
        }
        else {
            $json = array();
        }
        Mage::app(0);

        $deviceIdActions = Mage::helper('mobassistantconnector')->pushSettingsUpgrade();

        $currentDeviceId = $current_device['device_id'];
        $currentAppConnectionId = $current_device['app_connection_id'];

        $failure = isset($json['failure']) ? $json['failure'] : null;

        $canonicalIds = isset($json['canonical_ids']) ? $json['canonical_ids'] : null;

        if ($failure || $canonicalIds) {
            $result = isset($json['results'][0]) ? $json['results'][0] : array();

            $newRegId = isset($result['registration_id']) ? $result['registration_id'] : null;
            $error = isset($result['error']) ? $result['error'] : null;
            if ($newRegId) {
                // Need to update old deviceId
                if ($newRegId !== $currentDeviceId) {
                    foreach ($deviceIdActions as $settingNum => $device) {
                        // Delete duplicated push configs with new RegistrationId
                        if ($device['app_connection'] == $currentAppConnectionId && $device['push_device_id'] == $newRegId) {
                            unset($deviceIdActions[$settingNum]);
                        // Renew push config with new RegistrationId
                        } elseif ($device['push_device_id'] == $currentDeviceId) {
                            $deviceIdActions[$settingNum]['push_device_id'] = $newRegId;
                        }
                    }
                }
            }
            else if ($error) {
                // Unset not registered device id
                if ($error == 'NotRegistered' || $error == 'InvalidRegistration') {
                    foreach ($deviceIdActions as $settingNum => $device) {
                        if($device['push_device_id'] == $currentDeviceId) {
                            unset($deviceIdActions[$settingNum]);
                        }
                    }
                }
            }
        }

        Mage::getModel('core/config')->saveConfig('mobassistantconnectorinfosec/access/google_ids', serialize($deviceIdActions));
        Mage::app()->init('');

        return $deviceIdActions;
    }

    public function price_format($iso_code, $curr_format, $price, $convert_to, $force = false, $format = true) {
        $currency_symbol = '';
        $price = str_replace(' ', '', $price);
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

        if(strlen($convert_to) == 3){
            try {
                // $price2 = Mage::helper('directory')->currencyConvert($price, $baseCurrencyCode, $convert_to);
//                $price = $this->currencyConvert($price, $baseCurrencyCode, $convert_to);

                $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
                $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));
                if (!empty($rates[$convert_to])) {
                    $price = $price * $rates[$convert_to];
                }

                $iso_code = $convert_to;
            } catch(Exception $e) {
                Mage::log(
                    "Error while currency converting (". var_export($e->getMessage(), true). ")",
                    null,
                    'emagicone_mobassistantconnector.log'
                );
            }

        }

        if($format) {
            $price = number_format(floatval($price), 2, '.', ' ');
        }

        preg_match('/^[a-zA-Z]+$/', $iso_code, $matches);

        if(count($matches) > 0) {
            if(strlen($matches[0]) == 3) {
                $currency_symbol = Mage::app()->getLocale()->currency($iso_code)->getSymbol();
            }
        } else {
            $currency_symbol = $iso_code;
        }

        if($force) {
            return $currency_symbol;
        }
//        $sign = '<span>' . $currency_symbol . '</span>';
        $sign = $currency_symbol;
        if($curr_format == 1) {
            $price = $sign . $price;
        } elseif ($curr_format == 2) {
            $price = $price;
        } else {
            $price = $price . ' ' . $sign;
        }

        return $price;
    }
}