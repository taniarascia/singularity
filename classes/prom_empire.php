<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: prom_empire.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'classes/prom_entity.php');
require_once(PROM_BASEDIR .'classes/prom_race.php');
require_once(PROM_BASEDIR .'classes/prom_era.php');
require_once(PROM_BASEDIR .'classes/prom_empire_effects.php');

define('TURNS_TROUBLE_CASH', 1);
define('TURNS_TROUBLE_LOAN', 2);
define('TURNS_TROUBLE_FOOD', 4);

class prom_empire extends prom_entity
{
	public $race;
	public $era;
	public $effects;

	// Constructor - initialize as Empire
	public function __construct($id = 0)
	{
		parent::__construct($id, EMPIRE_TABLE, 'e_id', ENT_EMPIRE);
	}

	// Creates a brand new empire record and inserts it into the database
	// Must be called using an uninitialized empire object
	// Parameter $user must be a locked user record
	public function create ($user, $name, $race)
	{
		if ($this->id != 0)
		{
			warning('Attempted to initialize already initialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		if (get_class($user) != 'prom_user')
		{
			warning('Attempted to initialize entity of type '. get_class($this) .' using invalid base entity of type '. get_class($user), 1);
			return FALSE;
		}

		if (!$user->locked())
		{
			warning('Attempted to initialize entity of type '. get_class($this) .' using unlocked base entity of type '. get_class($user), 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('INSERT INTO '. EMPIRE_TABLE .' (u_id) VALUES (?)');
		$q->bindIntValue(1, $user->u_id);
		if (!$q->execute())
		{
			warning('Failed to create empire for user '. $user, 1);
			return FALSE;
		}

		$this->id = $db->lastInsertId($db->getSequence(EMPIRE_TABLE));
		$db->createLock($this->db_type, $this->id);
		$this->load();

		// set various properties of the new empire
		$this->e_signupdate = CUR_TIME;
		$this->e_valcode = md5(uniqid(mt_rand(), TRUE));

		$this->e_name = $name;
		$this->e_race = $race;
		$this->race = new prom_race($this->e_race);
		$this->e_era = ERA_PAST;
		$this->era = new prom_race($this->e_era);
		// set rank to user ID, placing empire at the very bottom of the score list
		$this->e_rank = $this->e_id;

		$this->e_turns = TURNS_INITIAL;
		$this->e_idle = CUR_TIME;

		// Configurable empire default values
		global $empire_defaults;
		foreach ($empire_defaults as $key => $val)
			$this->setData($key, $val);

		// update user account's date of last signup
		$user->u_lastdate = CUR_TIME;

		// and set up the era change timer
		$this->effects->r_newera = TURNS_ERA;

		// create player 'notes' post
		$q = $db->prepare('INSERT INTO '. EMPIRE_MESSAGE_TABLE .' (m_id_ref,m_time,m_flags,e_id_src,e_id_dst,m_subject,m_body) VALUES (0,0,0,0,?,?,?)');
		$q->bindIntValue(1, $this->e_id);
		$q->bindStrValue(2, '');
		$q->bindStrValue(3, '');
		$q->execute() or warning('Failed to create notes post for empire '. $this, 1);

		return TRUE;
	}

	// Loads empire data from the database, and attaches appropriate race/era data (if valid)
	public function load ()
	{
		warning_wrap();	// Error logging kludge
		$result = parent::load();
		warning_unwrap();
		if (!$result)
			return FALSE;

		if (prom_race::exists($this->e_race))
			$this->race = new prom_race($this->e_race);
		if (prom_era::exists($this->e_era))
			$this->era = new prom_era($this->e_era);
		$this->effects = new prom_empire_effects($this);
		return TRUE;
	}

	// Only loads enough data to allow using as a news recipient, formatting as a string, and looking up race/era data
	// Effect data is NOT loaded here - if needed, it must be loaded manually
	public function loadPartial ()
	{
		if ($this->id == 0)
		{
			warning('Attempted to load uninitialized entity of type '. get_class($this), 1);
			return FALSE;
		}

		global $db;

		$q = $db->prepare('SELECT e_id,e_name,c_id,e_race,e_era FROM '. $this->db_table .' WHERE '. $this->db_id .' = ?');
		$q->bindIntValue(1, $this->id);
		if (!$q->execute())
		{
			warning('Entity '. $this->id .' of type '. get_class($this) .' failed to load from database', 1);
			return FALSE;
		}

		if (!$this->initdata($q->fetch()))
			return FALSE;

		$this->update = array();

		$this->race = new prom_race($this->e_race);
		$this->era = new prom_era($this->e_era);
		return TRUE;
	}

	// Shortcut function for incrementing data fields
	public function addData ($field, $offset)
	{
		return $this->_setData($field, $this->_getData($field) + $offset);
	}

	// Shortcut function for decrementing data fields
	public function subData ($field, $offset)
	{
		return $this->_setData($field, $this->_getData($field) - $offset);
	}

	// Shortcut for setting flags within "e_flags"
	public function setFlag ($flag)
	{
		// ignore if the flag is already set
		if ($this->_getData('e_flags') & $flag)
			return TRUE;
		return $this->_setData('e_flags', $this->_getData('e_flags') | $flag);
	}

	// Shortcut for clearing flags within "e_flags"
	public function clrFlag ($flag)
	{
		// ignore if the flag is already cleared
		if (!($this->_getData('e_flags') & $flag))
			return TRUE;
		return $this->_setData('e_flags', $this->_getData('e_flags') & ~$flag);
	}

	// Shortcut for recalculating the empire's networth - called in save() and in a few location pages
	public function updateNet ()
	{
		return $this->_setData('e_networth', $this->getNetworth());
	}

	// Saves an empire's data back to the database
	public function save ()
	{
		// need to redo these checks so we don't end up trying to
		// call updateNet() and save effects (when it clearly won't work)
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
		// if we're modified, then force a networth recalculation
		if (count($this->update))
			$this->updateNet();
		if ($this->effects != NULL)
			$this->effects->save();
		warning_wrap();	// Error logging kludge
		$result = parent::save();
		warning_unwrap();
		// if save manages to fail, disable the empire
		if (!$result)
		{
			global $db;
			$q = $db->prepare('UPDATE '. EMPIRE_TABLE .' SET e_flags = e_flags | ?, e_killedby = 0, e_reason = ? WHERE e_id = ?');
			$q->bindIntValue(1, EFLAG_DISABLE | EFLAG_NOTIFY);
			$q->bindStrValue(2, def_lang('DISABLED_SCRIPT_FAIL_SAVE_EMPIRE'));
			$q->bindIntValue(3, $this->e_id);
			$q->execute() or warning('Failed to auto-disable corrupted empire', 1);
		}
		return $result;
	}

	// BEGIN UTILITY FUNCTIONS

	// Calculates a modifier based on the empire's race, era, and effects
	public function getModifier ($name)
	{
		$name = 'mod_'. $name;
		$mod = 100;
		if (isset($this->race->$name))
			$mod += $this->race->$name;
		if (isset($this->era->$name))
			$mod += $this->era->$name;
		if (isset($this->effects->$name))
			$mod += $this->effects->$name;
		if ($mod < 0)
			$mod = 0;
		return $mod / 100;
	}

	// Calculates a modifier based on the empire's race, era, and effects
	// Converts penalties into dividers equivalent to bonuses
	// (e.g. +100% == *2, -100% = /2, +200% = *3, -200% = /3)
	public function getModifier2 ($name)
	{
		$name = 'mod_'. $name;
		$mod = 0;
		if (isset($this->race->$name))
			$mod += $this->race->$name;
		if (isset($this->era->$name))
			$mod += $this->era->$name;
		if (isset($this->effects->$name))
			$mod += $this->effects->$name;
		if ($mod < 0)
			return 100 / (100 - $mod);
		else	return ($mod + 100) / 100;
	}

	// Prints a status bar, usually at the top and bottom of each page
	public function printStatsBar ()
	{
?>
<table style="width:100%">
<tr class="era<?php echo $this->e_era; ?>" style="font-size:medium">
    <td class="ac"><a href="?location=messages"><?php
		if ($this->numNewMessages() > 0)
			echo '<b style="color:#2BCF4A">'. lang('STATBAR_NEWMAIL') .'</b>';
		else	echo lang('STATBAR_MAILBOX');
?></a></td>
    <td class="ac"><?php echo label('ROW_TURNS', number($this->e_turns)); ?></td>
    <td class="ac"><?php echo label('ROW_CASH', money($this->e_cash)); ?></td>
    <td class="ac"><?php echo label('ROW_LAND', number($this->e_land)); ?></td>
    <td class="ac"><?php echo label($this->era->runes, number($this->e_runes)); ?></td>
    <td class="ac"><?php echo label($this->era->food, number($this->e_food)); ?></td>
    <td class="ac"><?php echo label('ROW_HEALTH', percent($this->e_health)); ?></td>
    <td class="ac"><?php echo label('ROW_NETWORTH', money($this->e_networth)); ?></td></tr>
</table>
<?php
	}

	// Prints summary information about the empire (as used on Main page, and in Spy skill)
	public function printMainStats ()
	{
?>
<table style="width:75%">
<tr class="era<?php echo $this->e_era; ?>"><th colspan="3"><?php echo $this; ?></th></tr>
    <tr><td style="width:40%">
    <table class="empstatus" style="width:100%">
        <tr><th><?php echo lang('ROW_TURNS'); ?></th><td><?php echo lang('COMMON_TURNS_CURRENT_MAX', number($this->e_turns), number(TURNS_MAXIMUM)); ?></td></tr>
        <tr><th><?php echo lang('ROW_STOREDTURNS'); ?></th><td><?php echo lang('COMMON_TURNS_CURRENT_MAX', number($this->e_storedturns), number(TURNS_STORED)); ?></td></tr>
        <tr><th><?php echo lang('ROW_RANK'); ?></th><td><?php echo prenum($this->e_rank); ?></td></tr>
        <tr><th><?php echo lang($this->era->peasants); ?></th><td><?php echo number($this->e_peasants); ?></td></tr>
        <tr><th><?php echo lang('ROW_LANDACRES'); ?></th><td><?php echo number($this->e_land); ?></td></tr>
        <tr><th><?php echo lang('ROW_CASH'); ?></th><td><?php echo money($this->e_cash); ?></td></tr>
        <tr><th><?php echo lang($this->era->food); ?></th><td><?php echo number($this->e_food); ?></td></tr>
        <tr><th><?php echo lang($this->era->runes); ?></th><td><?php echo number($this->e_runes); ?></td></tr>
        <tr><th><?php echo lang('ROW_NETWORTH'); ?></th><td><?php echo money($this->e_networth); ?></td></tr>
    </table></td>
    <td style="width:20%"></td>
    <td style="width:40%">
    <table class="empstatus" style="width:100%">
        <tr><th><?php echo lang('COLUMN_ERA'); ?></th><td><?php echo $this->era; ?></td></tr>
        <tr><th><?php echo lang('COLUMN_RACE'); ?></th><td><?php echo $this->race; ?></td></tr>
        <tr><th><?php echo lang('ROW_HEALTH'); ?></th><td><?php echo percent($this->e_health); ?></td></tr>
        <tr><th><?php echo lang('ROW_TAX'); ?></th><td><?php echo percent($this->e_tax); ?></td></tr>
        <tr><th><?php echo lang($this->era->trparm); ?></th><td><?php echo number($this->e_trparm); ?></td></tr>
        <tr><th><?php echo lang($this->era->trplnd); ?></th><td><?php echo number($this->e_trplnd); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpfly); ?></th><td><?php echo number($this->e_trpfly); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpsea); ?></th><td><?php echo number($this->e_trpsea); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpwiz); ?></th><td><?php echo number($this->e_trpwiz); ?></td></tr>
    </table></td></tr>
</table>
<?php
	}

	// Calculates the offensive or defensive power of all available units of the specified types
	public function calcPower ($mode, $units = TRUE)
	{
		if ($units == TRUE)
			$units = lookup('list_mil');
		$power = 0;
		foreach ($units as $unit)
			$power += $this->calcUnitPower($this->getData('e_'. $unit), $unit, $mode);
		return $power;
	}

	// Calculates the offensive or defensive power for a particular quantity of a specified unit type
	public function calcUnitPower($quantity, $unit, $mode)
	{
		$type = $mode .'_'. $unit;
		return $this->era->getData($type) * $quantity;
	}

	// Calculates empire size bonus/penalty, mainly used for interest rates
	// Ranges from 0.5 to 1.7, rounded to 3 decimal places
	public function calcSizeBonus ()
	{
		$networth = max($this->e_networth, 1);	// must be 1 or greater
		$size = atan(log($networth, 1000) - 1) * 2.1 - 0.65;
		$size = round(min(max(0.5, $size), 1.7), 3);
/*
		if ($networth <= 100000)
			$size = 0.524;
		elseif ($networth <= 500000)
			$size = 0.887;
		elseif ($networth <= 1000000)
			$size = 1.145;
		elseif ($networth <= 10000000)
			$size = 1.294;
		elseif ($networth <= 100000000)
			$size = 1.454;
		else	$size = 1.674;
*/
		return $size;
	}

	// Calculates food production and consumption
	public function calcProvisions (&$production, &$consumption)
	{
		$production = (10 * $this->e_freeland) + ($this->e_bldfood * 85) * sqrt(1 - 0.75 * $this->e_bldfood / max($this->e_land, 1));
		$production *= $this->getModifier('foodpro');
		$production = round($production);

		$consumption = ($this->e_trparm * 0.05) + ($this->e_trplnd * 0.03) + ($this->e_trpfly * 0.02) + ($this->e_trpsea * 0.01) + ($this->e_peasants * 0.01) + ($this->e_trpwiz * 0.25);
		$consumption *= (2 - $this->getModifier('foodcon'));
		$consumption = round($consumption);
	}

	// Calculates income, expenses, and loan payment
	public function calcFinances (&$income, &$loan, &$expenses)
	{
		$income = round((($this->calcPCI() * ($this->e_tax / 100) * ($this->e_health / 100) * $this->e_peasants) + ($this->e_bldcash * 500)) / $this->calcSizeBonus());
		$loan = round($this->e_loan / 200);
		$expenses = round(($this->e_trparm * 1) + ($this->e_trplnd * 2.5) + ($this->e_trpfly * 4) + ($this->e_trpsea * 7) + ($this->e_land * 8) + ($this->e_trpwiz * 0.5));
		$expbonus = min(0.5, ($this->getModifier('expenses') - 1) + ($this->e_bldcost / max($this->e_land, 1)));
		$expenses -= round($expenses * $expbonus);
	}

	// Prints detailed empire status, as used on "Status" page
	public function printDetailedStats ()
	{
		global $cnames;

		$offpts = $this->calcPower('o');
		$defpts = $this->calcPower('d');
		if ($this->e_blddef > 0)
			$defpts += $this->e_blddef * 450 * min(1, $this->e_trparm / (150 * $this->e_blddef));

		$offpts = round($offpts * $this->getModifier('offense'));
		$defpts = round($defpts * $this->getModifier('defense'));

		$this->calcProvisions($foodpro, $foodcon);
		$foodnet = $foodpro - $foodcon;

		$wartax = 0;
		if (CLAN_ENABLE)
		{
			if ($this->c_id)
			{
				$clan_a = new prom_clan($this->c_id);

				$allies = $clan_a->getAllies();
				$wars = $clan_a->getWars();

				// passive war tax only counts outbound war declarations
				$wartax = ceil(count($clan_a->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL, RELATION_OUTBOUND)) * ($this->e_networth / 100));

				if (count($allies) == 0)
					$allies[] = 0;
				if (count($wars) == 0)
					$wars[] = 0;
				$clan_a = NULL;
			}
			else
			{
				$allies = array(0);
				$wars = array(0);
			}
		}

		$this->calcFinances($income, $loan, $expenses);
		$netincome = $income - $expenses - $loan;

		$savrate = BANK_SAVERATE - $this->calcSizeBonus();
		$loanrate = BANK_LOANRATE + $this->calcSizeBonus();
?>
<table style="width:100%">
<tr><th colspan="3" class="era<?php echo $this->e_era; ?>"><?php echo $this; ?></th></tr>
<tr><td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_EMPIRE_HEADER'); ?></th></tr>
        <tr><th><?php echo lang('ROW_TURNS'); ?></th>
            <td><?php echo lang('STATUS_TURNS_STORED', number($this->e_turns), number($this->e_storedturns)); ?></td></tr>
        <tr><th><?php echo lang('ROW_TURNSUSED'); ?></th>
            <td><?php echo number($this->e_turnsused); ?></td></tr>
        <tr><th><?php echo lang('ROW_HEALTH'); ?></th>
            <td><?php echo percent($this->e_health); ?></td></tr>
        <tr><th><?php echo lang('ROW_NETWORTH'); ?></th>
            <td><?php echo money($this->e_networth); ?></td></tr>
        <tr><th><?php echo lang('ROW_POPULATION'); ?></th>
            <td><?php echo number($this->e_peasants); ?></td></tr>
        <tr><th><?php echo lang('ROW_RACE'); ?></th>
            <td><?php echo $this->race; ?></td></tr>
        <tr><th><?php echo lang('ROW_ERA'); ?></th>
            <td><?php echo $this->era; ?></td></tr>
        </table>
    </td>
    <td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_FOOD_HEADER'); ?></th></tr>
        <tr><th><?php echo lang($this->era->food); ?></th>
            <td><?php echo number($this->e_food); ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOOD_PRODUCE'); ?></th>
            <td><?php echo number($foodpro); ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOOD_CONSUME'); ?></th>
            <td><?php echo number($foodcon); ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOOD_NET'); ?></th>
            <td><?php echo colornum($foodnet, number(abs($foodnet))); ?></td></tr>
        </table>
    </td>
    <td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_FOREIGN_HEADER'); ?></th></tr>
<?php		if (CLAN_ENABLE) { ?>
        <tr><th><?php echo lang('STATUS_FOREIGN_CLAN'); ?></th>
            <td><?php echo $cnames[$this->c_id]; ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOREIGN_ALLIES'); ?></th>
            <td><?php
		$ally = 0;
		foreach ($allies as $i)
		{
			if ($ally++ > 0)
				echo ', ';
			echo $cnames[$i];
		}
?></td></tr>
        <tr><th><?php echo lang('STATUS_FOREIGN_WARS'); ?></th>
            <td><?php
		$war = 0;
		foreach ($wars as $i)
		{
			if ($war++ > 0)
				echo ', ';
			echo $cnames[$i];
		}
?></td></tr>
        <tr><td colspan="2">&nbsp;</td></tr>
<?php		} ?>
        <tr><th><?php echo lang('STATUS_FOREIGN_OFFENSE'); ?></th>
            <td><?php echo lang('COMMON_NUMBER_PERCENT', $this->e_offtotal, percent($this->e_offsucc / max($this->e_offtotal, 1) * 100)); ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOREIGN_DEFENSE'); ?></th>
            <td><?php echo lang('COMMON_NUMBER_PERCENT', $this->e_deftotal, percent($this->e_defsucc / max($this->e_deftotal, 1) * 100)); ?></td></tr>
        <tr><th><?php echo lang('STATUS_FOREIGN_KILLS'); ?></th>
            <td><?php echo $this->e_kills; ?></td></tr>
        </table>
    </td>
</tr>
<tr><td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_LAND_HEADER'); ?></th></tr>
        <tr><th><?php echo lang($this->era->bldpop); ?></th>
            <td><?php echo number($this->e_bldpop); ?></td></tr>
        <tr><th><?php echo lang($this->era->bldcash); ?></th>
            <td><?php echo number($this->e_bldcash); ?></td></tr>
        <tr><th><?php echo lang($this->era->bldtrp); ?></th>
            <td><?php echo number($this->e_bldtrp); ?></td></tr>
        <tr><th><?php echo lang($this->era->bldcost); ?></th>
            <td><?php echo number($this->e_bldcost); ?></td></tr>
        <tr><th><?php echo lang($this->era->bldwiz); ?></th>
            <td><?php echo number($this->e_bldwiz); ?></td></tr>
        <tr><th><?php echo lang($this->era->bldfood); ?></th>
            <td><?php echo number($this->e_bldfood); ?></td></tr>
        <tr><th><?php echo lang($this->era->blddef); ?></th>
            <td><?php echo number($this->e_blddef); ?></td></tr>
        <tr><th><?php echo lang('STATUS_LAND_UNUSED'); ?></th>
            <td><?php echo number($this->e_freeland); ?></td></tr>
        <tr><th><?php echo lang('ROW_LANDACRES'); ?></th>
            <td><?php echo number($this->e_land); ?></td></tr>
        </table>
    </td>
    <td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_CASH_HEADER'); ?></th></tr>
        <tr><th><?php echo lang('ROW_CASH'); ?></th>
            <td><?php echo money($this->e_cash); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_PERCAP'); ?></th>
            <td><?php echo money($this->calcPCI()); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_INCOME'); ?></th>
            <td><?php echo money($income); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_EXPENSE'); ?></th>
            <td><?php echo money($expenses); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_LOANPAY'); ?></th>
            <td><?php echo money($loan); ?></td></tr>
<?php		if (CLAN_ENABLE) { ?>
        <tr><th><?php echo lang('STATUS_CASH_WARTAX'); ?></th>
            <td><?php echo money($wartax); ?></td></tr>
<?php		} else {?>
        <tr><td colspan="2">&nbsp;</td></tr>
<?php		} ?>
        <tr><th><?php echo lang('STATUS_CASH_NET'); ?></th>
            <td><?php echo colornum($netincome, money(abs($netincome))); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_SAVINGS'); ?></th>
            <td><?php echo lang('COMMON_MONEY_INTEREST', money($this->e_bank), percent($savrate, 3)); ?></td></tr>
        <tr><th><?php echo lang('STATUS_CASH_LOAN'); ?></th>
            <td><?php echo lang('COMMON_MONEY_INTEREST', money($this->e_loan), percent($loanrate, 3)); ?></td></tr>
        </table>
    </td>
    <td style="vertical-align:top;width:33%">
        <table class="empstatus" style="width:100%">
        <tr><th colspan="2" class="era<?php echo $this->e_era; ?>" style="text-align:center"><?php echo lang('STATUS_MILITARY_HEADER'); ?></th></tr>
        <tr><th><?php echo lang($this->era->trparm); ?></th>
            <td><?php echo number($this->e_trparm); ?></td></tr>
        <tr><th><?php echo lang($this->era->trplnd); ?></th>
            <td><?php echo number($this->e_trplnd); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpfly); ?></th>
            <td><?php echo number($this->e_trpfly); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpsea); ?></th>
            <td><?php echo number($this->e_trpsea); ?></td></tr>
        <tr><th><?php echo lang($this->era->trpwiz); ?></th>
            <td><?php echo number($this->e_trpwiz); ?></td></tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th><?php echo lang('STATUS_MILITARY_OFFPOWER'); ?></th>
            <td><?php echo number($offpts); ?></td></tr>
        <tr><th><?php echo lang('STATUS_MILITARY_DEFPOWER'); ?></th>
            <td><?php echo number($defpts); ?></td></tr>
        <tr><th><?php echo lang($this->era->runes); ?></th>
            <td><?php echo number($this->e_runes); ?></td></tr>
        </table>
    </td>
