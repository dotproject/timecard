<?php /* TASKS $Id: do_updatetasklog.php,v 1.1 2004/04/16 18:27:01 bloaterpaste Exp $ */

//There is an issue with international UTF characters, when stored in the database an accented letter
//actually takes up two letters per say in the field length, this is a problem with costcodes since
//they are limited in size so saving a costcode as REDACIﾓN would actually save REDACIﾓ since the accent takes
//two characters, so lets unaccent them, other languages should add to the replacements array too...

function cleanText($text){
/*
	//This text file is not utf, its iso so we have to decode/encode
	$text = utf8_decode($text);
	$trade = array('・=>'a','・=>'a','・=>'a',
                 '・=>'a','・=>'a',
                 'ﾁ'=>'A','ﾀ'=>'A','ﾃ'=>'A',
                 'ﾄ'=>'A','ﾂ'=>'A',
                 '・=>'e','・=>'e',
                 '・=>'e','・=>'e',
                 'ﾉ'=>'E','ﾈ'=>'E',
                 'ﾋ'=>'E','ﾊ'=>'E',
                 '・=>'i','・=>'i',
                 '・=>'i','・=>'i',
                 'ﾍ'=>'I','ﾌ'=>'I',
                 'ﾏ'=>'I','ﾎ'=>'I',
                 '・=>'o','・=>'o','・=>'o',
                 '・=>'o','・=>'o',
                 'ﾓ'=>'O','ﾒ'=>'O','ﾕ'=>'O',
                 'ﾖ'=>'O','ﾔ'=>'O',
                 '・=>'u','・=>'u',
                 '・=>'u','・=>'u',
                 'ﾚ'=>'U','ﾙ'=>'U',
                 'ﾜ'=>'U','ﾛ'=>'U',
                 'ﾑ'=>'N','・=>'n');
    $text = strtr($text,$trade);
	$text = utf8_encode($text);
*/
	return $text;
}

require_once( $AppUI->getModuleClass( 'tasks' ) );

$del = dPgetParam( $_POST, 'del', 0 );

$obj = new CTaskLog();

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

if ($obj->task_log_date) {
	$date = new CDate( $obj->task_log_date );
	$obj->task_log_date = $date->format( FMT_DATETIME_MYSQL );
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Task Log' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT );
	}
	$AppUI->redirect();
} else {
	$obj->task_log_costcode = cleanText($obj->task_log_costcode);
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( @$_POST['task_log_id'] ? 'updated' : 'inserted', UI_MSG_OK, true );
		$AppUI->redirect("m=timecard&tab=0");
	}
}

//echo '<pre>';print_r($obj);echo '</pre>';

$AppUI->redirect();
?>
