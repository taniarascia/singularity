<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: military.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MILITARY_TITLE';

if ($action == 'attack')
	$lock['emp2'] = fixInputNum(getFormVar('attack_target'));

page_header();

if (ROUND_FINISHED)
	unavailable(lang('MILITARY_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('MILITARY_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('MILITARY_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('MILITARY_UNAVAILABLE_ADMIN'));

$trooptypes = lookup('list_mil');

// attacktypes in the trooptypes list are automatically restricted to that unit type
$attacktypes = array();
$attacktypes['standard'] = lang('MILITARY_TYPE_STANDARD');
$attacktypes['surprise'] = lang('MILITARY_TYPE_SURPRISE');
$attacktypes['trparm'] = lang('MILITARY_TYPE_ARM');
$attacktypes['trplnd'] = lang('MILITARY_TYPE_LND');
$attacktypes['trpfly'] = lang('MILITARY_TYPE_FLY');
$attacktypes['trpsea'] = lang('MILITARY_TYPE_SEA');

function performAttack ($emp1, $emp2, $attackUnits, $defendUnits, $attacktype, $warflag)
{
	global $db;

	// calculate power levels
	$offpower = 0;
	$defpower = 0;
	foreach ($attackUnits as $type => $num)
		$offpower += $emp1->calcUnitPower($num, $type, 'o');
	foreach ($defendUnits as $type => $num)
		$defpower += $emp2->calcUnitPower($num, $type, 'd');

	// apply race bonus
	$offpower *= $emp1->getModifier('offense');
	$defpower *= $emp2->getModifier('defense');

	// reduce power with health levels
	$offpower *= $emp1->e_health / 100;
	$defpower *= $emp2->e_health / 100;

	// grant 20% offense power when at war with the target
	if ($warflag)
		$offpower *= 1.20;

	// eras and time gates have already been checked - now we build the messages to display
	if ($emp1->e_era != $emp2->e_era)
	{
		// use your own time gate first, then try your target's gate
		if ($emp1->effects->m_gate)
			echo lang('MILITARY_TIMEGATE_SELF') ."<br />\n";
		elseif ($emp2->effects->m_gate)
			echo lang('MILITARY_TIMEGATE_OTHER') ."<br />\n";
	}

	$helping = FALSE;
	if ($attacktype == 'surprise')
	{
		// additional 25% offense power bonus on surprise attacks, and block ally help
		$offpower *= 1.25;
		// but take an additional 5% health loss (after health taken into account above)
		$emp1->e_health -= 5;
	}
	elseif (CLAN_ENABLE && ($emp2->c_id != 0) && ($emp2->e_sharing != 0) && ($defpower > 0))
	{
		// otherwise, enemy clanmates are permitted to send reinforcements
		// assuming they are shared AND the defender actually has something to defend with
		$q = $db->prepare('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE e_id != ? AND c_id = ? AND e_sharing != 0 AND e_land > 0 AND e_flags & ? = 0');
		$q->bindIntValue(1, $emp2->e_id);
		$q->bindIntValue(2, $emp2->c_id);
		$q->bindIntValue(3, EFLAG_DELETE | EFLAG_DISABLE);	// disallow aid from disabled or deleted empires
		$q->execute() or warning('Failed to fetch list of allies', 0);
		$data = $q->fetchAll();

		// build list of allies which are capable of providing protection
		$allies = array();
		foreach ($data as $ally)
		{
			$emp_a = new prom_empire($ally['e_id']);
			$emp_a->load();
			if ((($emp2->e_era == $emp_a->e_era) || ($emp2->effects->m_gate) || ($emp_a->effects->m_gate)) && ($defpower > 0))
				$allies[] = $emp_a;
			$emp_a = NULL;
		}
		$numallies = count($allies);
		$defbonus = 0;
		// and then calculate defense bonus
		foreach ($allies as $emp_a)
		{
			addEmpireNews(EMPNEWS_MILITARY_AID, $emp1, $emp_a, $emp2->e_id);
			$allydef = 0;
			foreach ($defendUnits as $type => $num)
			{
				$amt = ceil($emp_a->getData('e_'. $type) * 0.10);
				$allydef += $emp_a->calcUnitPower($amt, $type, 'd');
			}
			$allydef *= $emp_a->getModifier('defense');
			$allydef *= $emp_a->e_health / 100;

			$defbonus += $allydef;
		}
		$emp_a = NULL;
		$allies = NULL;
		// and limit the overall defense bonus from allies
		if ($defbonus > $defpower)
			$defbonus = $defpower;
		$defpower += $defbonus;
		if ($numallies > 0)
			echo lang('MILITARY_ALLY_DEFENSE', plural($numallies, 'EMPIRES_SINGLE', 'EMPIRES_PLURAL')) ."<br />\n";
	}

	// add defense from guard towers - max 450 defense per tower, provided there's at least 150 soldiers per tower
	// (soldiers inside the towers will provide their standard defense against enemy soldiers, but not against other units)
	$towerdef = $emp2->e_blddef * 450 * min(1, $emp2->e_trparm / (150 * $emp2->e_blddef + 1));
	$defpower += $towerdef;

	// determine how many units each empire is about to lose

	// modification to attacker losses (towers excluded)
	$omod = sqrt(($defpower - $towerdef) / ($offpower + 1));
	// modification to enemy losses
	$dmod = sqrt($offpower / ($defpower + 1));

	$attackLosses = array();
	$defendLosses = array();

	switch ($attacktype)
	{
	case 'trparm':
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.1155, 0.0705, $omod, $dmod, 'trparm');
		break;
	case 'trplnd':
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.0985, 0.0530, $omod, $dmod, 'trplnd');
		break;
	case 'trpfly':
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.0688, 0.0445, $omod, $dmod, 'trpfly');
		break;
	case 'trpsea':
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.0450, 0.0355, $omod, $dmod, 'trpsea');
		break;
	case 'surprise':
		// surprise attack hurts the attacker 20% more
		$omod *= 1.2;
		// fall through
	case 'standard':
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.1455, 0.0805, $omod, $dmod, 'trparm');
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.1285, 0.0730, $omod, $dmod, 'trplnd');
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.0788, 0.0675, $omod, $dmod, 'trpfly');
		calcUnitLoss($attackLosses, $defendLosses, $attackUnits, $defendUnits, 0.0650, 0.0555, $omod, $dmod, 'trpsea');
		break;
	}
	$won = FALSE;

	// offense needs to be at least 5% stronger than defense
	if ($offpower > $defpower * 1.05)
	{
		$won = TRUE;

		$buildLoss = array('e_land' => 0, 'e_freeland' => 0);
		$buildGain = array('e_land' => 0, 'e_freeland' => 0);

		// destroy/steal structures
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldcash',  0.07, 0.70, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldpop',   0.07, 0.70, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldtrp',   0.07, 0.50, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldcost',  0.07, 0.70, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldfood',  0.07, 0.30, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_bldwiz',   0.07, 0.60, $attacktype);
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_blddef',   0.11, 0.60, $attacktype);	// towers more likely to be taken, since they are encountered first
		destroyBuildings($buildLoss, $buildGain, $emp1, $emp2, 'e_freeland', 0.10, 0.00, $attacktype);	// 3rd argument MUST be 0 (for Standard attacks)
		if (DROP_DELAY)
			$emp1->effects->m_droptime = DROP_DELAY;

		$landloss = array_sum($buildGain);
		echo lang('MILITARY_SUCCESS_HEADER', $emp2->e_name, number($landloss));
		if (SCORE_ENABLE)
		{
			$points = $emp1->findScorePoints($emp2);
			$emp1->e_score += $points;
			$emp2->e_score -= 1;
			echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
		}
		echo "<br />\n";
		$emp1->e_offsucc++;

		$list = array();
		foreach ($defendLosses as $type => $num)
		{
			$list[] = lang('MILITARY_UNITLOSS_ROW', number($num), $emp2->era->getData($type));
			$emp2->subData('e_'. $type, $num);
		}
		if (count($list) > 0)
			echo lang('MILITARY_SUCCESS_DESTROYED', commalist($list)) ."<br />\n";

		$list = array();
		foreach ($attackLosses as $type => $num)
		{
			$list[] = lang('MILITARY_UNITLOSS_ROW', number($num), $emp1->era->getData($type));
			$emp1->subData('e_'. $type, $num);
		}
		if (count($list) > 0)
			echo lang('MILITARY_SUCCESS_LOSSES', commalist($list)) ."<br />\n";
	}
	else
	{
		$landloss = 0;
		echo lang('MILITARY_FAILURE_HEADER', $emp2->e_name);
		if (SCORE_ENABLE)
		{
			$points = round($emp1->findScorePoints($emp2) / 2);
			$emp1->e_score -= $points;
			$emp2->e_score += 1;
			echo ' '. plural($points, 'COMMON_POINTS_LOSE_SINGLE', 'COMMON_POINTS_LOSE_PLURAL');
		}
		echo "<br />\n";
		$emp2->e_defsucc++;

		$list = array();
		foreach ($attackLosses as $type => $num)
		{
			$list[] = lang('MILITARY_UNITLOSS_ROW', number($num), $emp1->era->getData($type));
			$emp1->subData('e_'. $type, $num);
		}
		if (count($list) > 0)
			echo lang('MILITARY_FAILURE_LOSSES', commalist($list)) ."<br />\n";

		$list = array();
		foreach ($defendLosses as $type => $num)
		{
			$list[] = lang('MILITARY_UNITLOSS_ROW', number($num), $emp2->era->getData($type));
			$emp2->subData('e_'. $type, $num);
		}
		if (count($list) > 0)
			echo lang('MILITARY_FAILURE_DESTROYED', commalist($list)) ."<br />\n";
	}

	$killed = FALSE;
	if ($won)
	{
		// if anything other than freeland was gained, report it
		$bldgain = array_sum($buildGain) - $buildGain['e_freeland'];
		if ($bldgain > 0)
			echo lang('MILITARY_STRUCTURES_GAINED', plural($bldgain, 'STRUCTURES_SINGLE', 'STRUCTURES_PLURAL')) ."<br />\n";

		// and if a negative amount of freeland was lost, report it
		$blddest = -$buildLoss['e_freeland'];
		if ($blddest > 0)
			echo lang('MILITARY_STRUCTURES_DESTROYED', plural($blddest, 'STRUCTURES_SINGLE', 'STRUCTURES_PLURAL')) ."<br />\n";
		if ($emp2->e_land == 0)
		{
			if (!($emp2->e_flags & EFLAG_DELETE) || ($emp2->e_idle > CUR_TIME - 60 * 30))
			{	// if they deleted less than 30 minutes ago, it still counts as a kill
				$killed = TRUE;
				echo '<span class="cgood">'. lang('MILITARY_SUCCESS_KILLED', $emp2) .'</span>';
				if (SCORE_ENABLE)
				{
					$points = max(round($emp2->e_score / 5), 100);
					$emp1->e_score += $points;
					echo ' '. plural($points, 'COMMON_POINTS_GAIN_SINGLE', 'COMMON_POINTS_GAIN_PLURAL');
				}
				echo '<br />';
				$emp1->e_kills++;
				$emp2->e_killedby = $emp1->e_id;
				if (CLAN_ENABLE)
					$emp2->e_killclan = $emp1->c_id;
			}
			else	echo '<span class="cgood">'. lang('MILITARY_SUCCESS_KILLED_LATE', $emp2) .'</span><br />';
		}
	}

	switch ($attacktype)
	{
	case 'standard':addEmpireNews(EMPNEWS_MILITARY_STANDARD, $emp1, $emp2, $landloss,
				$defendLosses['trparm'], $defendLosses['trplnd'], $defendLosses['trpfly'], $defendLosses['trpsea'],
				$attackLosses['trparm'], $attackLosses['trplnd'], $attackLosses['trpfly'], $attackLosses['trpsea']);	break;
	case 'surprise':addEmpireNews(EMPNEWS_MILITARY_SURPRISE, $emp1, $emp2, $landloss,
				$defendLosses['trparm'], $defendLosses['trplnd'], $defendLosses['trpfly'], $defendLosses['trpsea'],
				$attackLosses['trparm'], $attackLosses['trplnd'], $attackLosses['trpfly'], $attackLosses['trpsea']);	break;
	case 'trparm':	addEmpireNews(EMPNEWS_MILITARY_ARM, $emp1, $emp2, $landloss, $defendLosses['trparm'], $attackLosses['trparm']);		break;
	case 'trplnd':	addEmpireNews(EMPNEWS_MILITARY_LND, $emp1, $emp2, $landloss, $defendLosses['trplnd'], $attackLosses['trplnd']);		break;
	case 'trpfly':	addEmpireNews(EMPNEWS_MILITARY_FLY, $emp1, $emp2, $landloss, $defendLosses['trpfly'], $attackLosses['trpfly']);		break;
	case 'trpsea':	addEmpireNews(EMPNEWS_MILITARY_SEA, $emp1, $emp2, $landloss, $defendLosses['trpsea'], $attackLosses['trpsea']);		break;
	}
	if ($killed)
		addEmpireNews(EMPNEWS_MILITARY_KILL, $emp1, $emp2, 0);
}

