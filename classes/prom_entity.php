<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_entity.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'includes/database.php');

abstract class prom_entity implements IteratorAggregate
{
	protected $db_table;	// Database table name
	protected $db_id;	// Database table primary key column
	protected $db_type;	// Database lock table entity type ID

	protected $id;		// entity ID for internal tracking

	protected $data;	// __get and __set will automatically look into this - use getData/setData otherwise

	protected $update;	// list of fields within $data that have been modified

	// Constructor - initializes entity with a specific ID
	protected function __construct($id, $db_table, $db_id, $db_type)
	{
		$this->id = $id;
		$this->db_table = $db_table;
		$this->db_id = $db_id;
		$this->db_type = $db_type;

		$this->data = array();
		$this->update = array();
	}

	// Query whether the entity has been initialized with proper data
	public function isLoaded ()
	{
		return (count($this->data) != 0);
	}

	// Load data into entity
	public function initdata ($data)
	{
		if ($this->id == 0)
		{
			warning('Attempted to load data into uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		if (!$data)
			return FALSE;

		$this->data = $data;
		$this->update = array();
		return TRUE;
	}

	// Fetch entity data from the database
	public function load ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to load uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('SELECT * FROM '. $this->db_table .' WHERE '. $this->db_id .' = ?');
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

	public function getType ()
	{
		return $this->db_type;
	}

	// Query whether or not the current record is locked
	public function locked ()
	{
		global $db;
		return $db->queryLock($this->db_type, $this->id);
	}

	// Request that the entity be locked
	public function lock ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to lock uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		// if the entity has already been locked, then ignore this
		if ($this->locked())
			return TRUE;

		global $db;
		// if it hasn't, though, then complain loudly that it's too late for that sort of thing
		if ($db->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' requested lock after locking phase began', 1);
			return FALSE;
		}

		// otherwise, request the lock
		$db->lockSingle($this->db_type, $this->id);
		return TRUE;
	}

	// Magic function to allow retrieving data values directly
	public function __get ($field)
	{
		return $this->_getData($field);
	}

	// Magic function to allow modifying data values directly
	// and automatically including them in the list of fields to synch to the database
	public function __set ($field, $value)
	{
		$this->_setData($field, $value);
	}

	// Magic function to allow checking for presence of data fields
	public function __isset ($field)
	{
		return isset($this->data[$field]);
	}

	// Under no circumstances should a data field ever be unset
	// Display a warning if anything tries to do so
	public function __unset ($field)
	{
		warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to unset data field "'. $field .'"', 1);
	}

	// Legacy function for getting data fields
	public function getData ($field)
	{
		return $this->_getData($field);
	}

	// Legacy function for setting data fields
	public function setData ($field, $value)
	{
		return $this->_setData($field, $value);
	}

	// Underlying function for getting data fields
	protected function _getData ($field)
	{
		if ($this->id == 0)
		{
			warning('Attempted to read from uninitialized entity of type '. get_class($this), 2);
			return FALSE;
		}

		if (!isset($this->data[$field]))
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to retrieve nonexistant data field "'. $field .'"', 2);
			return NULL;
		}

		return $this->data[$field];
	}

	// Underlying function for setting data fields
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

		if (!isset($this->data[$field]))
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to modify nonexistant data field "'. $field .'"', 2);
			return FALSE;
		}

		$this->update[] = $field;
		$this->data[$field] = $value;
		return TRUE;
	}

	// Saves an entity's data back to the database
	public function save ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to save uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}
		if (!$this->locked())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' attempted to save while not locked', 1);
			return FALSE;
		}
		// if there are no fields to update, simply return
		if (count($this->update) == 0)
			return TRUE;

		$this->update = array_unique($this->update);

		$update_fields = array();
		$update_values = array();
		foreach ($this->update as $field)
		{
			$update_fields[] = $field .'=?';
			$update_values[] = $this->data[$field];
		}
		$update_query = implode(',', $update_fields);

		global $db;

		$q = $db->prepare('UPDATE '. $this->db_table .' SET '. $update_query .' WHERE '. $this->db_id .' = ?');
		$update_values[] = $this->id;
		$q->bindAllValues($update_values);
		if (!$q->execute())
		{
			$this->update[] = $this->db_id;		// need this for proper error reporting
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to save to database', 1, var_export(array_combine($this->update, $update_values), true));
			return FALSE;
		}
		$this->update = array();
		return TRUE;
	}

	// Allows using foreach() to iterate across properties
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
} // abstract class prom_entity
?>
