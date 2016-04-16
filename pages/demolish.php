<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: demolish.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'DEMOLISH_TITLE';

page_header();

if (ROUND_FINISHED)
	unavailable(lang('DEMOLISH_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('DEMOLISH_UNAVAILABLE_START'));

$bldtypes = array('bldpop', 'bldcash', 'bldtrp', 'bldcost', 'bldwiz', 'bldfood', 'blddef');

function getDemoAmounts ($emp, &$democost, &$demorate, &$candemo)
{
	$democost = round((BUILD_COST + $emp->e_land * 0.1) / 5);
	$demorate = min(floor((($emp->e_land * 0.02) + 2) * $emp->getModifier('buildrate')), 200);
	$candemo = min($demorate * $emp->e_turns, $emp->e_land - $emp->e_freeland);
}

function getDropAmounts ($emp, &$droprate, &$candrop)
{
	// 200/turn for demolition is a maximum, but 50/turn for dropping is a minimum
	$droprate = max(ceil(($emp->e_land * 0.02 + 2) * $emp->getModifier('buildrate') / 10), 50);
	if ($emp->effects->m_droptime)
		$droprate = ceil($droprate / 10);
	$candrop = min($droprate * $emp->e_turns, $emp->e_freeland, max(0, $emp->e_land - 1000));
}

getDemoAmounts($emp1, $democost, $demorate, $candemo);
getDropAmounts($emp1, $droprate, $candrop);

if ($action == 'demolish') do
{
	if (!isFormPost())
		break;
	$demo_amt = array();
	$demo_count = array();
	foreach ($bldtypes as $type)
	{
		$i = fixInputNum(getFormVar('demo_'.$type));
		if ($i > 0)
			$demo_count[$type] = $demo_amt[$type] = $i;
	}

	$turns = ceil(array_sum($demo_amt) / $demorate);
	foreach ($demo_amt as $type => $amt)
	{
		if ($demo_amt[$type] > $emp1->getData('e_'. $type))
		{
			notice(lang('DEMOLISH_TOO_MANY'));
			break 2;
		}
	}
	if ($turns > $emp1->e_turns)
	{
		notice(lang('DEMOLISH_NOT_ENOUGH_TURNS'));
		break;
	}
	if (array_sum($demo_amt) == 0)
		break;
	$demolished = 0;
	$salvaged = 0;
	for ($i = 0; $i < $turns; $i++)
	{
		$undemo = $demorate;
		while (($undemo != 0) && (count($demo_amt) != 0))
		{
			$demoper = max(min(floor($demorate / count($demo_amt)), min($demo_amt)), 1);
			foreach ($demo_amt as $type => $amt)
			{
				$to_demo = min($amt, $demoper, $undemo);
				$undemo -= $to_demo;
				$demolished += $to_demo;

				$emp1->subData('e_'. $type, $to_demo);
				$emp1->e_freeland += $to_demo;
				$emp1->e_cash += $to_demo * $democost;
				$salvaged += $to_demo * $democost;
				$demo_amt[$type] -= $to_demo;

				if ($demo_amt[$type] == 0)
					unset($demo_amt[$type]);
				if ($undemo == 0)
					break;
			}
		}
		if ($emp1->takeTurns(1, 'demolish', FALSE) < 0)
		{
			$turns = $i;
			break;
		}
	}
	notice(lang('DEMOLISH_COMPLETE', plural($turns, 'TURNS_SINGLE', 'TURNS_PLURAL'), plural($demolished, 'STRUCTURES_SINGLE', 'STRUCTURES_PLURAL'), money($salvaged)));
	logevent(varlist(array('salvaged', 'turns', 'demolished', 'demo_count'), get_defined_vars()));
	getDemoAmounts($emp1, $democost, $demorate, $candemo);
	getDropAmounts($emp1, $droprate, $candrop);
} while (0);
elseif ($action == 'drop') do
{
	if (!isFormPost())
		break;
	$drop_amt = fixInputNum(getFormVar('drop_land'));
	$turns = ceil($drop_amt / $droprate);

	if ($drop_amt > $candrop)
	{
		notice(lang('DEMOLISH_DROP_TOO_MUCH'));
		break;
	}
	if ($drop_amt == 0)
		break;
	$dropped = 0;
	for ($i = 0; $i < $turns; $i++)
	{
		$to_drop = min($droprate, $drop_amt);
		$emp1->e_land -= $to_drop;
		$emp1->e_freeland -= $to_drop;
		$drop_amt -= $to_drop;
		$dropped += $to_drop;

		if ($emp1->takeTurns(1, 'dropland', FALSE) < 0)
		{
			$turns = $i;
			break;
		}
	}
	notice(lang('DEMOLISH_DROP_COMPLETE', plural($turns, 'TURNS_SINGLE', 'TURNS_PLURAL'), plural($dropped, 'ACRES_SINGLE', 'ACRES_PLURAL')));
	logevent(varlist(array('turns', 'dropped'), get_defined_vars()));
	getDemoAmounts($emp1, $democost, $demorate, $candemo);
	getDropAmounts($emp1, $droprate, $candrop);
} while (0);
notices();
?>
<?php echo lang('DEMOLISH_HEADER', money($democost), number($demorate), number($candemo)); ?><br />
<form method="post" action="?location=demolish">
<table class="inputtable">
<tr><th class="al"><?php echo lang('COLUMN_STRUCTURE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANDEMO'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_DEMO'); ?></th></tr>
<?php
foreach ($bldtypes as $type)
{
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo number(min($candemo, $emp1->getData('e_'. $type))); ?></td>
    <td class="ar"><input type="text" name="demo_<?php echo $type; ?>" size="5" value="0" /></td></tr>
<?php
}
?>
<tr><td colspan="4" class="ac"><input type="hidden" name="action" value="demolish" /><input type="submit" value="<?php echo lang('DEMOLISH_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php echo lang('DEMOLISH_DROP_HEADER', number($candrop), number($droprate)); ?><br />
<form method="post" action="?location=demolish">
<table class="inputtable">
<tr><th class="al"></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANDROP'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_DROP'); ?></th></tr>
<tr><td><?php echo lang('BUILD_LABEL_FREELAND'); ?></td>
    <td class="ar"><?php echo number($emp1->e_freeland); ?></td>
    <td class="ar"><?php echo number($candrop); ?></td>
    <td class="ar"><input type="text" name="drop_land" size="5" value="0" /></td></tr>
<tr><td colspan="4" class="ac"><input type="hidden" name="action" value="drop" /><input type="submit" value="<?php echo lang('DEMOLISH_DROP_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<a href="?location=build"><?php echo lang('DEMOLISH_LINK_BUILD'); ?></a>
<?php
page_footer();
?>