// Calculate number of units lost for attacker and defender
function calcUnitLoss(&$attackLosses, &$defendLosses, $attackUnits, $defendUnits, $oper, $dper, $omod, $dmod, $type)
{
	$attackLosses[$type] = min(mt_rand(0, ceil($attackUnits[$type] * $oper * $omod) + 1), $attackUnits[$type]);

	// defender cannot lose more than 90-110% of the units the attacker sent (random)
	$maxkill = round(0.9 * $attackUnits[$type]) + mt_rand(0, round(0.2 * $attackUnits[$type]) + 1);
	$defendLosses[$type] = min(mt_rand(0, ceil($defendUnits[$type] * $dper * $dmod) + 1), $defendUnits[$type], $maxkill);
}

// Destroy an empire's buildings and/or land, possibly giving some back to the attacker
function destroyBuildings (&$buildLoss, &$buildGain, $emp1, $emp2, $type, $pcloss, $pcgain, $attacktype)
{
	if (($attacktype == 'trplnd') || ($attacktype == 'trpfly') || ($attacktype == 'trpsea'))
	{	// these attacks are special - they destroy buildings (loss), but only steal some of the empty land (gain)
		if ($attacktype == 'trpfly')
		{
			// air strikes destroy more, take more land, but gain fewer buildings
			$pcloss *= 1.25;
			$pcgain *= 0.72;
		}
		elseif (($type == 'e_blddef') || ($type == 'e_bldwiz'))
		{
			// towers are even more likely to be destroyed by land/sea attacks (and more likely to be destroyed)
			$pcloss *= 1.30;
			$pcgain *= 0.70;
		}
		else
		{
			// while land/sea attacks simply have a higher chance of destroying the buildings stolen
			$pcgain *= 0.90;
		}
	}

	$loss = min(mt_rand(1, ceil($emp2->getData($type) * $pcloss + 2)), $emp2->getData($type));
	$gain = ceil($loss * $pcgain);

	if (!isset($buildLoss[$type]))
		$buildLoss[$type] = 0;
	if (!isset($buildGain[$type]))
		$buildGain[$type] = 0;

	switch ($attacktype)
	{
	case 'standard':
		$emp2->e_land -= $loss;
		$emp2->subData($type, $loss);
		$buildLoss[$type] += $loss;

		$emp1->e_land += $loss;
		$emp1->addData($type, $gain);
		$buildGain[$type] += $gain;
		$emp1->e_freeland += $loss - $gain;
		$buildGain['e_freeland'] += $loss - $gain;
		break;
	case 'surprise':
	case 'trparm':
		$emp2->e_land -= $loss;
		$emp2->subData($type, $loss);
		$buildLoss[$type] += $loss;

		$emp1->e_land += $loss;
		$emp1->e_freeland += $loss;
		$buildGain['e_freeland'] += $loss;
		break;
	case 'trplnd':
	case 'trpfly':
	case 'trpsea':
		if ($type == 'e_freeland')	// for stealing unused land, the 'gain' percent is zero
			$gain = $loss;		// so we need to use the 'loss' value instead
		$emp2->e_land -= $gain;
		$emp2->subData($type, $loss);
		$buildLoss[$type] += $loss;
		$emp2->e_freeland += $loss - $gain;
		$buildLoss['e_freeland'] -= $loss - $gain;

		$emp1->e_land += $gain;
		$emp1->e_freeland += $gain;
		$buildGain['e_freeland'] += $gain;
		break;
	}
}

