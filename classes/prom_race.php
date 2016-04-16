<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_race.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_static.php');

define('RACE_HUMAN', 1);
define('RACE_ELF', 2);
define('RACE_DWARF', 3);
define('RACE_TROLL', 4);
define('RACE_GNOME', 5);
define('RACE_GREMLIN', 6);
define('RACE_ORC', 7);
define('RACE_DROW', 8);
define('RACE_GOBLIN', 9);

class prom_race extends prom_static
{
	protected static $races = array(
		RACE_HUMAN => array(
			'name'		=> 'RACE_HUMAN',
			'mod_offense'	=> 0,
			'mod_defense'	=> 0,
			'mod_buildrate'	=> 0,
			'mod_expenses'	=> 0,
			'mod_magic'	=> 0,
			'mod_industry'	=> 0,
			'mod_income'	=> 0,
			'mod_explore'	=> 0,
			'mod_market'	=> 0,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> 0,
			'mod_foodpro'	=> 0),
		RACE_ELF => array(
			'name'		=> 'RACE_ELF',
			'mod_offense'	=> -14,
			'mod_defense'	=> -2,
			'mod_buildrate'	=> -10,
			'mod_expenses'	=> 0,
			'mod_magic'	=> 18,
			'mod_industry'	=> -12,
			'mod_income'	=> 2,
			'mod_explore'	=> 12,
			'mod_market'	=> 0,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> 12,
			'mod_foodpro'	=> -6),
		RACE_DWARF => array(
			'name'		=> 'RACE_DWARF',
			'mod_offense'	=> 6,
			'mod_defense'	=> 16,
			'mod_buildrate'	=> 16,
			'mod_expenses'	=> -8,
			'mod_magic'	=> -16,
			'mod_industry'	=> 12,
			'mod_income'	=> 0,
			'mod_explore'	=> -18,
			'mod_market'	=> -8,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> 0,
			'mod_foodpro'	=> 0),
		RACE_TROLL => array(
			'name'		=> 'RACE_TROLL',
			'mod_offense'	=> 24,
			'mod_defense'	=> -10,
			'mod_buildrate'	=> 8,
			'mod_expenses'	=> 0,
			'mod_magic'	=> -12,
			'mod_industry'	=> 0,
			'mod_income'	=> 4,
			'mod_explore'	=> 14,
			'mod_market'	=> -12,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> -8,
			'mod_foodpro'	=> -8),
		RACE_GNOME => array(
			'name'		=> 'RACE_GNOME',
			'mod_offense'	=> -16,
			'mod_defense'	=> 10,
			'mod_buildrate'	=> 0,
			'mod_expenses'	=> 6,
			'mod_magic'	=> 0,
			'mod_industry'	=> -10,
			'mod_income'	=> 10,
			'mod_explore'	=> -12,
			'mod_market'	=> 24,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> -12,
			'mod_foodpro'	=> 0),
		RACE_GREMLIN => array(
			'name'		=> 'RACE_GREMLIN',
			'mod_offense'	=> 10,
			'mod_defense'	=> -6,
			'mod_buildrate'	=> 0,
			'mod_expenses'	=> 0,
			'mod_magic'	=> -10,
			'mod_industry'	=> -14,
			'mod_income'	=> -20,
			'mod_explore'	=> 0,
			'mod_market'	=> 8,
			'mod_foodcon'	=> 14,
			'mod_runepro'	=> 0,
			'mod_foodpro'	=> 18),
		RACE_ORC => array(
			'name'		=> 'RACE_ORC',
			'mod_offense'	=> 16,
			'mod_defense'	=> 0,
			'mod_buildrate'	=> 4,
			'mod_expenses'	=> -14,
			'mod_magic'	=> -4,
			'mod_industry'	=> 8,
			'mod_income'	=> 0,
			'mod_explore'	=> 22,
			'mod_market'	=> 0,
			'mod_foodcon'	=> -10,
			'mod_runepro'	=> -14,
			'mod_foodpro'	=> -8),
		RACE_DROW => array(
			'name'		=> 'RACE_DROW',
			'mod_offense'	=> 14,
			'mod_defense'	=> 6,
			'mod_buildrate'	=> -12,
			'mod_expenses'	=> -10,
			'mod_magic'	=> 18,
			'mod_industry'	=> 0,
			'mod_income'	=> 0,
			'mod_explore'	=> -16,
			'mod_market'	=> 0,
			'mod_foodcon'	=> 0,
			'mod_runepro'	=> 6,
			'mod_foodpro'	=> -6),
		RACE_GOBLIN => array(
			'name'		=> 'RACE_GOBLIN',
			'mod_offense'	=> -18,
			'mod_defense'	=> -16,
			'mod_buildrate'	=> 0,
			'mod_expenses'	=> 18,
			'mod_magic'	=> 0,
			'mod_industry'	=> 14,
			'mod_income'	=> 0,
			'mod_explore'	=> 0,
			'mod_market'	=> -6,
			'mod_foodcon'	=> 8,
			'mod_runepro'	=> 0,
			'mod_foodpro'	=> 0),
	);

	// Loads variables
	public function load ()
	{
		if (!self::exists($this->id))
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to locate data', 1);
			return FALSE;
		}

		$this->data = self::$races[$this->id];
		return TRUE;
	}

	// By default, retrieve race's name
	public function __toString ()
	{
		if ($this->id == 0)
			return lang('RACE_UNKNOWN');
		return lang($this->name);
	}

	public static function exists ($id)
	{
		return isset(self::$races[$id]);
	}

	// Loads all race names into an associative array keyed by race ID
	public static function getNames ()
	{
		$names = array();
		foreach (self::$races as $id => $race)
			$names[$id] = lang($race['name']);
		return $names;
	}

	// Provided mainly for round history, which needs to resolve and cache only the race names
	public static function lookup ($race, $data)
	{
		if (!self::exists($race))
			return;
		if (!isset(self::$races[$race][$data]))
			return;
		return self::$races[$race][$data];
	}
} // class prom_race
?>
