<?php 

	//grab hours per day from config
	$min_hours_day = $AppUI->cfg['daily_working_hours'];
	//compute hours/week from config
	$min_hours_week =count(explode(",",$AppUI->getConfig("cal_working_days"))) * $min_hours_day;
	// get date format
	$df = $AppUI->getPref('SHDATEFORMAT');
	
	//How many weeks are we going to show?
	$week_count = 4;
		
	if (isset( $_GET['start_date'] )) {
		$AppUI->setState( 'TimecardWeeklyReportStartDate', $_GET['start_date'] );
	}
	$start_day = new CDate( $AppUI->getState( 'TimecardWeeklyReportStartDate' ) ? $AppUI->getState( 'TimecardWeeklyReportStartDate' ) : NULL);

	if (isset( $_GET['company_id'] )) {
		$AppUI->setState( 'TimecardWeeklyReportCompanyId', $_GET['company_id'] );
	}
	$company_id = $AppUI->getState( 'TimecardWeeklyReportCompanyId' ) ? $AppUI->getState( 'TimecardWeeklyReportCompanyId' ) : 0;

	//set that to just midnight so as to grab the whole day
	$date = $start_day->format("%Y-%m-%d")." 00:00:00";
	$start_day -> setDate($date, DATE_FORMAT_ISO);
	
	$today_weekday = $start_day -> getDayOfWeek();

	//roll back to the first day of that week, regardless of what day was specified
	$rollover_day = '0';
	$new_start_offset = $rollover_day - $today_weekday;
	$start_day -> addDays($new_start_offset);

	//last day of that week, add 6 days
	$end_day = new CDate ();
	$end_day -> copy($start_day);
	$end_day -> addDays(6);

	//set that to just before midnight so as to grab the whole day
	$date = $end_day->format("%Y-%m-%d")." 23:59:59";
	$end_day -> setDate($date, DATE_FORMAT_ISO);

	$selects = array();
	$join = array();
	$where = getPermsWhereClause("companies", "user_company");
	
	$sql = "
		SELECT 
			user_id,
			concat(user_first_name,' ',user_last_name) as name,
			user_email,
			company_name
		FROM 
			users
			LEFT JOIN companies ON companies.company_id=users.user_company
		WHERE 
		".$where."
	";
	if($company_id>0){
		$sql .= " AND users.user_company = $company_id";
	}
	
	$sql .= " ORDER BY user_last_name, user_first_name";

//	print "<pre>$sql</pre>";
	$result = db_loadList($sql);


	for($i=0;$i<count($result);$i++){
		$people[$result[$i]['user_id']] = $result[$i];
		$ids[] = $result[$i]['user_id'];
	}
	unset($result);
	
	if(isset($ids))
	for($i=0;$i<$week_count;$i++){
		$start_month = $start_day->format("%b");
		$end_month = $end_day->format("%b");
		$start_date = $start_day->format("%e");
		$end_date = $end_day->format("%e");
	
		$start_data_pretty[$i] =  "$start_month $start_date-".($start_month==$end_month?$end_date:"$end_month $end_date");
		$start_data_linkable[$i] =  urlencode($start_day->getDate()) ;
//		$starts[$i] =  $start_day->format($df);

		$sql = "
			SELECT
				task_log_creator,
				sum(task_log_hours) as hours
			FROM
				task_log
			WHERE
				task_log_creator in (".implode(", ",$ids).")
				AND task_log_date >= '".$start_day->format( FMT_DATETIME_MYSQL )."' 
				AND task_log_date <= '".$end_day->format( FMT_DATETIME_MYSQL )."'
			GROUP BY
				task_log_creator
			";

//		print "<pre>$sql</pre>";
		$result = db_loadList($sql);

		foreach($result as $row){
			$people[$row['task_log_creator']][$i] = $row['hours'];
		}

		$start_day -> addDays(-7);
		$end_day -> addDays(-7);
		//set that to just midnight so as to grab the whole day
		$date = $start_day->format("%Y-%m-%d")." 00:00:00";
		$start_day -> setDate($date, DATE_FORMAT_ISO);
		//set that to just before midnight so as to grab the whole day
		$date = $end_day->format("%Y-%m-%d")." 23:59:59";
		$end_day -> setDate($date, DATE_FORMAT_ISO);
	}

/*
	print "<table>";
	foreach($people as $row){
		print "<tr><td>";
		print implode("</td><td>", $row);
		print "</td></tr>";
		print "\n";
	}
	print "</table>";
	
	print "<pre>";
	print_r($people);
	print "</pre>";
*/

	$sql = "SELECT company_id, company_name FROM companies WHERE ".getPermsWhereClause("companies", "company_id")." ORDER BY company_name";
	$companies = arrayMerge( array( 0 => $AppUI->_('All Companies') ), db_loadHashList( $sql ) );

	//last day of that week, add 6 days
	$next_day = new CDate ();
	$next_day -> copy($start_day);
	$next_day -> addDays($week_count*7*2);
?>
<form name="frmCompanySelect" action="" method="get">
	<input type="hidden" name="m" value="timecard">
	<input type="hidden" name="report_type" value="weekly_by_user">
	<input type="hidden" name="tab" value="2">
	<table cellspacing="1" cellpadding="2" border="0" width="100%">
	<tr>
		<td width="95%"><?=arraySelect( $companies, 'company_id', 'size="1" class="text" id="medium" onchange="document.frmCompanySelect.submit()"',
                          $company_id )?></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=2&report_type=weekly_by_user&start_date=<?php echo urlencode($start_day->getDate()) ;?>"><img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'previous' );?>" border="0"></a></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=2&report_type=weekly_by_user&start_date=<?php echo urlencode($start_day->getDate()) ;?>"><?=$AppUI->_('previous')?> <?= $week_count?> <?=$AppUI->_('weeks')?></a></td>
		<td width="1%" nowrap="nowrap">&nbsp;|&nbsp;</td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=2&report_type=weekly_by_user&start_date=<?php echo urlencode($next_day->getDate()) ;?>"><?=$AppUI->_('next')?> <?= $week_count?> <?=$AppUI->_('weeks')?></a></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=2&report_type=weekly_by_user&start_date=<?php echo urlencode($start_day->getDate()) ;?>"><img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'next' );?>" border="0"></a></td>
	</tr>
	</table>

</form>
<table cellspacing="1" cellpadding="2" border="0" class="std" width="100%">
<?php
	if(!isset($people)){
?>
	<tr><td align="center"><?=$AppUI->_('No Users Available')?></td></tr>
<?php
	} else {

?>
<tr>
	<th><?=$AppUI->_('User')?></th>
	<th><?=$AppUI->_('Company')?></th>
<?php
	if(isset($start_data_pretty))
	for($i=$week_count-1;$i>=0;$i--){
?>
	<th><?=$start_data_pretty[$i]?></th>
<?php
	}
?>
</tr>
<?php
	if(isset($people))
	foreach($people as $id => $person){
?>
<tr>
	<td nowrap="nowrap"><?=$person['name']?></td>
	<td nowrap="nowrap"><?=$person['company_name']?></td>
<?php
	for($i=$week_count-1;$i>=0;$i--){
		 $hours = isset($person[$i])?$person[$i]:0;
		 $hours = round($hours,2);
?>
	<td <?=$hours<$min_hours_week?"bgcolor=\"#FFAEB8\"":""?>><a href="?m=timecard&user_id=<?=$id?>&tab=0&start_date=<?=$start_data_linkable[$i]?>"><?=$hours?></a></td>
<?php
	}
?>
</tr>
<?php
	}
}
?>
</table>
	
	
