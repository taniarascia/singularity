<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: turns.php 1983 2014-10-01 15:18:43Z quietust $
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('UTC');
ignore_user_abort(1);
set_time_limit(0);

// require CLI invocation
if (isset($_SERVER['REQUEST_URI']))
	die("Access denied");

// prevent recursion
if (defined('IN_GAME'))
	die("Access denied");

define('IN_GAME', TRUE);
define('IN_TURNS', TRUE);
define('PROM_BASEDIR', dirname(__FILE__) . '/');

// Don't allow accessing anything while setup.php is being run
if (file_exists(PROM_BASEDIR .'setup.php'))
	die("Access denied");

require_once(PROM_BASEDIR .'config.php');
require_once(PROM_BASEDIR .'includes/constants.php');
require_once(PROM_BASEDIR .'includes/database.php');
require_once(PROM_BASEDIR .'includes/language.php');
require_once(PROM_BASEDIR .'includes/logging.php');
require_once(PROM_BASEDIR .'includes/misc.php');
require_once(PROM_BASEDIR .'includes/news.php');
require_once(PROM_BASEDIR .'classes/prom_vars.php');
require_once(PROM_BASEDIR .'classes/prom_user.php');
require_once(PROM_BASEDIR .'classes/prom_empire.php');
require_once(PROM_BASEDIR .'classes/prom_clan.php');
require_once(PROM_BASEDIR .'classes/prom_turns.php');

function statecho ($message)
{
	$time = explode(' ', microtime());
	echo sprintf('%s.%06d - [----/--/-- --:--] - ', date('Y/m/d H:i:s', $time[1]), $time[0] * 1000000) . $message ."\n";
}

if (TURNS_CRONTAB)
{
	$starttime = microtime(TRUE);
	statecho('Connecting to database...');

	$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
	do
	{
		if (!$db)
		{
			statecho('Database is offline - aborting');
			break;
		}

		$world = new prom_vars();
		if (!$world->load())
		{
			statecho('Unable to read world variables!');
			break;
		}

		if (!$world->check())
		{
			statecho('Game setup is not yet complete');
			break;
		}

		$turns = new prom_turns();
		$turns->doUpdate();
	} while (0);

	$duration = microtime(TRUE) - $starttime;
	echo "Total time elapsed: $duration seconds\n\n";
}
else
{
	$db = db_open(DB_TYPE, DB_SOCK, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
	if (!$db)
	{
		fprintf(STDERR, "Warning:  Database is offline, unable to query turn reports!\n");
		exit;
	}

	$last = $db->queryCell('SELECT MAX(turn_id) FROM '. TURNLOG_TABLE .' WHERE turn_type IN (?,?)', array(TURN_END, TURN_ABORT));
	if (!$last)
		exit;
	$q = $db->queryParam('SELECT * FROM '. TURNLOG_TABLE .' WHERE turn_id <= ? ORDER BY turn_id ASC', array($last));
	$logdata = $q->fetchAll();
	foreach ($logdata as $row)
	{
		switch ($row['turn_type'])
		{
		case TURN_START:
			$starttime = $row['turn_time'] + $row['turn_ticks'] / 1000000;
		case TURN_EVENT:
		case TURN_ABORT:
			echo sprintf('%s.%06d - [%s] - %s', date('Y/m/d H:i:s', $row['turn_time']), $row['turn_ticks'], ($row['turn_interval'] == 0) ? '----/--/-- --:--' : gmdate('Y/m/d H:i', $row['turn_interval']), $row['turn_text']) ."\n";
			// allow TURN_ABORT to fall through
			if ($row['turn_type'] != TURN_ABORT)
				break;
		case TURN_END:
			$endtime = $row['turn_time'] + $row['turn_ticks'] / 1000000;
			$duration = $endtime - $starttime;
			echo "Total time elapsed: $duration seconds\n";
			echo "\n";
			break;
		}
	}
	$db->queryParam('DELETE FROM '. TURNLOG_TABLE .' WHERE turn_id <= ?', array($last));
}
?>
