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

class Emagicone_Mobassistantconnector_Model_Defpassword extends Mage_Core_Model_Config_Data
{
    public function toOptionArray()
    {
		Mage::app()->cleanCache();

        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        if (!$db->showTableStatus('emagicone_mobassistantconnector_sessions')) {
            $db->query("CREATE TABLE `" . (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname') .
                    (string)Mage::getConfig()->getTablePrefix() . ".emagicone_mobassistantconnector_sessions` (
                        `session_id` int(11) NOT NULL auto_increment,
                        `session_key` varchar(100) NOT NULL default '',
                        `date_added` int(11) NOT NULL,
                        PRIMARY KEY (`session_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }
        if (!$db->showTableStatus('emagicone_mobassistantconnector_failed_login')) {
            $db->query("CREATE TABLE `" . (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname') .
                    (string)Mage::getConfig()->getTablePrefix() . "emagicone_mobassistantconnector_failed_login` (
                        `attempt_id` int(11) NOT NULL auto_increment,
                        `ip` varchar(20) NOT NULL default '',
                        `date_added` int(11) NOT NULL,
                        PRIMARY KEY (`attempt_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }



        $password = Mage::getStoreConfig('mobassistantconnectorinfosec/emoaccess/password');
        if($password === 'c4ca4238a0b923820dcc509a6f75849b') {
            Mage::getSingleton('core/session')->addNotice(Mage::helper('mobassistantconnector/data')->__('<span style="color:green">Mobile Assistant Connector: </span> Default password is "1". Please change it because of security reasons!'));
        }
    }
}