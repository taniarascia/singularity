<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_empire_effects.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');

// All effect names must be prefixed with one of these distinct strings
define('EMPIRE_EFFECT_TIME', 'm_');
define('EMPIRE_EFFECT_TURN', 'r_');
define('EMPIRE_EFFECT_PERM', 'p_');

class prom_empire_effects extends prom_entity
{
	protected $self;
	protected $modifiers;
	protected static $effectmods = array(
//		'r_sample' => array(
//			'mod_income'  => 10,
//			'mod_foodpro' => 10,
//		),
	);

	protected static $effectdescs = array(
		'r_newera' => array(
			'name' => 'EFFECT_NAME_NEWERA',
			'desc' => 'EFFECT_DESC_NEWERA',
		),
		'm_gate' => array(
			'name' => 'EFFECT_NAME_GATE',
			'desc' => 'EFFECT_DESC_GATE',
			'era_name' => 'effectname_gate',
		),
		'm_shield' => array(
			'name' => 'EFFECT_NAME_SHIELD',
			'desc' => 'EFFECT_DESC_SHIELD',
			'era_name' => 'effectname_shield',
			'era_desc' => 'effectdesc_shield',
		),
		'm_clan' => array(
			'name' => 'EFFECT_NAME_CLAN',
			'desc' => 'EFFECT_DESC_CLAN',
			'admin' => true,
		),
		'm_revalidate' => array(
			'name' => 'EFFECT_NAME_REVALIDATE',
			'desc' => 'EFFECT_DESC_REVALIDATE',
			'admin' => true,
		),
		'm_droptime' => array(
			'name' => 'EFFECT_NAME_DROPTIME',
			'desc' => 'EFFECT_DESC_DROPTIME',
			'admin' => true,
		),
		'm_sendaid' => array(
			'name' => 'EFFECT_NAME_SENDAID',
			'desc' => 'EFFECT_DESC_SENDAID',
			'admin' => true,
		),
		'm_message' => array(
			'name' => 'EFFECT_NAME_MESSAGE',
			'desc' => 'EFFECT_DESC_MESSAGE',
			'admin' => true,
		),
		'm_freeturns' => array(
			'name' => 'EFFECT_NAME_FREETURNS',
			'desc' => 'EFFECT_DESC_FREETURNS',
			'admin' => true,
		),
	);

	public function __construct($emp)
	{
		// effects table has special structure - multiple rows per entity
		// inherit ID from supplied empire, don't use database vars
		$this->self = $emp;
		parent::__construct($emp->e_id, FALSE, 'DUAL', 'NULL', 0);
		// automatically load
		$this->load();
	}

	// Loads empire effects from the database
	public function load ()
	{
		global $db;

		$q = $db->prepare('SELECT * FROM '. EMPIRE_EFFECT_TABLE .' WHERE e_id = ?');
		$q->bindIntValue(1, $this->id);
		if (!$q->execute())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to load from database', 1);
			return FALSE;
		}

		$data = $q->fetchAll();
		foreach ($data as $effect)
			$this->data[$effect['ef_name']] = $effect['ef_value'];