// defaults
$attacktype = 'standard';
$sendall = FALSE;

if ($action == 'attack') do
{
	if (!isFormPost())
		break;
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if ($emp1->e_id == $emp2->e_id)
	{
		notice(lang('MILITARY_SELF'));
		break;
	}
	$attacktype = getFormVar('attack_type');
	if (!isset($attacktypes[$attacktype]))
	{
		notice(lang('MILITARY_INVALID'));
		break;
	}
	if ($emp1->e_turns < 2)
	{
		notice(lang('MILITARY_NEED_TURNS'));
		break;
	}
	if ($emp1->e_health <= 10)
	{
		notice(lang('MILITARY_NEED_HEALTH'));
		break;
	}
	if ($emp2->e_land == 0)
	{
		notice(lang('MILITARY_DEAD'));
		break;
	}
	if ($emp2->u_id == 0)
	{
		notice(lang('MILITARY_DELETED'));
		break;
	}
	if (($emp2->e_era != $emp1->e_era) && (!$emp1->effects->m_gate) && (!$emp2->effects->m_gate))
	{
		notice(lang('MILITARY_NEED_GATE'));
		break;
	}
	if ($emp2->e_flags & EFLAG_ADMIN)
	{
		notice(lang('MILITARY_ADMIN'));
		break;
	}
	if ($emp2->e_flags & EFLAG_DISABLE)
	{
		notice(lang('MILITARY_DISABLED'));
		break;
	}
	if ($emp2->is_protected())
	{
		notice(lang('MILITARY_PROTECTED'));
		break;
	}
	if ($emp2->is_vacation())
	{
		notice(lang('MILITARY_VACATION'));
		break;
	}

	$warflag = 0;
	if (CLAN_ENABLE)
	{
		$netmult_refuse = 20;
		$netmult_desert = 2.5;
	}
	else
	{
		$netmult_refuse = 50;
		$netmult_desert = 5;
	}

	if (CLAN_ENABLE && ($emp1->c_id != 0) && ($emp2->c_id != 0))
	{
		if ($emp1->c_id == $emp2->c_id)
		{
			notice(lang('MILITARY_REFUSE_CLAN'));
			break;
		}

		$clan_a = new prom_clan($emp1->c_id);

		$allies = $clan_a->getAllies();
		$wars = $clan_a->getWars();

		if (in_array($emp2->c_id, $allies))
		{
			notice(lang('MILITARY_REFUSE_ALLY'));
			break;
		}
		if (in_array($emp2->c_id, $wars))
		{
			$warflag = 1;
			$netmult_refuse = 50;
		}
		$clan_a = NULL;
	}

	$alive = TRUE;
	if (($emp2->e_flags & EFLAG_DELETE) || ($emp2->e_networth == 0))
		$alive = FALSE;		// deleted empires cannot defend themselves

	/*if (CLAN_ENABLE && ($alive) && ($emp2->e_networth < $emp1->e_networth / $netmult_refuse))
	{
		notice(lang('MILITARY_REFUSE_SMALL'));
		break;
	}*/
	if (($alive) && ($emp2->e_networth > $emp1->e_networth * $netmult_refuse))
	{
		notice(lang('MILITARY_REFUSE_LARGE'));
		break;
	}

	$sendall = fixInputBool(getFormVar('attack_sendall'));

	// only enumerate the unit types which are involved in the attack
	// all units have unit-specific attacks, so all other attack types use everything
	if (in_array($attacktype, $trooptypes))
		$types = array($attacktype);
	else	$types = $trooptypes;
	foreach ($types as $type)
	{
		$attackUnits[$type] = $emp1->getData('e_'. $type);
		$defendUnits[$type] = $emp2->getData('e_'. $type);
		// shared forces cannot be used for defense
		// though they CAN be used for offense
		if (CLAN_ENABLE && ($emp2->e_sharing != 0))
			$defendUnits[$type] = ceil($defendUnits[$type] * 0.9);

		// if not opting to send all units, just cap at max unit count
		if (!$sendall)
			$attackUnits[$type] = min($attackUnits[$type], fixInputNum(getFormVar('attack_'. $type)));
		// if target is dead, they have no units to defend with
		if (!$alive)
			$defendUnits[$type] = 0;
	}
	if (array_sum($attackUnits) == 0)
	{
		notice(lang('MILITARY_NEED_UNITS'));
		break;
	}

	if (($warflag == 0) && ($alive))
	{
		if ((MAX_ATTACKS > 0) && ($emp1->e_attacks >= 2 * MAX_ATTACKS))
		{
			notice(lang('MILITARY_ATTACK_LIMIT', duration(2 * TURNS_FREQ * 60)));
			break;
		}
		$revolt = 0;
		if ($emp2->e_networth < $emp1->e_networth / $netmult_desert)
		{	// Shame is less powerful than fear
			echo '<div class="cwarn">'. lang('MILITARY_DESERT_SMALL') .'</div>';
			$revolt = ($emp1->e_networth / $emp2->e_networth) / 125;
		}
		if ($emp2->e_networth > $emp1->e_networth * $netmult_desert)
		{
			echo '<div class="cwarn">'. lang('MILITARY_DESERT_LARGE') .'</div>';
			$revolt = ($emp2->e_networth / $emp1->e_networth) / 100;
		}
		// half losses if no clans
		if (!CLAN_ENABLE)
			$revolt *= 0.5;
		// limit to 10% loss
		if ($revolt > 0.1)
			$revolt = 0.1;
		foreach ($trooptypes as $type)
		{
			$troop = 'e_'. $type;
			$loss = ceil($emp1->getData($troop) * $revolt);
			$emp1->subData($troop, $loss);
			// if your military deserts, they won't be participating in the attack
			if (isset($attackUnits[$type]) && ($attackUnits[$type] > $emp1->getData($troop)))
				$attackUnits[$type] = $emp1->getData($troop);
		}
	}

	echo lang('MILITARY_BEGIN') .'<br />';

	if ($warflag)
		$taken = $emp1->taketurns(2, 'war', TRUE);
	else	$taken = $emp1->taketurns(2, 'attack', TRUE);

	if ($taken != 2)
	{
		notice(lang('MILITARY_TROUBLE_ABORT'));
		logevent(varlist(array('taken'), get_defined_vars()));
		break;
	}

	performAttack($emp1, $emp2, $attackUnits, $defendUnits, $attacktype, $warflag);

	if ((MAX_ATTACKS > 0) && ($warflag == 0) && ($alive))
	{
		$emp1->e_attacks += 2;
		$emp2->e_attacks--;
	}
	$emp1->e_health -= 8;
	$emp1->e_offtotal++;
	$emp2->e_deftotal++;

	logevent(varlist(array('taken', 'attacktype'), get_defined_vars()));
} while (0);
notices();
?>
<form method="post" action="?location=military">
<table class="inputtable">
<tr><td colspan="3" class="ac"><?php echo lang('MILITARY_HEADER', '<input type="text" name="attack_target" value="'. prenum($lock['emp2']) .'" size="5" />'); ?></td></tr>
<tr><td colspan="3" class="ac"><?php echo lang('MILITARY_ATTACKTYPE_LABEL') .' ';
$attacklist = array();
foreach ($attacktypes as $type => $name)
	$attacklist[$type] = $name;
