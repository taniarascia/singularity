<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_clan.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');

define('RELATION_OUTBOUND', 1);
define('RELATION_INBOUND', 2);
define('RELATION_BOTH', RELATION_OUTBOUND | RELATION_INBOUND);

class prom_clan extends prom_entity
{
	// Constructor - initialize as Clan
	public function __construct($id = 0)
	{
		parent::__construct($id, CLAN_TABLE, 'c_id', ENT_CLAN);
	}

	// Creates a brand new clan record and inserts it into the database
	// Must be called using an uninitialized clan object
	// Parameter $emp must be a locked empire record (for consistency, though it does not modify it)
	public function create ($emp, $name, $password)
	{
		if ($this->id != 0)
		{
			warning('Attempted to initialize already initialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		if (get_class($emp) != 'prom_empire')
		{
			warning('Attempted to initialize entity of type '. get_class($this) .' using invalid base entity of type '. get_class($emp), 1);
			return FALSE;
		}

		if (!$emp->locked())
		{
			warning('Attempted to initialize entity of type '. get_class($this) .' using unlocked base entity of type '. get_class($emp), 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('INSERT INTO '. CLAN_TABLE.' (e_id_leader) VALUES (?)');
		$q->bindIntValue(1, $emp->e_id);
		if (!$q->execute())
		{
			warning('Failed to create clan for empire '. $emp, 1);
			return FALSE;
		}

		$this->id = $db->lastInsertId($db->getSequence(CLAN_TABLE));
		$db->createLock($this->db_type, $this->id);
		$this->load();

		// set various properties of the new clan
		$this->c_name = $name;
		$this->c_title = def_lang('CLAN_CREATE_DEFAULT_TITLE', $name);
		$this->setPassword($password);
		$this->c_members = 1;

		// create clan news topic
		$q = $db->prepare('INSERT INTO '. CLAN_TOPIC_TABLE .' (c_id,ct_flags) VALUES (?,?)');
		$q->bindIntValue(1, $this->id);
		$q->bindIntValue(2, CTFLAG_NEWS);
		$q->execute() or warning('Failed to create news topic for clan '. $this, 1);
		$topic_id = $db->lastInsertId($db->getSequence(CLAN_TOPIC_TABLE));

		// create initial post in clan news topic
		$q = $db->prepare('INSERT INTO '. CLAN_MESSAGE_TABLE .' (ct_id,e_id,cm_body,cm_time) VALUES (?,?,?,?)');
		$q->bindIntValue(1, $topic_id);
		$q->bindIntValue(2, $emp->e_id);
		$q->bindStrValue(3, def_lang('CLAN_CREATE_DEFAULT_MOTD', $name));
		$q->bindIntValue(4, CUR_TIME);
		$q->execute() or warning('Failed to create news post for clan '. $this, 1);

		return TRUE;
	}

	// Only loads minimal data, e.g. for displaying on news reports
	public function loadPartial ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to load uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		global $db;
		$q = $db->prepare('SELECT c_id,c_name,c_title FROM '. $this->db_table .' WHERE '. $this->db_id .' = ?');
		$q->bindIntValue(1, $this->id);
		if (!$q->execute())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to load from database', 1);
			return FALSE;
		}

		if (!$this->initdata($q->fetch()))
			return FALSE;

		$this->update = array();
		return TRUE;
	}

	// Retrieves a list of all relations this clan has with any others
	// checkflags - flags to examine (must include those in needflags)
	// needflags - exactly which flags need to be set or clear
	// direction - combination of RELATION_OUTBOUND and RELATION_INBOUND
	// full - TRUE to return the entire clan relation record, FALSE to simply return the other clan's ID
	public function listRelations ($checkflags, $needflags, $direction = RELATION_BOTH, $full = FALSE)
	{
		$relations = array();

		if ($this->id == 0)
		{
			warning('Attempted to list relations for uninitialized entity of type '. get_class($this), 1);
			return $relations;
		}

		global $db;

		if ($direction & RELATION_OUTBOUND)
		{
			$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE c_id_1 = ? AND cr_flags & ? = ?');
			$q->bindIntValue(1, $this->id);
			$q->bindIntValue(2, $checkflags);
			$q->bindIntValue(3, $needflags);

			if (!$q->execute())
			{
				warning('Failed to fetch outward relations for clan '. $this, 1);
				return NULL;
			}
			while ($row = $q->fetch())
			{
				if ($full)
					$relations[$row['c_id_2']] = $row;
				else	$relations[] = $row['c_id_2'];
			}
		}

		if ($direction & RELATION_INBOUND)
		{
			$q = $db->prepare('SELECT * FROM '. CLAN_RELATION_TABLE .' WHERE c_id_2 = ? AND cr_flags & ? = ?');
			$q->bindIntValue(1, $this->id);
			$q->bindIntValue(2, $checkflags);
			$q->bindIntValue(3, $needflags);

			if (!$q->execute())
			{
				warning('Failed to fetch inward relations for clan '. $this, 1);
				return NULL;
			}
			while ($row = $q->fetch())
			{
				if ($full)
					$relations[$row['c_id_1']] = $row;
				else	$relations[] = $row['c_id_1'];
			}
		}

		return $relations;
	}

	// Shortcut function for listing all active alliances
	public function getAllies ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to list allies for uninitialized entity of type '. get_class($this), 1);
			return array();
		}

		return $this->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY | CRFLAG_MUTUAL);
	}

	// Shortcut function for listing all active wars - ones that have been declared,
	// and ones that where peace has been requested (by the originator) but not yet accepted
	public function getWars ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to list wars for uninitialized entity of type '. get_class($this), 1);
			return array();
		}

		$war1 = $this->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL);
		$war2 = $this->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR, RELATION_INBOUND);
		return array_merge($war1, $war2);
	}

	// Set the clan's password
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

		$this->c_password = enc_password($password);
		return TRUE;
	}

	// Verifies the clan's password
	// Specifying 2nd parameter makes it only attempt conversion
	public function checkPassword ($password, $convert_only = FALSE)
	{
		if ($this->id == 0)
			return FALSE;
		// handle and convert old password hashes
		// previously just used MD5 because it's a shared password and thus not very important
		if ($this->c_password == md5($password))
		{
			if ($this->locked())
				$this->setPassword($password);
			return TRUE;
		}
		if ($convert_only)
			return FALSE;
		return chk_password($password, $this->c_password);
	}

	// Formats clan name+number as a string
	public function __toString ()
	{
		if ($this->id == 0)
			return lang('COMMON_CLAN_NAMEID', 'COMMON_CLAN_UNINITIALIZED', prenum(0));
		return lang('COMMON_CLAN_NAMEID', $this->c_name, prenum($this->c_id));
	}

	// Loads all clan names into an array
	public static function getNames ()
	{
		global $db;
		$q = $db->query('SELECT c_id, c_name FROM '. CLAN_TABLE .' ORDER BY c_id ASC') or warning('Failed to retrieve list of clan names', 1);
	
		$names = array();
		$names[0] = lang('CLAN_NONE');
		while ($c = $q->fetch())
			$names[$c['c_id']] = $c['c_name'];
	
		return $names;
	}

	// Loads a clan partially, attempting to cache it where possible
	// Specify ID of 0 to flush cache
	public static function cached_load ($id)
	{
		static $cache = array();
		if ($id == 0)
		{
			$cache = array();
			return NULL;
		}
		if (!isset($cache[$id]))
		{
			$clan = new prom_clan($id);
			$clan->loadPartial();
			$cache[$id] = $clan;
		}
		return $cache[$id];
	}
} // class prom_clan
?>
