<?php 
	Global $tab,$TIMECARD_CONFIG;
	
	$show_possible_hours_worked = $TIMECARD_CONFIG['show_possible_hours_worked'];

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

	//Get has of users
	$sql = "
		SELECT 
			user_id,
			concat(user_first_name,' ',user_last_name) as name,
			user_email
		FROM 
			users
		ORDER BY user_last_name, user_first_name
	";
	$result = db_loadList($sql);	
	$people = array();

	foreach($result as $row){
		$people[$row['user_id']] = $row;
	}
	unset($result);

	$sql = "
		SELECT 
			project_id,
			project_name,
			company_name
		FROM 
			projects
			LEFT JOIN companies ON projects.project_company=companies.company_id
		WHERE 
		".getPermsWhereClause("companies", "company_id")."
	";
	if($company_id>0){
		$sql .= " AND projects.project_company = $company_id";
	}
	
	$sql .= " ORDER BY project_name";

//	print "<pre>$sql</pre>";
	$result = db_loadList($sql);


	for($i=0;$i<count($result);$i++){
		$project_id = $result[$i]['project_id'];
		$projects[$project_id] = $result[$i];
//		$projects[$project_id]['totals'] = array();
//		$projects[$project_id]['users'] = array();
		$ids[] = $project_id;
		unset($project_id);
	}
	unset($result);
	
	if(isset($ids))
	for($i=0;$i<$week_count;$i++){
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

		//set that to just midnight so as to grab the whole day
		$date = $start_day->format("%Y-%m-%d")." 00:00:00";
		$start_day -> setDate($date, DATE_FORMAT_ISO);
		//set that to just before midnight so as to grab the whole day
		$date = $end_day->format("%Y-%m-%d")." 23:59:59";
		$end_day -> setDate($date, DATE_FORMAT_ISO);

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
				project_id, 
				project_name, 
				sum(task_log_hours) as hours
			FROM 
				task_log
				left join tasks on task_log.task_log_task = tasks.task_id
				left join projects on tasks.task_project = projects.project_id
			WHERE
				project_id in (".implode(", ",$ids).")
				AND task_log_date >= '".$start_day->format( FMT_DATETIME_MYSQL )."' 
				AND task_log_date <= '".$end_day->format( FMT_DATETIME_MYSQL )."'
			GROUP BY
				project_id, task_log.task_log_creator
		";

//		print "<pre>$sql</pre>";
		$result = db_loadList($sql);

		foreach($result as $row){
			$projects[$row['project_id']]['users'][$row['task_log_creator']][$i] = $row['hours'];
			@$projects[$row['project_id']]['totals'][$i] += $row['hours'];
		}

		$start_day -> addDays(-7);
	}

//print "<pre>".print_r($projects, true)."</pre>";
	$sql = "SELECT company_id, company_name FROM companies WHERE ".getPermsWhereClause("companies", "company_id")." ORDER BY company_name";
	$companies = arrayMerge( array( 0 => $AppUI->_('All Companies') ), db_loadHashList( $sql ) );

	//last day of that week, add 6 days
	$next_day = new CDate ();
	$next_day -> copy($start_day);
	$next_day -> addDays($week_count*7*2);
?>
<form name="frmCompanySelect" action="" method="get">
	<input type="hidden" name="m" value="timecard">
	<input type="hidden" name="report_type" value="weekly_by_project">
	<input type="hidden" name="tab" value="<?=$tab?>">
	<table cellspacing="1" cellpadding="2" border="0" width="100%">
	<tr>
		<td width="95%"><?=arraySelect( $companies, 'company_id', 'size="1" class="text" id="medium" onchange="document.frmCompanySelect.submit()"',
                          $company_id )?></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=<?=$tab?>&report_type=weekly_by_project&start_date=<?php echo urlencode($start_day->getDate()) ;?>"><img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'previous' );?>" border="0"></a></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=<?=$tab?>&report_type=weekly_by_project&start_date=<?php echo urlencode($start_day->getDate()) ;?>"><?=$AppUI->_('previous')?> <?= $week_count?> <?=$AppUI->_('weeks')?></a></td>
		<td width="1%" nowrap="nowrap">&nbsp;|&nbsp;</td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=<?=$tab?>&report_type=weekly_by_project&start_date=<?php echo urlencode($next_day->getDate()) ;?>"><?=$AppUI->_('next')?> <?= $week_count?> <?=$AppUI->_('weeks')?></a></td>
		<td width="1%" nowrap="nowrap"><a href="?m=timecard&tab=<?=$tab?>&report_type=weekly_by_project&start_date=<?php echo urlencode($next_day->getDate()) ;?>"><img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'next' );?>" border="0"></a></td>
	</tr>
	</table>

</form>
<table cellspacing="1" cellpadding="2" border="0" class="std" width="100%">
<?php
	if(!isset($projects)){
?>
	<tr><td align="center"><?=$AppUI->_('No Users Available')?></td></tr>
<?php
	} else {

?>
<tr>
	<th><?=$AppUI->_('Project/UserName')?></th>
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
	if(isset($projects))
	foreach($projects as $id => $project){
	//only display projects with time assigned

	if(isset($project['totals'])){
?>
<tr>
	<td nowrap="nowrap" style="border-top:1px solid #BBBBBB;border-bottom:1px solid #BBBBBB;background:#EEEEEE;"><a href="?m=projects&a=view&project_id=<?=$id?>"><?=$project['project_name']?></a></td>
	<td nowrap="nowrap" style="border-top:1px solid #BBBBBB;border-bottom:1px solid #BBBBBB;background:#EEEEEE;" ><?=$project['company_name']?></td>
<?php
	for($i=$week_count-1;$i>=0;$i--){
?>
	<td style="border-top:1px solid #BBBBBB;border-bottom:1px solid #BBBBBB;background:#EEEEEE;"><?=isset($project['totals'][$i])?round($project['totals'][$i],2):"0"?></td>
<?php
	}
?>
</tr>
<?php
		if(isset($project['users']))
		foreach($project['users'] as $id => $person){
?>
<tr>
	<td colspan="2">&nbsp;&nbsp;&nbsp;<?=$people[$id]['name']?></td>
<?php
echo "";
			for($i=$week_count-1;$i>=0;$i--){
				 $hours = isset($person[$i])?$person[$i]:0;
				 $hours = round($hours,2);
?>
	<td><a href="?m=timecard&user_id=<?=$id?>&tab=0&start_date=<?=$start_data_linkable[$i]?>"><?=$hours?></a></td>
<?php
			}
?>
</tr>
<?php
		}
?>
</tr>
<?php
	}
	}
}
?>
</table>
	
	