echo optionlist('attack_type', $attacklist, $attacktype);
?></td></tr>
<tr><th class="al"><?php echo lang('COLUMN_UNIT'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANSEND'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_SEND'); ?></th></tr>
<?php
foreach ($trooptypes as $type)
{
	$cansend = $emp1->getData('e_'. $type);
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($cansend) .' '. copybutton('attack_'. $type, number($cansend)); ?></td>
    <td class="ar"><input type="text" name="attack_<?php echo $type; ?>" id="attack_<?php echo $type; ?>" size="8" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="3" class="ac"><?php echo checkbox('attack_sendall', lang('MILITARY_SENDALL'), 1, $sendall); ?></td></tr>
<tr><td colspan="3" class="ac"><input type="hidden" name="action" value="attack" /><input type="submit" value="<?php echo lang('MILITARY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php

$effects = $emp1->effects->getDescriptions();
if (count($effects))
{
	echo '<i>'. lang('EFFECT_HEADER') ."</i>\n";
	echo '<table class="inputtable">';
	echo '<tr><th>'. lang('COLUMN_EFFECT_NAME') .'</th><th>'. lang('COLUMN_EFFECT_DESC') .'</th><th>'. lang('COLUMN_EFFECT_DURATION') ."</th></tr>\n";
	foreach ($effects as $effect)
		echo '<tr><td>'. $effect['name'] .'</td><td>'. $effect['desc'] .'</td><td>'. $effect['duration'] .'</td></tr>'."\n";
	echo "</table>\n";
}
page_footer();
?>
