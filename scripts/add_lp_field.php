<?php

 

// Turn on debugging level

$Vtiger_Utils_Log = true;

chdir('../');

require_once('vtlib/Vtiger/Menu.php');

require_once('vtlib/Vtiger/Block.php');

require_once('vtlib/Vtiger/Field.php');

 

// Include necessary classes

include_once('vtlib/Vtiger/Module.php');

 

// Define instances

$users = Vtiger_Module::getInstance('Users');

$blockInstance = new Vtiger_Block();

 

// Nouvelle instance pour le nouveau bloc

$block = Vtiger_Block::getInstance('LBL_MORE_INFORMATION', $users);

 

 

// Add Default User Module

$fieldInstance = new Vtiger_Field();

$fieldInstance->name = 'user_default_module';

$fieldInstance->table = 'vtiger_users';

$fieldInstance->column = 'user_default_module';

$fieldInstance->label = 'User Default Module ';

$fieldInstance->columntype = 'varchar(50)';

$fieldInstance->uitype = 15;

$fieldInstance->typeofdata = 'V~O';

$block->addField($fieldInstance);

 

?>