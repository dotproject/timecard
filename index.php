<?php /* HISTORY $Id: index.php,v 1.1 2004/04/16 18:27:01 bloaterpaste Exp $ */

// check permissions
$denyRead = getDenyRead( $m );
$denyEdit = getDenyEdit( $m );

if ($denyRead) {
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

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
$tabBox->add( 'vw_reports', 'Reports' );
$tabBox->show();
?>
