<?php /* HELPDESK $Id: configure.php,v 1.7 2007/08/22 1:21 PM arcos Exp $ */

//This file will write a php config file to be included during execution of all timecard file for configuration.

// deny all but system admins
$canEdit = !getDenyEdit( 'system' );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

@include_once( "./functions/admin_func.php" );

$CONFIG_FILE = "./modules/timecard/config.php";

$AppUI->savePlace();

//define user type list
$user_types = arrayMerge( $utypes, array( '-1' => $AppUI->_('None') ) );

//All config options, their descriptions and their default values are defined here.  
//Add new config options here.  type can be "checkbox", "text", or "select".  If it's "select"
//then be sure to include a 'list' entry with the options.
$config_options = array(
	'minimum_report_level' => array(
		'description' => $AppUI->_('Minimum user level to access Reports.'),
		'value' => 0,
		'type' => 'select',
		'list' => @$user_types
	),
	'minimum_see_level' => array(
		'description' => $AppUI->_('Minimum user level to see others timecards.'),
		'value' => 0,
		'type' => 'select',
		'list' => @$user_types
	),
	'minimum_edit_level' => array(
		'description' => $AppUI->_('Minimum user level to edit others timecards.'),
		'value' => 0,
		'type' => 'select',
		'list' => @$user_types
	),
	'integrate_with_helpdesk' => array(
		'description' => $AppUI->_('Allow integration with HelpDesk module.'),
		'value' => 0,
		'type' => 'checkbox'
	),
	'show_possible_hours_worked' => array(
		'description' => $AppUI->_('Highlight where users went into overtime.'),
		'value' => 0,
		'type' => 'checkbox'
	)
);

//if this is a submitted page, overwrite the config file.
if(dPgetParam( $_POST, "Save", '' )!=''){

	if (is_writable($CONFIG_FILE)) {
		if (!$handle = fopen($CONFIG_FILE, 'w')) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be opened.'), UI_MSG_ERROR );
			exit;
		}

		if (fwrite($handle, "<?php //Do not edit this file by hand, it will be overwritting by the configuration utility. \n") === FALSE) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be written to.'), UI_MSG_ERROR );
			exit;
		} else {
			foreach ($config_options as $key=>$value){
				$val="";
				switch($value['type']){
					case 'checkbox': 
						$val = isset($_POST[$key])?"1":"0";
						break;
					case 'text': 
						$val = isset($_POST[$key])?$_POST[$key]:"";
						break;
					case 'select': 
						$val = isset($_POST[$key])?$_POST[$key]:"0";
						break;
					default:
						break;
				}
				
				fwrite($handle, "\$TIMECARD_CONFIG['".$key."'] = '".$val."';\n");
			}

			fwrite($handle, "?> \n");
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('has been successfully updated.'), UI_MSG_OK );
			fclose($handle);
		}
	} else {
		$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('is not writable.'), UI_MSG_ERROR );
	}
} else if(dPgetParam( $_POST, "Cancel", '' )!=''){
	$AppUI->redirect("m=system&a=viewmods");
}

$TIMECARD_CONFIG = array();
require_once( $CONFIG_FILE );

//Read the current config values from the config file and update the array.
foreach ($config_options as $key=>$value){
	if(isset($TIMECARD_CONFIG[$key])){
		$config_options[$key]['value']=$TIMECARD_CONFIG[$key];
	}
}

// setup the title block
$titleBlock = new CTitleBlock( 'Configure TimeCard Module', 'TimeCard.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=system", "system admin" );
$titleBlock->addCrumb( "?m=system&a=viewmods", "modules list" );
$titleBlock->show();

?>

<form method="post">
<table class="std">
<?php
foreach ($config_options as $key=>$value){
?>
	<tr>
		<td align="left"><?php echo $value['description']; ?></td>
		<td><?php
		switch($value['type']){
			case 'checkbox': ?>
				<input type="checkbox" name="<?php echo $key; ?>" <?php echo $value['value'] ? "checked=\"checked\"" : ""; ?> />
				<?php
				break;
			case 'text': ?>
				<input type="text" name="<?php echo $key; ?>" value="<?php echo $value['value']; ?>" />
				<?php
				break;
			case 'select': 
				print arraySelect( $value["list"], $key, 'class=text size=1', $value["value"] );
				break;
			default:
				break;
		}
		?></td>
	</tr>
<?php	
}
?>
	<tr>
		<td colspan="2" align="right"><input type="Submit" name="Cancel" value="<?php echo $AppUI->_('Back'); ?>"><input type="Submit" name="Save" value="<?php echo $AppUI->_('Save'); ?>"></td>
	</tr>
</table>
</form>
