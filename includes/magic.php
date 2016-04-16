<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: magic.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_spell.php');

// Dummy spell, returned in case of invalid selection
class prom_spell_invalid extends prom_spell
{
}

// news event ID is used as index so that news search can look up the spell name quickly
$spells = array();
$spells[EMPNEWS_MAGIC_SPY] = 'spy';
$spells[EMPNEWS_MAGIC_BLAST] = 'blast';
$spells[EMPNEWS_MAGIC_SHIELD] = 'shield';
$spells[EMPNEWS_MAGIC_STORM] = 'storm';
$spells[EMPNEWS_MAGIC_RUNES] = 'runes';
$spells[EMPNEWS_MAGIC_STRUCT] = 'struct';
$spells[EMPNEWS_MAGIC_FOOD] = 'food';
$spells[EMPNEWS_MAGIC_CASH] = 'cash';
$spells[EMPNEWS_MAGIC_GATE] = 'gate';
$spells[EMPNEWS_MAGIC_UNGATE] = 'ungate';
$spells[EMPNEWS_MAGIC_FIGHT] = 'fight';
$spells[EMPNEWS_MAGIC_STEAL] = 'steal';
$spells[EMPNEWS_MAGIC_ADVANCE] = 'advance';
if (MAGIC_ALLOW_REGRESS)
	$spells[EMPNEWS_MAGIC_REGRESS] = 'regress';

// spells themselves are located in individual files
function loadSpells ()
{
	global $spells;
	foreach ($spells as $spellname)
		require_once(PROM_BASEDIR .'spells/'. $spellname .'.php');	
}

function getSpell ($name, $self = NULL, $other = NULL)
{
	global $spells;
	if (in_array($name, $spells))
	{
		$classname = 'prom_spell_'. $name;
		return new $classname($self, $other);
	}
	else	return new spell_invalid();
}
?>
