<?php /* TIMECARD $Id: vw_reports.php,v 1.2 2004/04/29 21:47:18 bloaterpaste Exp $ */
error_reporting( E_ALL );
Global $m,$a,$tab,$TIMECARD_CONFIG;

$report_type = dPgetParam( $_REQUEST, "report_type", '' );

// check permissions for this record
$canRead = !getDenyRead( $m );

if (!$TIMECARD_CONFIG['allow_reporting']) {//!$canRead || 
	$AppUI->redirect( "m=public&a=access_denied" );
}

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

$reports = $AppUI->readFiles( $AppUI->getConfig( 'root_dir' )."/modules/timecard/reports", "\.php$" );

// setup the title block
$titleBlock = new CTitleBlock( 'TimeCard Reports', '', $m, "$m.$a" );
//$titleBlock->addCrumb( "?m=timecard", "timecards list" );
if ($report_type) {
	$titleBlock->addCrumb( "?m=timecard&tab=$tab", "reports index" );
}
$titleBlock->show();

if ($report_type) {
	$report_type = $AppUI->checkFileName( $report_type );
	$report_type = str_replace( ' ', '_', $report_type );
	require( $AppUI->getConfig( 'root_dir' )."/modules/timecard/reports/$report_type.php" );
} else {
	echo "<table>";
	echo "<tr><td><h2>" . $AppUI->_( 'Reports Available' ) . "</h2></td></tr>";
	foreach ($reports as $v) {
		$type = str_replace( ".php", "", $v );
		$desc_file = str_replace( ".php", ".$AppUI->user_locale.txt", $v );
		$desc = @file( $AppUI->getConfig( 'root_dir' )."/modules/timecard/reports/$desc_file" );

		echo "\n<tr>";
		echo "\n	<td><a href=\"index.php?m=timecard&tab=$tab&report_type=$type\">";
		echo @$desc[0] ? $desc[0] : $v;
		echo "</a>";
		echo "\n</td>";
		echo "\n<td>" . (@$desc[1] ? "- $desc[1]" : '') . "</td>";
		echo "\n</tr>";
	}
	echo "</table>";
}
?>
<br><br><br>