</tr>
</table>
<?php
	}

	// Returns number of unread messages in mailbox
	// Result is cached for the scope of a single page load
	public function numNewMessages ()
	{
		static $count = -1;

		if ($count == -1)
		{
			global $db;
			$count = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_MESSAGE_TABLE .' WHERE e_id_src != 0 AND e_id_dst = ? AND m_flags & ? = 0', array($this->e_id, MFLAG_DELETE | MFLAG_READ));
		}
		return $count;
	}

	// Returns number of messages in mailbox
	// Result is cached for the scope of a single page load
	public function numTotalMessages ()
	{
		static $count = -1;

		if ($count == -1)
		{
			global $db;
			$count = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_MESSAGE_TABLE .' WHERE e_id_src != 0 AND e_id_dst = ? AND m_flags & ? = 0', array($this->e_id, MFLAG_DELETE));
		}
		return $count;
	}

	// Calculates per capita income
	public function calcPCI ()
	{
		return round(25 * (1 + $this->e_bldcash / max($this->e_land, 1)) * $this->getModifier('income'));
	}

	// Calculates the empire's networth
	public function getNetworth ()
	{
		$net = 0;
		// Troops
		$net += $this->e_trparm * 1;
		$net += $this->e_trplnd * PVTM_TRPLND / PVTM_TRPARM;
		$net += $this->e_trpfly * PVTM_TRPFLY / PVTM_TRPARM;
		$net += $this->e_trpsea * PVTM_TRPSEA / PVTM_TRPARM;
		$net += $this->e_trpwiz * 2;
		$net += $this->e_peasants * 3;
		// Cash
		$net += ($this->e_cash + $this->e_bank / 2 - $this->e_loan * 2) / (5 * PVTM_TRPARM);
		$net += $this->e_land * 500;
		$net += $this->e_freeland * 100;
		// Food, reduced using logarithm to prevent it from boosting networth to ludicrous levels
		$net += $this->e_food / log(max(10, $this->e_food), 10) * (PVTM_FOOD / PVTM_TRPARM);
		return max(0, floor($net));
	}

	// Determines how much land to give to an empire during exploration
	public function give_land ()
	{
		return ceil((1 / ($this->e_land * 0.00022 + 0.25)) * 20 * $this->getModifier('explore'));
	}

	// Performs exploration
	public function do_explore ()
	{
		$land = $this->give_land();
		$this->e_land += $land;
		$this->e_freeland += $land;
		return $land;
	}

	// Take a specified number of turns performing the given action
	// Valid actions:
	// 'land' - explore for more land, put land gain in $turnresult
	// 'cash' - gain 25% more cash than usual, put net cash in $turnresult
	// 'farm' - gain 25% more food than usual, put net food in $turnresult
	// 'war' - attacking somebody you're at war with, incur 10% increased costs
	// All other actions are ignored, but may be used in the future for logging purposes
	// Returns the number of turns taken, negative if trouble was encountered
	// $turnresult, when specified, must be initialized to zero
	public function takeTurns ($numturns, $action, $interruptable = FALSE, $condensed = 0, &$turnresult = NULL)
	{
		if (in_array($action, array('land', 'cash', 'farm')) && !isset($turnresult))
		{
			warning('Failed to specify $turnresult', 1);
			return 0;
		}

		if ($numturns > $this->e_turns)
			return 0;

		$taken = 0;
		$deserted = 1;

		$overall = array();

		if (CLAN_ENABLE && $this->c_id)
		{
			$clan_a = new prom_clan($this->c_id);
			$wars = $clan_a->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL, RELATION_OUTBOUND);
		}

		while ($taken < $numturns)					// use up specified number of turns
		{
			$current = array();

			$taken++;
			$trouble = 0;
			$this->updateNet();

			if ($action == 'land')
				$turnresult += $this->do_explore();

			$size = $this->calcSizeBonus();		// size bonus/penalty

// savings interest
			$withdraw = 0;
			if (!$this->is_protected())
			{
				$bank_max = $this->e_networth * 100;
				if ($this->e_bank > $bank_max)
				{
					// if your savings account is above its limit, automatically withdraw the remainder
					$withdraw = $this->e_bank - $bank_max;
					$this->e_bank -= $withdraw;
					$this->e_cash += $withdraw;
				}
				else
				{
					// otherwise, earn interest up to the limit
					$saverate = BANK_SAVERATE - $size;
					$bank_interest = round($this->e_bank * ($saverate / 52 / 100));
					$this->e_bank = min($this->e_bank + $bank_interest, $bank_max);
				}
			}
			$current['withdraw'] = $withdraw;

// loan interest
			$loan_max = $this->e_networth * 50;
			$loanrate = BANK_LOANRATE + $size;			// update savings and loan
			$loan_interest = round($this->e_loan * ($loanrate / 52 / 100));
			$this->e_loan += $loan_interest;

// income/expenses/loan
			$this->calcFinances($income, $loan, $expenses);
			if ($action == 'cash')					// cashing?
				$income = round($income * 1.25);

// war tax
			$wartax = 0;
			if (CLAN_ENABLE && $this->c_id)
			{
				// passive war tax, applied for each clan you are at war with
				$wartax += count($wars) * ($this->e_networth / 100);

				// active war tax, applied when you attack somebody you're at war with
				if ($action == 'war')
					$wartax += $expenses / 10;
				$wartax = ceil($wartax);
			}

// net income
			$money = $income - ($expenses + $wartax);
			$this->e_cash += $money;

// handle loan separately
			if ($this->e_cash < 0)
				$trouble |= TURNS_TROUBLE_CASH;
// simply running out of money no longer halts turns; instead, it just adds it to your loan
// but if your loan exceeds the limit, then you're in trouble

// for emergencies, the loan limit is doubled (except during the final week, when loans are otherwise unavailable)
			$loan_emergency_limit = $loan_max * 2;
			if (ROUND_CLOSING)
				$loan_emergency_limit = $loan_max;
			if (($trouble & TURNS_TROUBLE_CASH) && ($this->e_loan > $loan_emergency_limit))
			{
				$trouble |= TURNS_TROUBLE_LOAN;
				$this->e_cash = 0;
				$loanpayed = 0;
			}	// if cash is negative, then the loan payment will also be negative
			else	$loanpayed = min(round($this->e_loan / 200), $this->e_cash);

			$this->e_cash -= $loanpayed;
			$this->e_loan -= $loanpayed;

// adjust net income
			$money -= $loanpayed;
			if ($action == 'cash')
				$turnresult += $money;

			$current['income'] = $income;
			$current['expenses'] = $expenses;
			$current['wartax'] = $wartax;
			$current['loanpayed'] = $loanpayed;
			$current['money'] = $money;

// industry
			$trparm = ceil(($this->e_bldtrp * ($this->e_indarm / 100)) * 1.2 * $this->getModifier('industry') * INDUSTRY_MULT);
			$trplnd = ceil(($this->e_bldtrp * ($this->e_indlnd / 100)) * 0.6 * $this->getModifier('industry') * INDUSTRY_MULT);
			$trpfly = ceil(($this->e_bldtrp * ($this->e_indfly / 100)) * 0.3 * $this->getModifier('industry') * INDUSTRY_MULT);
			$trpsea = ceil(($this->e_bldtrp * ($this->e_indsea / 100)) * 0.2 * $this->getModifier('industry') * INDUSTRY_MULT);

			$this->e_trparm += $trparm;
			$this->e_trplnd += $trplnd;
			$this->e_trpfly += $trpfly;
			$this->e_trpsea += $trpsea;

			$current['trparm'] = $trparm;
			$current['trplnd'] = $trplnd;
			$current['trpfly'] = $trpfly;
			$current['trpsea'] = $trpsea;

// update food
			$this->calcProvisions($foodpro, $foodcon);

			if ($action == 'farm')					// farming?
				$foodpro = round(1.25 * $foodpro);
			$food = $foodpro - $foodcon;
			$this->e_food += $food;
			if ($action == 'farm')
				$turnresult += $food;
			if ($this->e_food < 0)
			{
				$this->e_food = 0;
				$trouble |= TURNS_TROUBLE_FOOD;
			}
			$current['foodpro'] = $foodpro;
			$current['foodcon'] = $foodcon;
			$current['food'] = $food;

// health
			if ($this->e_health < 100 - max(($this->e_tax - 10) / 2, 0))
				$this->e_health++;

// update population
			$taxrate = $this->e_tax / 100;
			if ($taxrate > 0.40)
				$taxpenalty = ($taxrate - 0.40) / 2;
			elseif ($taxrate < 0.20)
				$taxpenalty = ($taxrate - 0.20) / 2;
			else	$taxpenalty = 0;

			$popbase = round((($this->e_land * 2) + ($this->e_freeland * 5) + ($this->e_bldpop * 60)) / (0.95 + $taxrate + $taxpenalty));

			$peasants = 0;
			$peasmult = 1;
			if ($this->e_peasants != $popbase)
				$peasants = ($popbase - $this->e_peasants) / 20;
			if ($peasants > 0)
				$peasmult = (4 / (($this->e_tax + 15) / 20)) - (7 / 9);
			if ($peasants < 0)
				$peasmult = 1 / ((4 / (($this->e_tax + 15) / 20)) - (7 / 9));
			$peasants = round($peasants * $peasmult * $peasmult);
			// don't let population reach zero
			if ($this->e_peasants + $peasants < 1)
				$peasants = 1 - $this->e_peasants;
			$this->e_peasants += $peasants;
			$current['peasants'] = $peasants;

// gain magic energy
			$runes = 0;
			if (($this->e_bldwiz / $this->e_land) > 0.15)
				$runes = mt_rand(round($this->e_bldwiz * 1.1), round($this->e_bldwiz * 1.5));
			else	$runes = round($this->e_bldwiz * 1.1);
			$runes = round($runes * $this->getModifier('runepro'));
			$this->e_runes += $runes;
			$current['runes'] = $runes;

// these values in the midst of adjustment
			$trpwiz = 0;
			if ($this->e_trpwiz < ($this->e_bldwiz * 25))
				$trpwiz = $this->e_bldwiz * 0.45;
			elseif ($this->e_trpwiz < ($this->e_bldwiz * 50))
				$trpwiz = $this->e_bldwiz * 0.30;
			elseif ($this->e_trpwiz < ($this->e_bldwiz * 90))
				$trpwiz = $this->e_bldwiz * 0.15;
			elseif ($this->e_trpwiz < ($this->e_bldwiz * 100))
				$trpwiz = $this->e_bldwiz * 0.10;
			elseif ($this->e_trpwiz > ($this->e_bldwiz * 175))	// above tests add wizards based on buildings
				$trpwiz = $this->e_trpwiz * -0.05;		// this one subtracts a direct percentage of wizards
			$trpwiz = round($trpwiz * sqrt(1 - $trpwiz / max(1, abs($trpwiz)) * 0.75 * $this->e_bldwiz / $this->e_land));
			$this->e_trpwiz += $trpwiz;
			$current['trpwiz'] = $trpwiz;

// update accumulated stats
			foreach ($current as $key => $val)
			{
				if (isset($overall[$key]))
					$overall[$key] += $val;
				else	$overall[$key] = $val;
			}

			if ((!$condensed) || ($taken == $numturns) || (($trouble & 6) && ($interruptable)))
			{
				if ($condensed)
					$stats = $overall;
				else	$stats = $current;

// print status report
				if ($stats['withdraw'] > 0) { ?>
<span class="cwarn"><?php echo lang('TURNS_WITHDRAW', money($stats['withdraw'])); ?></span><br />
<?php				} ?>
<table class="empstatus">
<tr><td style="vertical-align:top"><table>
    <tr class="inputtable"><th colspan="2"><?php echo lang('TURNS_CASH_HEADER'); ?></th></tr>
    <tr><th><?php echo lang('TURNS_CASH_INCOME'); ?></th>
        <td class="cneutral"><?php echo money($stats['income']); ?></td></tr>
    <tr><th><?php echo lang('TURNS_CASH_EXPENSE'); ?></th>
        <td class="cneutral"><?php echo money($stats['expenses']); ?></td></tr>
<?php				if ($stats['wartax']) { ?>
    <tr><th><?php echo lang('TURNS_CASH_WARTAX'); ?></th>
        <td class="cneutral"><?php echo money($stats['wartax']); ?></td></tr>
<?php				} ?>
<?php				if ($stats['loanpayed']) { ?>
    <tr><th><?php echo lang('TURNS_CASH_LOANPAY'); ?></th>
        <td><?php echo colornum($stats['loanpayed'], money(abs($stats['loanpayed'])), 'cwarn'); ?></td></tr>
<?php				} ?>
    <tr><th><?php echo lang('TURNS_CASH_NET'); ?></th>
        <td><?php echo colornum($stats['money'], money(abs($stats['money']))); ?></td></tr>
    </table></td>
    <td style="vertical-align:top"><table>
    <tr class="inputtable"><th colspan="2"><?php echo lang('TURNS_FOOD_HEADER'); ?></th></tr>
    <tr><th><?php echo lang('TURNS_FOOD_PRODUCE'); ?></th>
        <td class="cneutral"><?php echo number($stats['foodpro']); ?></td></tr>
    <tr><th><?php echo lang('TURNS_FOOD_CONSUME'); ?></th>
        <td class="cneutral"><?php echo number($stats['foodcon']); ?></td></tr>
    <tr><th><?php echo lang('TURNS_FOOD_NET'); ?></th>
        <td><?php echo colornum($stats['food'], number(abs($stats['food']))); ?></td></tr>
    </table></td>
    <td style="vertical-align:top"><table>
    <tr class="inputtable"><th colspan="2"><?php echo lang('TURNS_UNITS_HEADER'); ?></th></tr>
<?php				$printed = 0; ?>
<?php				if ($stats['peasants']) { $printed++; ?>
    <tr><th><?php echo label($this->era->peasants); ?></th>
        <td><?php echo colornum($stats['peasants'], number(abs($stats['peasants']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['trpwiz']) { $printed++; ?>
    <tr><th><?php echo label($this->era->trpwiz); ?></th>
        <td><?php echo colornum($stats['trpwiz'], number(abs($stats['trpwiz']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['runes']) { $printed++; ?>
    <tr><th><?php echo label($this->era->runes); ?></th>
        <td><?php echo colornum($stats['runes'], number(abs($stats['runes']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['trparm']) { $printed++; ?>
    <tr><th><?php echo label($this->era->trparm); ?></th>
        <td><?php echo colornum($stats['trparm'], number(abs($stats['trparm']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['trplnd']) { $printed++; ?>
    <tr><th><?php echo label($this->era->trplnd); ?></th>
        <td><?php echo colornum($stats['trplnd'], number(abs($stats['trplnd']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['trpfly']) { $printed++; ?>
    <tr><th><?php echo label($this->era->trpfly); ?></th>
        <td><?php echo colornum($stats['trpfly'], number(abs($stats['trpfly']))); ?></td></tr>
<?php				} ?>
<?php				if ($stats['trpsea']) { $printed++; ?>
    <tr><th><?php echo label($this->era->trpsea); ?></th>
        <td><?php echo colornum($stats['trpsea'], number(abs($stats['trpsea']))); ?></td></tr>
<?php				} ?>
<?php				if (!$printed) { ?>
    <tr><td colspan="2" class="ac"><?php echo lang('TURNS_UNITS_NOCHANGE'); ?></td></tr>
<?php				} ?>
    </table></td></tr>
</table>
<?php				if (($this->e_tax > 40) && ($stats['peasants'] < 0)) { ?>
<span class="cbad"><?php echo lang('TURNS_TAX_HIGH'); ?></span><br />
<?php				} elseif (($this->e_tax < 20) && ($stats['peasants'] > 0)) { ?>
<span class="cgood"><?php echo lang('TURNS_TAX_LOW'); ?></span><br />
<?php				} ?>
<?php				if ($trouble & TURNS_TROUBLE_CASH) { ?>
<span class="cwarn"><?php echo lang('TURNS_TROUBLE_CASH'); ?></span><br />
<?php				} ?>
<?php				if ($trouble & TURNS_TROUBLE_LOAN) { ?>
<span class="cbad"><?php echo lang('TURNS_TROUBLE_LOAN'); ?></span><br />
<?php				} ?>
<?php				if ($trouble & TURNS_TROUBLE_FOOD) { ?>
<span class="cbad"><?php echo lang('TURNS_TROUBLE_FOOD'); ?></span><br />
<?php				} ?>
<hr style="width:50%" />
<?php
			}
			$this->effects->takeTurn();
			$this->e_turnsused++;
			$this->e_turns--;
// only punish for refused loans or starvation
			if ($trouble & (TURNS_TROUBLE_LOAN | TURNS_TROUBLE_FOOD))
			{
				$this->e_peasants -= round($this->e_peasants * 0.03);
				$this->e_trparm -= round($this->e_trparm * 0.03);
				$this->e_trplnd -= round($this->e_trplnd * 0.03);
				$this->e_trpfly -= round($this->e_trpfly * 0.03);
				$this->e_trpsea -= round($this->e_trpsea * 0.03);
				$this->e_trpwiz -= round($this->e_trpwiz * 0.03);
				$deserted *= (1 - 0.03);
				if (!$interruptable) { ?>
<span class="cbad"><?php echo lang('TURNS_TROUBLE_LOSSES_CONTINUE', percent($condensed ? round((1 - $deserted) * 100) : 3)); ?></span><br />
<?php				} else { ?>
<span class="cbad"><?php echo lang('TURNS_TROUBLE_LOSSES_HALT', percent($condensed ? round((1 - $deserted) * 100) : 3)); ?></span><br />
<?php					break;
				}
			}
		}
		$clan_a = NULL;
		$this->updateNet();
		return $trouble ? -$taken : $taken;
	}

	// Checks news report for events granting items, then gives them to the player
	public function giveNews ()
	{
		global $db;
		$mkttype = lookup('pubmkt_id_name');

		$q = $db->prepare('UPDATE '. EMPIRE_NEWS_TABLE .' SET n_flags = n_flags | ? WHERE e_id_dst = ? AND n_flags & ? = 0 AND n_event BETWEEN ? AND ?');
		$q->bindIntValue(1, NFLAG_LOCK);
		$q->bindIntValue(2, $this->e_id);
		$q->bindIntValue(3, NFLAG_GOTTEN);
		$q->bindIntValue(4, EMPNEWS_ATTACH_FIRST);
		$q->bindIntValue(5, EMPNEWS_ATTACH_LAST);
		$q->execute() or warning("Failed to lock news entries for empire $this->id", 1);

		$q = $db->prepare('SELECT n_time, e_id_src, e1.e_name AS e_name_src, c_id_src, n_event, n_d0, n_d1, n_d2, n_d3, n_d4, n_d5, n_d6, n_d7, n_d8 FROM '. EMPIRE_NEWS_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' AS e1 ON (e_id_src = e1.e_id) WHERE e_id_dst = ? AND n_flags & ? = ? ORDER BY n_time ASC');
		$q->bindIntValue(1, $this->e_id);
		$q->bindIntValue(2, NFLAG_LOCK);
		$q->bindIntValue(3, NFLAG_LOCK);
		$q->execute() or warning("Failed to retrieve news for empire $this->id", 1);
		$news = $q->fetchAll();
		if (count($news) == 0)
			return 0;
		$newsdata = array();

		foreach ($news as $event)
		{
			$entry = array();
			$date = duration(CUR_TIME - $event['n_time'], 1, DURATION_HOURS);
			$entry['date'] = lang('EMPNEWS_DATE_FORMAT', $date);

			// create empire and populate the bare minimum, just enough for prom_empire::__toString() and addEmpireNews()
			if ($event['e_id_src'])
			{
				$other = new prom_empire($event['e_id_src']);
				$other->initdata(array('e_id' => $event['e_id_src'], 'e_name' => $event['e_name_src'], 'c_id' => $event['c_id_src']));
			}

			switch ($event['n_event'])
			{
			case EMPNEWS_ATTACH_MARKET_SELL:
				$this->e_cash += $event['n_d3'];
				$entry['class'] = 'cgood';
				$entry['gain'] = '+'. money($event['n_d3']);
				$entry['desc'] = lang('EMPNEWS_GIVE_MARKET_SELL', number($event['n_d1']), $this->era->getData($mkttype[$event['n_d0']]));
				$newsdata[] = $entry;
				break;
			case EMPNEWS_ATTACH_LOTTERY:
				$this->e_cash += $event['n_d0'];
				$entry['class'] = 'cgood';
				$entry['gain'] = '+'. money($event['n_d0']);
				$entry['desc'] = lang('EMPNEWS_GIVE_LOTTERY');
				$newsdata[] = $entry;
				break;
			case EMPNEWS_ATTACH_MARKET_RETURN:
				$this->addData('e_'. $mkttype[$event['n_d0']], $event['n_d3']);
				$entry['class'] = 'cwarn';
				$entry['gain'] = '+'. number($event['n_d3']) .' '. lang($this->era->getData($mkttype[$event['n_d0']]));
				$entry['desc'] = lang('EMPNEWS_GIVE_MARKET_RETURN', number($event['n_d1']), $this->era->getData($mkttype[$event['n_d0']]));
				$newsdata[] = $entry;
				break;
			case EMPNEWS_ATTACH_AID_SEND:
			case EMPNEWS_ATTACH_AID_SENDCLAN:
				if ($event['n_d4'] < $event['n_d0'])
				{
					$return = $event['n_d0'] - $event['n_d4'];
					$returned = min($return, $this->e_trpsea);
					$this->e_trpsea -= $returned;
					addEmpireNews(EMPNEWS_ATTACH_AID_RETURN, $this, $other, $return, $returned);
					if ($returned > 0)
					{
						$entry['class'] = 'cneutral';
						$entry['gain'] = '-'. number($returned) .' '. lang($this->era->trpsea);
						$entry['desc'] = lang('EMPNEWS_GIVE_AID_SEND', $other, $this->era->trpsea, number($returned));
						$newsdata[] = $entry;
					}
				}
				break;
			case EMPNEWS_ATTACH_AID_RETURN:
				$this->e_trpsea += $event['n_d1'];
				$entry['gain'] = '+'. number($event['n_d1']) .' '. lang($this->era->trpsea);
				if ($event['n_d0'] == $event['n_d1'])
				{
					$entry['class'] = 'cgood';
					$entry['desc'] = lang('EMPNEWS_GIVE_AID_RETURN_ALL', $this->era->trpsea);
				}
				else
				{
					$entry['class'] = 'cwarn';
					$entry['desc'] = lang('EMPNEWS_GIVE_AID_RETURN_SOME', number($event['n_d1']), number($event['n_d0']), $this->era->trpsea);
				}
				$newsdata[] = $entry;
				break;
			}
			$other = NULL;
		}
		if (count($newsdata) > 0)
		{
?>
<table class="inputtable" border="1">
<tr><th><?php echo lang('EMPNEWS_GIVE_COLUMN_DATE'); ?></th><th><?php echo lang('EMPNEWS_GIVE_COLUMN_GAIN'); ?></th><th><?php echo lang('EMPNEWS_GIVE_COLUMN_DESCRIBE'); ?></th></tr>
<?php
			foreach ($newsdata as $event)
			{
?>
<tr style="vertical-align:top"><th><?php echo $event['date']; ?></th>
    <td><span class="<?php echo $event['class']; ?>"><?php echo $event['gain']; ?></span></td><td><?php echo $event['desc']; ?></td></tr>
<?php
			}
?>
</table>
<?php
		}
		$q = $db->prepare('UPDATE '. EMPIRE_NEWS_TABLE .' SET n_flags = (n_flags | ?) & ? WHERE e_id_dst = ? AND n_flags & ? = ?');
		$q->bindIntValue(1, NFLAG_GOTTEN);
		$q->bindIntValue(2, ~NFLAG_LOCK);
		$q->bindIntValue(3, $this->e_id);
		$q->bindIntValue(4, NFLAG_LOCK);
		$q->bindIntValue(5, NFLAG_LOCK);
		$q->execute() or warning("Failed to unlock and mark news entries for empire $this->id", 1);
		return 1;
	}

	// Sends a validation email to the empire's owner
	public function sendValidationMail ($user)
	{
		if ($this->locked() && ($this->effects != NULL))
			$this->effects->m_revalidate = VALIDATE_RESEND;
		return prom_mail($user->u_email, lang('VALIDATION_EMAIL_SUBJECT', GAME_TITLE, $this), lang('VALIDATION_EMAIL_BODY', GAME_TITLE, $user->u_username, $this->e_name, $this->e_valcode, TURNS_VALIDATE, TXT_EMAIL, MAIL_ADMIN));
	}

	// Checks if empire is under protection due to being a new empire
	// Once you've used N+1 turns, you're out of protection
	// Once signups have been closed, new empire protection is dropped
	public function is_protected ()
	{
		return ($this->e_turnsused <= TURNS_PROTECTION) && (ROUND_SIGNUP);
	}

	// Checks if empire is under protection due to being on vacation
	public function is_vacation ()
	{
		return ($this->e_vacation >= VACATION_START + 1);
	}

	// Checks if empire on vacation is capable of leaving it
	public function is_vacation_done ()
	{
		return ($this->e_vacation >= VACATION_START + VACATION_LIMIT + 1);
	}

	// Returns how many hours the empire has been on vacation
	public function vacation_hours_since_lock ()
	{
		return $this->e_vacation - 1;
	}

	// Returns how many hours the empire has been on vacation
	public function vacation_hours_since_start ()
	{
		return ($this->e_vacation - 1) - VACATION_START;
	}

	// Returns how many hours until empire will be protected by vacation
	public function vacation_hours_until_start ()
	{
		return VACATION_START - ($this->e_vacation - 1);
	}

	// Returns how many hours the empire has been on vacation
	public function vacation_hours_until_limit ()
	{
		return VACATION_START + VACATION_LIMIT - ($this->e_vacation - 1);
	}

	// Determine how many points to give when attacking another empire
	public function findScorePoints ($other)
	{
		if ($other == NULL)
			return 0;
		$ratio = $other->e_networth / max(1, $this->e_networth);
		if ($ratio <= 1)
			return 1;
		return 1 + floor(($ratio - 1) * 2);
		//return 1 + floor(3 * log($ratio) + 0.2 * $ratio);
	}

	// Formats empire name+number as a string
	public function __toString ()
	{
		if ($this->id == 0)
			return lang('COMMON_EMPIRE_NAMEID', 'COMMON_EMPIRE_UNINITIALIZED', prenum(0));
		return lang('COMMON_EMPIRE_NAMEID', $this->e_name, prenum($this->e_id));
	}

	// Loads an empire partially, attempting to cache it where possible
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
			$emp = new prom_empire($id);
			$emp->loadPartial();
			$cache[$id] = $emp;
		}
		return $cache[$id];
	}
} // class prom_empire
?>
