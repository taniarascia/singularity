<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_era.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_static.php');

// These must be consecutive and in chronological order
define('ERA_PAST', 1);
define('ERA_PRESENT', 2);
define('ERA_FUTURE', 3);

class prom_era extends prom_static
{
	protected static $eras = array(
		ERA_PAST => array(
			'name'		=> 'ERA_PAST_NAME',
			'peasants'	=> 'ERA_PAST_PEASANTS',
			'food'		=> 'ERA_PAST_FOOD',
			'runes'		=> 'ERA_PAST_RUNES',
			'trparm'	=> 'ERA_PAST_TRPARM',
			'trplnd'	=> 'ERA_PAST_TRPLND',
			'trpfly'	=> 'ERA_PAST_TRPFLY',
			'trpsea'	=> 'ERA_PAST_TRPSEA',
			'trpwiz'	=> 'ERA_PAST_TRPWIZ',
			'bldpop'	=> 'ERA_PAST_BLDPOP',
			'bldcash'	=> 'ERA_PAST_BLDCASH',
			'bldtrp'	=> 'ERA_PAST_BLDTRP',
			'bldcost'	=> 'ERA_PAST_BLDCOST',
			'bldwiz'	=> 'ERA_PAST_BLDWIZ',
			'bldfood'	=> 'ERA_PAST_BLDFOOD',
			'blddef'	=> 'ERA_PAST_BLDDEF',
			'spell_spy'	=> 'ERA_PAST_SPELL_SPY',
			'spell_blast'	=> 'ERA_PAST_SPELL_BLAST',
			'spell_shield'	=> 'ERA_PAST_SPELL_SHIELD',
			'spell_storm'	=> 'ERA_PAST_SPELL_STORM',
			'spell_runes'	=> 'ERA_PAST_SPELL_RUNES',
			'spell_struct'	=> 'ERA_PAST_SPELL_STRUCT',
			'spell_food'	=> 'ERA_PAST_SPELL_FOOD',
			'spell_cash'	=> 'ERA_PAST_SPELL_CASH',
			'spell_gate'	=> 'ERA_PAST_SPELL_GATE',
			'spell_ungate'	=> 'ERA_PAST_SPELL_UNGATE',
			'spell_fight'	=> 'ERA_PAST_SPELL_FIGHT',
			'spell_steal'	=> 'ERA_PAST_SPELL_STEAL',
			'spell_advance'	=> 'ERA_PAST_SPELL_ADVANCE',
			'spell_regress'	=> 'ERA_PAST_SPELL_REGRESS',
			'effectname_shield'	=> 'ERA_PAST_EFFECT_NAME_SHIELD',
			'effectdesc_shield'	=> 'ERA_PAST_EFFECT_DESC_SHIELD',
			'effectname_gate'	=> 'ERA_PAST_EFFECT_NAME_GATE',
			'mod_explore'	=> 0,
			'mod_industry'	=> -5,
			'mod_runepro'	=> 20,
			'o_trparm' => 1, 'd_trparm' => 2,
			'o_trplnd' => 3, 'd_trplnd' => 2,
			'o_trpfly' => 7, 'd_trpfly' => 5,
			'o_trpsea' => 7, 'd_trpsea' => 6,
			'era_prev'	=> 0,
			'era_next'	=> ERA_PRESENT),
		ERA_PRESENT => array(
			'name'		=> 'ERA_PRESENT_NAME',
			'peasants'	=> 'ERA_PRESENT_PEASANTS',
			'food'		=> 'ERA_PRESENT_FOOD',
			'runes'		=> 'ERA_PRESENT_RUNES',
			'trparm'	=> 'ERA_PRESENT_TRPARM',
			'trplnd'	=> 'ERA_PRESENT_TRPLND',
			'trpfly'	=> 'ERA_PRESENT_TRPFLY',
			'trpsea'	=> 'ERA_PRESENT_TRPSEA',
			'trpwiz'	=> 'ERA_PRESENT_TRPWIZ',
			'bldpop'	=> 'ERA_PRESENT_BLDPOP',
			'bldcash'	=> 'ERA_PRESENT_BLDCASH',
			'bldtrp'	=> 'ERA_PRESENT_BLDTRP',
			'bldcost'	=> 'ERA_PRESENT_BLDCOST',
			'bldwiz'	=> 'ERA_PRESENT_BLDWIZ',
			'bldfood'	=> 'ERA_PRESENT_BLDFOOD',
			'blddef'	=> 'ERA_PRESENT_BLDDEF',
			'spell_spy'	=> 'ERA_PRESENT_SPELL_SPY',
			'spell_blast'	=> 'ERA_PRESENT_SPELL_BLAST',
			'spell_shield'	=> 'ERA_PRESENT_SPELL_SHIELD',
			'spell_storm'	=> 'ERA_PRESENT_SPELL_STORM',
			'spell_runes'	=> 'ERA_PRESENT_SPELL_RUNES',
			'spell_struct'	=> 'ERA_PRESENT_SPELL_STRUCT',
			'spell_food'	=> 'ERA_PRESENT_SPELL_FOOD',
			'spell_cash'	=> 'ERA_PRESENT_SPELL_CASH',
			'spell_gate'	=> 'ERA_PRESENT_SPELL_GATE',
			'spell_ungate'	=> 'ERA_PRESENT_SPELL_UNGATE',
			'spell_fight'	=> 'ERA_PRESENT_SPELL_FIGHT',
			'spell_steal'	=> 'ERA_PRESENT_SPELL_STEAL',
			'spell_advance'	=> 'ERA_PRESENT_SPELL_ADVANCE',
			'spell_regress'	=> 'ERA_PRESENT_SPELL_REGRESS',
			'effectname_shield'	=> 'ERA_PRESENT_EFFECT_NAME_SHIELD',
			'effectdesc_shield'	=> 'ERA_PRESENT_EFFECT_DESC_SHIELD',
			'effectname_gate'	=> 'ERA_PRESENT_EFFECT_NAME_GATE',
			'mod_explore'	=> 40,
			'mod_industry'	=> 0,
			'mod_runepro'	=> 0,
			'o_trparm' => 2, 'd_trparm' => 1,
			'o_trplnd' => 2, 'd_trplnd' => 6,
			'o_trpfly' => 5, 'd_trpfly' => 3,
			'o_trpsea' => 6, 'd_trpsea' => 8,
			'era_prev'	=> ERA_PAST,
			'era_next'	=> ERA_FUTURE),
		ERA_FUTURE => array(
			'name'		=> 'ERA_FUTURE_NAME',
			'peasants'	=> 'ERA_FUTURE_PEASANTS',
			'food'		=> 'ERA_FUTURE_FOOD',
			'runes'		=> 'ERA_FUTURE_RUNES',
			'trparm'	=> 'ERA_FUTURE_TRPARM',
			'trplnd'	=> 'ERA_FUTURE_TRPLND',
			'trpfly'	=> 'ERA_FUTURE_TRPFLY',
			'trpsea'	=> 'ERA_FUTURE_TRPSEA',
			'trpwiz'	=> 'ERA_FUTURE_TRPWIZ',
			'bldpop'	=> 'ERA_FUTURE_BLDPOP',
			'bldcash'	=> 'ERA_FUTURE_BLDCASH',
			'bldtrp'	=> 'ERA_FUTURE_BLDTRP',
			'bldcost'	=> 'ERA_FUTURE_BLDCOST',
			'bldwiz'	=> 'ERA_FUTURE_BLDWIZ',
			'bldfood'	=> 'ERA_FUTURE_BLDFOOD',
			'blddef'	=> 'ERA_FUTURE_BLDDEF',
			'spell_spy'	=> 'ERA_FUTURE_SPELL_SPY',
			'spell_blast'	=> 'ERA_FUTURE_SPELL_BLAST',
			'spell_shield'	=> 'ERA_FUTURE_SPELL_SHIELD',
			'spell_storm'	=> 'ERA_FUTURE_SPELL_STORM',
			'spell_runes'	=> 'ERA_FUTURE_SPELL_RUNES',
			'spell_struct'	=> 'ERA_FUTURE_SPELL_STRUCT',
			'spell_food'	=> 'ERA_FUTURE_SPELL_FOOD',
			'spell_cash'	=> 'ERA_FUTURE_SPELL_CASH',
			'spell_gate'	=> 'ERA_FUTURE_SPELL_GATE',
			'spell_ungate'	=> 'ERA_FUTURE_SPELL_UNGATE',
			'spell_fight'	=> 'ERA_FUTURE_SPELL_FIGHT',
			'spell_steal'	=> 'ERA_FUTURE_SPELL_STEAL',
			'spell_advance'	=> 'ERA_FUTURE_SPELL_ADVANCE',
			'spell_regress'	=> 'ERA_FUTURE_SPELL_REGRESS',
			'effectname_shield'	=> 'ERA_FUTURE_EFFECT_NAME_SHIELD',
			'effectdesc_shield'	=> 'ERA_FUTURE_EFFECT_DESC_SHIELD',
			'effectname_gate'	=> 'ERA_FUTURE_EFFECT_NAME_GATE',
			'mod_explore'	=> 80,
			'mod_industry'	=> 15,
			'mod_runepro'	=> 0,
			'o_trparm' => 1, 'd_trparm' => 2,
			'o_trplnd' => 5, 'd_trplnd' => 2,
			'o_trpfly' => 6, 'd_trpfly' => 3,
			'o_trpsea' => 7, 'd_trpsea' => 7,
			'era_prev'	=> ERA_PRESENT,
			'era_next'	=> 0)
	);

	// Loads variables
	public function load ()
	{
		if (!self::exists($this->id))
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to locate data', 1);
			return FALSE;
		}

		$this->data = self::$eras[$this->id];
		return TRUE;
	}

	// By default, retrieve era's name
	public function __toString ()
	{
		if ($this->id == 0)
			return lang('ERA_UNKNOWN');
		return lang($this->name);
	}

	public static function exists ($id)
	{
		return isset(self::$eras[$id]);
	}

	public static function getNames ()
	{
		$names = array();
		foreach (self::$eras as $id => $era)
			$names[$id] = lang($era['name']);
		return $names;
	}

	// Provided mainly for round history, which needs to resolve and cache only the era names
	public static function lookup ($era, $data)
	{
		if (!self::exists($era))
			return;
		if (!isset(self::$eras[$era][$data]))
			return;
		return self::$eras[$era][$data];
	}
} // class prom_era
?>
