<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_static.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');

abstract class prom_static extends prom_entity
{
	// Static entities only need an ID - database fields and lock-related fields are irrelevant
	// Also, they should automatically load their data immediately
	public function __construct ($id)
	{
		parent::__construct($id, 'DUAL', 'NULL', 0);
		$this->load();
	}

	// Static entities must reimplement this on their own
	public function load ()
	{
		warning('Static entity '. $this->id .' of type '. get_class($this) .' did not implement load()', 1);
		return FALSE;
	}

	// Static entities cannot be locked - throw a warning if something tries
	public function lock ()
	{
		warning('Static entity '. $this->id .' of type '. get_class($this) .' attempted to request lock', 1);
		return FALSE;
	}
	public function save ()
	{
		warning('Static entity '. $this->id .' of type '. get_class($this) .' attempted to save', 1);
		return FALSE;
	}
} // abstract class prom_static
?>
