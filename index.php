<?php /* HISTORY $Id: index.php,v 1.2 2004/04/29 21:47:18 bloaterpaste Exp $ */

// check permissions
$denyRead = getDenyRead( $m );
$denyEdit = getDenyEdit( $m );

if ($denyRead) {
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

$TIMECARD_CONFIG = array();
require_once( "./modules/timecard/config.php" );

// setup the title block
$titleBlock = new CTitleBlock( 'Timecard', 'TimeCard.png', $m, "$m.$a" );

$titleBlock->show();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'TimecardVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'TimecardVwTab' ) ? $AppUI->getState( 'TimecardVwTab' ) : 0;

$tabBox = new CTabBox( "?m=timecard", "./modules/timecard/", $tab );
$tabBox->add( 'vw_timecard', 'Weekly Time Card' );
//$tabBox->add( 'vw_monthly', 'Monthly' );
$tabBox->add( 'vw_newlog', 'Task Log' );
if($TIMECARD_CONFIG['integrate_with_helpdesk']){
	$tabBox->add( 'vw_newhelpdesklog', 'Helpdesk Log' );
}
if($TIMECARD_CONFIG['allow_reporting']){
	$tabBox->add( 'vw_reports', 'Reports' );
}
$tabBox->show();
?>
