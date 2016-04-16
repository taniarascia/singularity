<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_user.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');

class prom_user extends prom_entity
{
	// Constructor - initialize as User
	public function __construct($id = 0)
	{
		parent::__construct($id, USER_TABLE, 'u_id', ENT_USER);
	}

	// Locates the user record having a particular username
	// Must be called using an uninitialized user object
	public function findName ($name)
	{
		if ($this->id != 0)
		{
			warning('Attempted to initialize already initialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		global $db;
		$id = $db->queryCell('SELECT u_id FROM '. USER_TABLE .' WHERE u_username = ?', array($name));
		if (!$id)
			return FALSE;
		$this->id = $id;
		return TRUE;
	}

	// Creates a brand new user record and inserts it into the database
	// Must be called using an uninitialized user object
	public function create ($username, $password, $name, $email, $language)
	{
		if ($this->id != 0)
		{
			warning('Attempted to initialize already initialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('INSERT INTO '. USER_TABLE .' (u_username,u_email) VALUES (?,?)');
		$q->bindStrValue(1, $username);
		$q->bindStrValue(2, $email);
		if (!$q->execute())
		{
			warning('Failed to create user account', 1);
			return FALSE;
		}

		$this->id = $db->lastInsertId($db->getSequence(USER_TABLE));
		$db->createLock($this->db_type, $this->id);
		$this->load();

		// set various properties of the new user
		$this->setPassword($password);
		$this->u_name = $name;
		$this->u_createdate = CUR_TIME;
		$this->u_lastdate = CUR_TIME;
		$this->u_lang = $language;
		$this->u_style = DEFAULT_STYLE;
		$this->u_timezone = DEFAULT_TIMEZONE;
		$this->u_dateformat = DEFAULT_DATEFORMAT;
		// initialize IP address if the account was created via HTTP request
		if (isset($_SERVER['REMOTE_ADDR']))
			$this->u_lastip = $_SERVER['REMOTE_ADDR'];

		return TRUE;
	}

	// Sets user's password
	public function setPassword ($password)
	{
		if ($this->id == 0)
		{
			warning('Attempted to set password on uninitialized entity of type '. get_class($this), 2);
			return FALSE;
		}

		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to set password while not locked', 1);
			return FALSE;
		}

		$this->u_password = enc_password($password);
		return TRUE;
	}

	// Verifies user's password
	// Specifying 2nd parameter makes it only attempt conversion
	public function checkPassword ($password, $convert_only = FALSE)
	{
		if ($this->id == 0)
			return FALSE;
		// handle and convert old password hashes
		// previously used a double-salted SHA1 which probably wasn't that effective
		if (strlen($this->u_password) == 64)
		{
			$salt1 = substr($this->u_password, 0, 12);
			$salt2 = substr($this->u_password, -12);
			if ($this->u_password == $salt1 . sha1($salt1 . $password . $salt2) . $salt2)
			{
				if ($this->locked())
					$this->u_password = enc_password($password);
				return TRUE;
			}
		}
		if ($convert_only)
			return FALSE;
		return chk_password($password, $this->u_password);
	}

	// Shortcut for setting flags within "u_flags"
	public function setFlag ($flag)
	{
		return $this->setData('u_flags', $this->data['u_flags'] | $flag);
	}

	// Shortcut for clearing flags within "u_flags"
	public function clrFlag ($flag)
	{
		return $this->setData('u_flags', $this->data['u_flags'] & ~$flag);
	}

	// Formats a date using the user's configured date format and time zone
	// Format can be overridden for special purposes
	public function customdate ($time, $format = NULL)
	{
		if (!$format)
			$format = $this->u_dateformat;
		$date = gmdate($format, $time + $this->u_timezone);
		// The only timezone formats we can reliably substitute are 'O' and 'P' (and 'c' and 'r' by extension)
		// Anything else is by name ('e' and 'T') or wouldn't match reliably ('Z')
		$date = str_replace(array(
				'+0000',
				'+00:00',
			), array(
				sprintf('%+03d%02d', $this->u_timezone / 3600, $this->u_timezone % 60),
				sprintf('%+03d:%02d', $this->u_timezone / 3600, $this->u_timezone % 60),
			), $date);
		// user might have put <> or & in their date format string
		$date = htmlspecialchars($date);
		return $date;
	}

	// Formats user name+number as a string
	public function __toString ()
	{
		if ($this->id == 0)
			return lang('COMMON_USER_NAMEID', 'COMMON_USER_UNINITIALIZED', prenum(0));
		return lang('COMMON_USER_NAMEID', $this->u_name, prenum($this->u_id));
	}
} // class prom_user
?>
