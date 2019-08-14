<?php
/**
 * Created by PhpStorm.
 * User: jarchik
 * Date: 5/13/15
 * Time: 11:41 AM
 */

$installer = $this;

$installer->startSetup();

/*$table = $installer->getConnection()
    ->newTable($installer->getTable('emo_assistantconnector/sessions'))
    ->addColumn('key', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'identity'  => false,
        'primary'   => true,
    ), 'Key')
    ->addColumn('date_added', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable'  => false,
        'unsigned'  => false,
    ), 'Date added');
$installer->getConnection()->createTable($table);*/

$installer->run("
        -- DROP TABLE IF EXISTS {$this->getTable('emagicone_mobassistantconnector_sessions')};
        CREATE TABLE {$this->getTable('emagicone_mobassistantconnector_sessions')} (
        `session_id` int(11) NOT NULL auto_increment,
        `session_key` varchar(100) NOT NULL default '',
        `date_added` int(11) NOT NULL,
        PRIMARY KEY (`session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");



/*$table = $installer->getConnection()
    ->newTable($installer->getTable('emagicone_mobassistantconnector/failed'))
    ->addColumn('ip', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'identity'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('date_added', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable'  => false,
        'unsigned'  => false,
    ), 'Date added');
$installer->getConnection()->createTable($table);*/

$installer->run("
        -- DROP TABLE IF EXISTS {$this->getTable('emagicone_mobassistantconnector_failed_login')};
        CREATE TABLE {$this->getTable('emagicone_mobassistantconnector_failed_login')} (
        `attempt_id` int(11) NOT NULL auto_increment,
        `ip` varchar(20) NOT NULL default '',
        `date_added` int(11) NOT NULL,
        PRIMARY KEY (`attempt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");


$installer->endSetup();