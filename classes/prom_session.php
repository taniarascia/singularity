<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_session.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'includes/database.php');

define('SESSION_COOKIE', 'prom_session');

class prom_session
{
	// Checks if the session cookie is set
	public static function check ()
	{
		return isset($_COOKIE[SESSION_COOKIE]);
	}

	// Initializes session settings and starts a session
	public static function start ()
	{
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.referer_check', '');
		ini_set('session.use_trans_sid', 0);
		session_set_save_handler(
			array('prom_session', 's_open'),
			array('prom_session', 's_close'),
			array('prom_session', 's_read'),
			array('prom_session', 's_write'),
			array('prom_session', 's_destroy'),
			array('prom_session', 's_gc')
		);
		session_name(SESSION_COOKIE);
		session_set_cookie_params(0, parse_url(URL_BASE, PHP_URL_PATH), '.'. parse_url(URL_BASE, PHP_URL_HOST));
		session_start();

		// need this so it happens before $db goes away
		register_shutdown_function('session_write_close');
	}

	// Checks if all critical session variables are set
	public static function verify ()
	{
		$vars = array('user', 'empire');
		foreach ($vars as $var)
			if (!isset($_SESSION[$var]))
				return FALSE;
		return TRUE;
	}

	// Destroys the session and unsets the session cookie
	public static function kill ()
	{
		$_SESSION = array();
		setcookie(SESSION_COOKIE, NULL, 0, parse_url(URL_BASE, PHP_URL_PATH), '.'. parse_url(URL_BASE, PHP_URL_HOST));
		unset($_COOKIE[SESSION_COOKIE]);
		session_destroy();
	}

	// Initializes a session variable if it has not yet been set.
	public static function initvar ($name, $value)
	{
		if (!isset($_SESSION[$name]))
			$_SESSION[$name] = $value;
	}

	// Insure that there is an active database connection
	// Path and name are discarded since we don't use them
	public static function s_open ($path, $name)
	{
		global $db;
		if (!isset($db))
		{
			warning('Attempted to open session before database connection was established!', 0);
			return FALSE;
		}
		return TRUE;
	}

	// Nothing to do here
	public static function s_close ()
	{
		return TRUE;
	}

	// Fetch a session from the database if it exists
	public static function s_read ($id)
	{
		global $db;
		$q = $db->prepare('SELECT sess_data FROM '. SESSION_TABLE .' WHERE sess_id = ?');
		$q->bindStrValue(1, $id);
		$q->execute() or warning('Unable to load session data', 0);
		if ($row = $q->fetch())
			return $row['sess_data'];
		else	return NULL;
	}

	// Store a session back into the database, replacing it if it already exists
	public static function s_write ($id, $data)
	{
		global $db;
		$exists = $db->queryCell('SELECT COUNT(*) FROM '. SESSION_TABLE .' WHERE sess_id = ?', array($id));
		if ($exists)
		{
			$q = $db->prepare('UPDATE '. SESSION_TABLE .' SET sess_data = ?, sess_time = ? WHERE sess_id = ?');
			$q->bindStrValue(1, $data);
			$q->bindIntValue(2, CUR_TIME);
			$q->bindStrValue(3, $id);
			if ($q->execute())
				return TRUE;
			warning('Unable to update session data', 0);
		}
		else
		{
			$q = $db->prepare('INSERT INTO '. SESSION_TABLE .' (sess_id,sess_time,sess_data) VALUES (?,?,?)');
			$q->bindStrValue(1, $id);
			$q->bindIntValue(2, CUR_TIME);
			$q->bindStrValue(3, $data);
			if ($q->execute())
				return TRUE;
			warning('Unable to save session data', 0);
		}
		return FALSE;
	}

	// Purge a session from the database
	public static function s_destroy ($id)
	{
		global $db;
		$q = $db->prepare('DELETE FROM '. SESSION_TABLE .' WHERE sess_id = ?');
		$q->bindStrValue(1, $id);
		if (!$q->execute())
		{
			warning('Unable to delete session data', 0);
			return FALSE;
		}
		return TRUE;
	}

	// Garbage collection - delete all sessions older than 2 hours
	public static function s_gc ($date)
	{
		global $db;
		$db->queryParam('DELETE FROM '. SESSION_TABLE .' WHERE sess_time < ?', array(CUR_TIME - 2 * 60 * 60));
		return TRUE;
	}
} // class prom_session
?>
