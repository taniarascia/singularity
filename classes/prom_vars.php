<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_vars.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');

class prom_vars extends prom_entity
{
	public function __construct()
	{
		// variables table has special structure - no ID column
		// set ID to 1, and define dummy table/column names
		parent::__construct(1, 'DUAL', 'NULL', ENT_VARS);
	}

	// Loads variables from the database
	public function load ()
	{
		global $db;

		$q = $db->query('SELECT * FROM '. VAR_TABLE);
		if (!$q)
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to load from database', 1);
			return FALSE;
		}

		$data = $q->fetchAll();
		foreach ($data as $var)
			$this->data[$var['v_name']] = $var['v_value'];

		$this->update = array();
		return TRUE;
	}

	// Creates a new world variable and inserts it into the database
	public function create ($name)
	{
		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to create new variable while not locked', 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('INSERT INTO '. VAR_TABLE .' (v_name) VALUES (?)');
		$q->bindStrValue(1, $name);
		if (!$q->execute())
		{
			warning('Failed to create new world variable "'. $name .'"', 1);
			return FALSE;
		}

		$this->data[$name] = '';
		return TRUE;
	}

	// Enqueues an adjustment for a numeric world variable
	public function adjust ($name, $offset)
	{
		global $db;

		$q = $db->prepare('INSERT INTO '. VAR_ADJUST_TABLE .' (v_name,v_offset) VALUES (?,?)');
		$q->bindStrValue(1, $name);
		$q->bindIntValue(2, $offset);
		if (!$q->execute())
		{
			warning('Failed to enqueue adjustment for world variable "'. $name .'"', 1);
			return FALSE;
		}
		return TRUE;
	}

	// Saves world variables back to the database
	public function save ()
	{
		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to save while not locked', 1);
			return FALSE;
		}
		// if there are no fields to update, simply return
		if (count($this->update) == 0)
			return TRUE;
		$this->update = array_unique($this->update);

		global $db;

		$q = $db->prepare('UPDATE '. VAR_TABLE .' SET v_value = ? WHERE v_name = ?');
		foreach ($this->update as $field)
		{
			$q->bindStrValue(2, $field);
			if (is_numeric($this->data[$field]))
				$q->bindIntValue(1, $this->data[$field]);
			else	$q->bindStrValue(1, $this->data[$field]);
			if (!$q->execute())
			{
				// if even a single variable fails to save, then return failure
				warning('Entity '. $this->id .' of type '. get_class($this) .' failed to save variable "'. $field .'" to database', 1);
				return FALSE;
			}
		}

		$this->update = array();
		return TRUE;
	}

	// Verify that all required world variables have been created
	public function check ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to perform check on uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}
		global $required_vars;
		foreach ($required_vars as $field)
			if (!isset($this->$field))
				return FALSE;
		return TRUE;
	}
} // class prom_vars
?>
