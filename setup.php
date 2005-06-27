<?php /* TIMECARD $Id: setup.php,v 1.3 2004/05/12 22:52:08 bloaterpaste Exp $ */
/*
dotProject Module

Name:      TimeCard
Directory: timecard
Version:   2.0
Class:     user
UI Name:   TimeCard
UI Icon:	TimeCard.png

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'TimeCard';
$config['mod_version'] = '2.0';
$config['mod_directory'] = 'timecard';
$config['mod_setup_class'] = 'CSetupTimeCard';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Time Card';
$config['mod_ui_icon'] = 'TimeCard.png';
$config['mod_description'] = 'Time Card allows easy access to a weekly timecard based on existing task logs.';
$config['mod_config'] = true;

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

//require_once( $AppUI->cfg['root_dir'].'/modules/system/syskeys/syskeys.class.php' );
require_once( dPgetConfig( 'root_dir' ).'/modules/system/syskeys/syskeys.class.php' );
/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupTimeCard {
/*
	Install routine
*/
	function install() {
		return true;
	}
/*
	Removal routine
*/
	function remove() {
		return true;
	}
/*
	Upgrade routine
*/
	function upgrade() {
		return true;
	}

	function configure() {
		global $AppUI;
		$AppUI->redirect("m=timecard&a=configure");
		return true;
	}
}

?>