		$this->update = array();
		$this->getModifiers();
		return TRUE;
	}

	// Creates a new effect variable and inserts it into the database
	public function create ($name)
	{
		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to create new effect while not locked', 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('INSERT INTO '. EMPIRE_EFFECT_TABLE .' (e_id,ef_name) VALUES (?,?)');
		$q->bindIntValue(1, $this->id);
		$q->bindStrValue(2, $name);
		if (!$q->execute())
		{
			warning('Failed to create new effect variable "'. $name .'"', 1);
			return FALSE;
		}

		$this->data[$name] = 0;
		return TRUE;
	}

	// Empire effects are only locked if the empire owning them is locked
	public function locked ()
	{
		return $this->self->locked();
	}

	// Effects cannot be locked directly - throw a warning if something tries
	public function lock ()
	{
		warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to lock', 1);
		return FALSE;
	}

	// Magic function to allow checking for presence of data fields
	// Modified here to allow checking for modifiers
	public function __isset ($field)
	{
		return (isset($this->data[$field]) || isset($this->modifiers[$field]));
	}

	// Underlying function for retrieving effect durations
	// Time-based effects are made relative to the current time, and are trimmed to zero if they have expired
	// Turn-based effects are returned as-is
	// Modifiers can also be retrieved this way
	protected function _getData ($field)
	{
		if ($this->id == 0)
		{
			warning('Attempted to read from uninitialized entity of type '. get_class($this), 2);
			return FALSE;
		}

		if (EMPIRE_EFFECT_TIME == substr($field, 0, strlen(EMPIRE_EFFECT_TIME)))
		{
			if (isset($this->data[$field]))
				$value = $this->data[$field];
			else	$value = CUR_TIME;
			$value = $value - CUR_TIME;
			if ($value > 0)
				return $value;
			else	return 0;
		}
		elseif (EMPIRE_EFFECT_TURN == substr($field, 0, strlen(EMPIRE_EFFECT_TURN)))
		{
			if (isset($this->data[$field]))
				$value = $this->data[$field];
			else	$value = 0;
			return $value;
		}
		elseif (EMPIRE_EFFECT_PERM == substr($field, 0, strlen(EMPIRE_EFFECT_PERM)))
		{
			if (isset($this->data[$field]))
				$value = $this->data[$field];
			else	$value = 0;
			return $value;
		}
		elseif (isset($this->modifiers[$field]))
			return $this->modifiers[$field];
		else
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to read effect "'. $field .'" of unrecognized type', 2);
			return FALSE;
		}
	}

	// Underlying function for setting data fields
	// Time-based effects are treated as an offset relative to the current time
	// Turn-based effects are set as-is
	// Undefined effects are created automatically
	protected function _setData ($field, $value)
	{
		if ($this->id == 0)
		{
			warning('Attempted to write to uninitialized entity of type '. get_class($this), 2);
			return FALSE;
		}

		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to modify data field "'. $field .'" while not locked', 2);
			return FALSE;
		}

		if (EMPIRE_EFFECT_TIME == substr($field, 0, strlen(EMPIRE_EFFECT_TIME)))
		{
			if (!isset($this->data[$field]))
				$this->create($field);

			$this->update[] = $field;
			$this->data[$field] = $value + CUR_TIME;
			$this->getModifiers();
			return TRUE;
		}
		elseif (EMPIRE_EFFECT_TURN == substr($field, 0, strlen(EMPIRE_EFFECT_TURN)))
		{
			if (!isset($this->data[$field]))
				$this->create($field);

			$this->update[] = $field;
			$this->data[$field] = $value;
			$this->getModifiers();
			return TRUE;
		}
		elseif (EMPIRE_EFFECT_PERM == substr($field, 0, strlen(EMPIRE_EFFECT_PERM)))
		{
			if (!isset($this->data[$field]))
				$this->create($field);

			$this->update[] = $field;
			$this->data[$field] = $value;
			$this->getModifiers();
			return TRUE;
		}
		else
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to modify effect "'. $field .'" of unrecognized type', 2);
			return FALSE;
		}
	}

	// Apply modifiers based on all active effects
	protected function getModifiers ()
	{
		$this->modifiers = array();
		foreach (self::$effectmods as $name => $data)
		{
			if (!$this->$name)
				continue;
			foreach ($data as $mod => $value)
			{
				if (!isset($this->modifiers[$mod]))
					$this->modifiers[$mod] = 0;
				$this->modifiers[$mod] += $value;
			}
		}
	}

	// Subtracts one turn from every active turn-based effect
	public function takeTurn ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to write to uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to consume turn effects while not locked', 1);
			return FALSE;
		}

		foreach ($this->data as $field => $value)
		{
			if (EMPIRE_EFFECT_TURN != substr($field, 0, strlen(EMPIRE_EFFECT_TURN)))
				continue;
			if ($value > 0)
			{
				$this->update[] = $field;
				$this->data[$field] = $value - 1;
			}
		}
		$this->getModifiers();
	}

	// Saves empire effects back to the database
	public function save ()
	{
		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to save while not locked', 1);
			return FALSE;
		}
		// is there anything to update?
		if (count($this->update) == 0)
			return TRUE;
		$this->update = array_unique($this->update);

		global $db;

		$q = $db->prepare('UPDATE '. EMPIRE_EFFECT_TABLE .' SET ef_value = ? WHERE e_id = ? AND ef_name = ?');
		$q->bindIntValue(2, $this->id);
		foreach ($this->update as $field)
		{
			$q->bindIntValue(1, $this->data[$field]);
			$q->bindStrValue(3, $field);
			if (!$q->execute())
			{
				// if even a single effect fails to save, then return failure
				warning('Entity '. $this->id .' of type '. get_class($this) .' failed to save effect "'. $field .'" to database', 1);
				return FALSE;
			}
		}

		$this->update = array();
		return TRUE;
	}

	public function getDescriptions($effects = NULL)
	{
		global $user1;
		$descs = array();
		if (!isset($user1))
		{
			warning('Attempted to read empire effects without user context', 1);
			return $descs;
		}
		if ($this->id == 0)
		{
			warning('Attempted to read from uninitialized entity of type '. get_class($this), 1);
			return $descs;
		}
		if ($effects == NULL)
			$effects = array_keys($this->data);
		foreach ($effects as $id)
		{
			$show = false;
			if ($user1->u_flags & UFLAG_ADMIN)
				// Administrators see EVERY effect, listed or not
				$show = true;
			elseif ($user1->u_flags & UFLAG_MOD)
			{
				// Moderators see all effects in the list above
				if (isset(self::$effectdescs[$id]))
					$show = true;
			}
			else
			{
				// Normal users only see effects not marked as 'admin'
				if (isset(self::$effectdescs[$id]) && !isset(self::$effectdescs[$id]['admin']))
					$show = true;
			}
			if (!$show)
				continue;

			$sortkey = array(-1, -1);
			if (EMPIRE_EFFECT_TIME == substr($id, 0, strlen(EMPIRE_EFFECT_TIME)))
			{
				if ($this->data[$id] <= CUR_TIME)
					continue;
				$duration = duration($this->data[$id] - CUR_TIME);
				// timed effects come second
				$sortkey = array(1, $this->data[$id] - CUR_TIME);
			}
			elseif (EMPIRE_EFFECT_TURN == substr($id, 0, strlen(EMPIRE_EFFECT_TURN)))
			{
				if ($this->data[$id] <= 0)
					continue;
				$duration = plural($this->data[$id], 'TURNS_SINGLE', 'TURNS_PLURAL');
				// turn-based effects come third
				$sortkey = array(2, $this->data[$id]);
			}
			elseif (EMPIRE_EFFECT_PERM == substr($id, 0, strlen(EMPIRE_EFFECT_PERM)))
			{
				$duration = lang('EFFECT_PERMANENT');
				// permanent effects come first
				$sortkey = array(0, 0);
			}
			else
			{
				warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to describe effect "'. $id .'" of unrecognized type', 1);
				continue;
			}

			if (isset(self::$effectdescs[$id]['era_name']))
				$name = lang($this->self->era->getData(self::$effectdescs[$id]['era_name']));
			elseif (isset(self::$effectdescs[$id]['name']))
				$name = lang(self::$effectdescs[$id]['name']);
			else	$name = $id;

			if (isset(self::$effectdescs[$id]['era_desc']))
				$desc = lang($this->self->era->getData(self::$effectdescs[$id]['era_desc']));
			elseif (isset(self::$effectdescs[$id]['desc']))
				$desc = lang(self::$effectdescs[$id]['desc']);
			else	$desc = '';

			$descs[$id] = array('duration' => $duration, 'name' => $name, 'desc' => $desc, 'sortkey' => $sortkey);
		}
		uasort($descs, function($a, $b) {
			$sk1 = $a['sortkey'];
			$sk2 = $b['sortkey'];
			if ($sk1[0] != $sk2[0])
				return $sk1[0] - $sk2[0];
			// sort descending by duration remaining
			return $sk2[1] - $sk1[1];
		});
		return $descs;
	}
} // class prom_empire_effects
?>
