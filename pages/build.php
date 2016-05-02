<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: build.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'BUILD_TITLE';

page_header();

if (ROUND_FINISHED)
	unavailable(lang('BUILD_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('BUILD_UNAVAILABLE_START'));

$bldtypes = array('bldpop', 'bldcash', 'bldtrp', 'bldcost', 'bldwiz', 'bldfood', 'blddef');

function getBuildAmounts ($emp, &$buildcost, &$buildrate, &$canbuild)
{
	$buildcost = round(BUILD_COST + ($emp->e_land * 0.1));
	$buildrate = $emp->e_land * 0.015 + 4;
/*
	if ($buildrate > 400)
		$buildrate = 400;
*/
	$buildrate = round($emp->getModifier('buildrate') * $buildrate);
	// limit by available cash, available turns, and available land
	$canbuild = min(floor($emp->e_cash / $buildcost), $buildrate * $emp->e_turns, $emp->e_freeland);
}

getBuildAmounts($emp1, $buildcost, $buildrate, $canbuild);
if ($action == 'build') do
{
	if (!isFormPost())
		break;
	$build_amt = array();
	$build_count = array();	// save build counts
	foreach ($bldtypes as $type)
	{
		$i = fixInputNum(getFormVar('build_'.$type));
		if ($i > 0)
			$build_count[$type] = $build_amt[$type] = $i;
	}

	$totalbuild = array_sum($build_amt);
	$totalspent = $totalbuild * $buildcost;
	$turns = ceil($totalbuild / $buildrate);

	if ($totalbuild == 0)
	{
		notice(lang('BUILD_NO_INPUT'));
		break;
	}
	if ($totalbuild > $canbuild)
	{
		notice(lang('BUILD_TOO_MANY'));
		break;
	}
	$built = 0;
	$spent = 0;
	for ($i = 0; $i < $turns; $i++)
	{
		if ($emp1->takeTurns(1, 'build', FALSE) < 0)
		{	// trouble? stop building (and cancel anything that was to happen this turn)
			$turns = $i + 1;
			break;
		}
		$unbuilt = $buildrate;
		while (($unbuilt != 0) && (count($build_amt) != 0))
		{
			$buildper = max(min(floor($buildrate / count($build_amt)), min($build_amt)), 1);
			foreach ($build_amt as $type => $amt)
			{
				$to_build = min($amt, $buildper, $unbuilt);
				if ($emp1->e_cash < $to_build * $buildcost)
				{
					notice(lang('BUILD_OUT_OF_CASH'));
					$turns = $i + 1;
					break 3;
				}

				$emp1->addData('e_'. $type, $to_build);
				$emp1->e_freeland -= $to_build;
				$emp1->e_cash -= $to_build * $buildcost;
				$spent += $to_build * $buildcost;
				$build_amt[$type] -= $to_build;
				$unbuilt -= $to_build;
				$built += $to_build;

				if ($build_amt[$type] == 0)
					unset($build_amt[$type]);
				if ($unbuilt == 0)
					break;
			}
		}
	}
	notice(lang('BUILD_COMPLETE', money($spent), plural($turns, 'TURNS_SINGLE', 'TURNS_PLURAL'), plural($built, 'STRUCTURES_SINGLE', 'STRUCTURES_PLURAL')));
	logevent(varlist(array('spent', 'turns', 'built', 'build_count'), get_defined_vars()));
	getBuildAmounts($emp1, $buildcost, $buildrate, $canbuild);
} while (0);
notices();
?>
<?php echo lang('BUILD_HEADER', money($buildcost), number($buildrate), number($canbuild)); ?><br />
<br/><img src="https://s-media-cache-ak0.pinimg.com/736x/51/7f/a2/517fa2b6d8b7f5ce48c002260afd626b.jpg" style="max-width:550px;"/>
<table class="inputtable">
<form method="post" action="?location=build">

<tr><th class="al"><?php echo lang('COLUMN_STRUCTURE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_OWNED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_CANBUILD'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_BUILD'); ?></th></tr>
<?php
foreach ($bldtypes as $type)
{
?>
<tr><td><?php echo lang($emp1->era->getData($type)); ?></td>
    <td class="ar"><?php echo number($emp1->getData('e_'. $type)); ?></td>
    <td class="ar"><?php echo number($canbuild); ?></td>
    <td class="ar"><input type="text" name="build_<?php echo $type; ?>" size="5" value="0" /></td></tr>
<?php
}
?>
<tr><td><?php echo lang('BUILD_LABEL_FREELAND'); ?></td>
    <td class="ar"><?php echo number($emp1->e_freeland); ?></td>
    <td colspan="2"></td></tr>
<tr><td colspan="4" class="ac"><input type="hidden" name="action" value="build" /><input type="submit" value="<?php echo lang('BUILD_SUBMIT'); ?>" /></td></tr>

</form>
<br />
<a href="?location=demolish" class="button"><?php echo lang('BUILD_LINK_DEMOLISH'); ?></a>
</table>
<?php
page_footer();
?>
