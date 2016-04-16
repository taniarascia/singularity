<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: logging.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'includes/bbcode.php');

function error_handler ($errno, $errstr, $errfile, $errline)
{
	// Do NOT attempt to log any errors that occur within this file
	// or it will likely throw itself into an infinite loop
	if ($errfile == __FILE__)
		return FALSE;
	logmsg($errno, "$errstr in '$errfile' on line '$errline'");
	return TRUE;
}

// Base warning level
$warning_baselevel = 0;

// In cases where a class method can be called either directly or via parent:: in a subclass,
// call warning_wrap() before all parent:: calls and warning_unwrap() immediately afterward.
// Currently, this is required only for prom_empire::load() and prom_empire::save()
function warning_wrap ()
{
	global $warning_baselevel;
	$warning_baselevel++;
}
function warning_unwrap ()
{
	global $warning_baselevel;
	$warning_baselevel--;
}

// Generates a warning message
// If in the setup script, display as formatted XHTML
// If in turns script, display as plain text both to STDOUT (to be logged to disk) and to STDERR (to be emailed to the admin)
// If in a utility script, display as plain text to STDOUT only
// If in-game, store in database (along with other relevant information)
// Specify level = 0 to indicate the file/line where warning() itself was called,
// level = 1 for the caller, level = 2 for the caller's caller, etc.
// If a description is specified, a message will be delivered to the in-game moderator mailbox
function warning ($text, $level, $desc = NULL)
{
	global $warning_baselevel;
	$dbg = debug_backtrace();
	$level += $warning_baselevel;
	if (!isset($dbg[$level]))
		$level = 0;
	$file = $dbg[$level]['file'];
	$line = $dbg[$level]['line'];
	if (defined('IN_SETUP'))
		echo "<br /><b>Warning</b>:  $text in <b>$file</b> on line <b>$line</b><br />\n";
	elseif (defined('IN_TURNS'))
	{
		fprintf(STDOUT, "\nWarning: %s in %s on line %u\n", $text, $file, $line);
		fprintf(STDERR, "PHP Warning:  %s in %s on line %u\n", $text, $file, $line);
	}
	elseif (defined('IN_SCRIPT'))
		fprintf(STDOUT, "\nWarning: %s in %s on line %u\n", $text, $file, $line);
	else	logmsg(E_USER_WARNING, "$text in '$file' on line '$line'");

	if (isset($desc))
	{
		global $db;
		$q = $db->prepare('INSERT INTO '. EMPIRE_MESSAGE_TABLE .' (m_id_ref,m_time,e_id_src,e_id_dst,m_subject,m_body,m_flags) VALUES (0,?,0,0,?,?,?)');
		$q->bindIntValue(1, CUR_TIME);
		$q->bindStrValue(2, $text);
		$q->bindStrValue(3, bbencode($desc));
		$q->bindStrValue(4, MFLAG_REPORT);
		$q->execute();
	}
}

// If logging is enabled, logs the current event
function logevent ($text = '')
{
	global $emp1;
	if (isset($emp1) && ($emp1->e_flags & EFLAG_LOGGED))
	{
		logmsg(E_ERROR, $text);
		return;
	}
	if (LOG_ENABLE)
	{
		logmsg(0, $text);
		return;
	}
}

// Log an event into the database
function logmsg ($type, $text = '')
{
	global $page, $action, $lock;
	global $db;
	$q = $db->prepare('INSERT INTO '. LOG_TABLE .' (log_time,log_type,log_ip,log_page,log_action,log_locks,log_text,u_id,e_id,c_id) VALUES (?,?,?,?,?,?,?,?,?,?)');
	$q->bindIntValue(1, CUR_TIME);
	$q->bindIntValue(2, $type);
	$q->bindStrValue(3, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'n/a');
	$q->bindStrValue(4, isset($page) ? $page : 'n/a');
	$q->bindStrValue(5, isset($action) ? $action : 'n/a');

	$lockstr = '';
	if (isset($lock))
	{
		foreach ($lock as $name => $val)
			if ($val > 0)
				$lockstr .= ','. substr($name, 0, 1) . $val;
		$lockstr = trim($lockstr, ',');
	}
	if (!strlen($lockstr))
		$lockstr = 'n/a';

	$q->bindStrValue(6, $lockstr);
	$q->bindStrValue(7, $text);

	global $user1, $emp1, $clan1;
	$q->bindIntValue(8, ($user1 && $user1->isLoaded()) ? $user1->u_id : 0);
	$q->bindIntValue(9, ($emp1 && $emp1->isLoaded()) ? $emp1->e_id : 0);
	$q->bindIntValue(10, ($clan1 && $clan1->isLoaded()) ? $clan1->c_id : 0);
	$q->execute();	// can't display a warning message here, or it'd recurse onto itself
}

// Log an important event from turns processing into the database
function logmsg_turns ($text = '')
{
	global $db;
	$q = $db->prepare('INSERT INTO '. LOG_TABLE .' (log_time,log_type,log_ip,log_page,log_action,log_locks,log_text) VALUES (?,?,?,?,?,?,?)');
	$q->bindIntValue(1, CUR_TIME);
	$q->bindIntValue(2, 0);
	$q->bindStrValue(3, $_SERVER['REMOTE_ADDR']);
	$q->bindStrValue(4, 'turns');
	$q->bindStrValue(5, 'turns');
	$q->bindStrValue(6, '*');
	$q->bindStrValue(7, $text);
	$q->execute();
}

// When passing get_defined_vars() as second parameter, fetches all variables whose names are in the first parameter
// and formats them into a text string suitable for inserting into the log database table
function varlist ($names, $vars)
{
	$arr = array();
	foreach ($names as $name)
		$arr[$name] = $vars[$name];		
	$data = explode("\n", var_export($arr, TRUE));
	array_pop($data);
	array_shift($data);
	$ret = implode('', $data);
	return trim(str_replace(array('array ( ', ', )', ' => '), array('arr[', ']', ':'), preg_replace('/ +/', ' ', $ret)));
}
?>
