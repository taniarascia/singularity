<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: empire.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MANAGE_EMPIRE_TITLE';

page_header();

// killUnits($emp, 'unit', 0.20, 0.40) will destroy 20%-40% of a unit, biased toward 30%
function killUnits ($emp, $type, $minpc, $maxpc)
{
	$losspct = gauss_rand($minpc, $maxpc);
	$loss = round($emp->getData($type) * $losspct);
	$emp->subData($type, $loss);
	return $loss;
}

if ($action == 'polymorph') do
{
	if (!isFormPost())
		break;
	$race = fixInputNum(getFormVar('polymorph_race'));
	$confirm = fixInputBool(getFormVar('polymorph_confirm'));

	// If the round hasn't started, nobody's used any turns
	// so it's okay for them to polymorph
	if (ROUND_FINISHED)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_UNAVAILABLE'));
		break;
	}
	if (!$race)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_NEED_RACE'));
		break;
	}
	if (!prom_race::exists($race))
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_BAD_RACE'));
		break;
	}
	if ($race == $emp1->e_race)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_SAME_RACE'));
		break;
	}
	if ($emp1->e_turnsused == 0)
	{	// don't need to confirm if no turns have been used
		$emp1->e_race = $race;
		$emp1->race = new prom_race($race);
		notice(lang('MANAGE_EMPIRE_POLYMORPH_FREE'));
		logevent(varlist(array('race'), get_defined_vars()));
		break;
	}
	if (!$confirm)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_NEED_CONFIRM'));
		break;
	}
	if ($emp1->e_turns < TURNS_INITIAL)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_NEED_TURNS'));
		break;
	}
	if ($emp1->e_health < 75)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_NEED_HEALTH'));
		break;
	}
	if ($emp1->e_trpwiz < $emp1->e_land * 3)
	{
		notice(lang('MANAGE_EMPIRE_POLYMORPH_NEED_TRPWIZ', $emp1->era->trpwiz));
		break;
	}
	$emp1->e_health -= 50;
	$emp1->e_turns -= TURNS_INITIAL;
	notice(lang('MANAGE_EMPIRE_POLYMORPH_BEGIN', $emp1->era->trpwiz));
	$losearm = killUnits($emp1, 'e_trparm', 0.10, 0.15);
	$loselnd = killUnits($emp1, 'e_trplnd', 0.10, 0.15);
	$losefly = killUnits($emp1, 'e_trpfly', 0.10, 0.15);
	$losesea = killUnits($emp1, 'e_trpsea', 0.10, 0.15);
	$losepeasants = killUnits($emp1, 'e_peasants', 0.10, 0.15);
	$losewiz = killUnits($emp1, 'e_trpwiz', 0.10, 0.15);
	notice(lang('MANAGE_EMPIRE_POLYMORPH_UNITS_LOST',
		number($losearm), $emp1->era->trparm,
		number($loselnd), $emp1->era->trplnd,
		number($losefly), $emp1->era->trpfly,
		number($losesea), $emp1->era->trpsea,
		number($losepeasants), $emp1->era->peasants,
		number($losewiz), $emp1->era->trpwiz));
	$losebld = 0;
	$losebld += killUnits($emp1, 'e_bldpop', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_bldcash', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_bldtrp', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_bldcost', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_bldwiz', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_bldfood', 0.15, 0.50);
	$losebld += killUnits($emp1, 'e_blddef', 0.15, 0.50);
	$emp1->e_freeland += $losebld;
	$size = $emp1->calcSizeBonus();
	$losefood = killUnits($emp1, 'e_food', 0.15 * $size, 0.45 * $size);
	$loserunes = killUnits($emp1, 'e_runes', 0.15 * $size, 0.45 * $size);
	$losecash = killUnits($emp1, 'e_cash', 0.15 * $size, 0.45 * $size);
	notice(lang('MANAGE_EMPIRE_POLYMORPH_OTHER_LOST',
		number($losebld),
		number($losefood), $emp1->era->food,
		number($loserunes), $emp1->era->runes,
		money($losecash)));
	$emp1->e_race = $race;
	$emp1->race = new prom_race($race);
	notice(lang('MANAGE_EMPIRE_POLYMORPH_COMPLETE', $emp1->race));
	logevent(varlist(array('race', 'losearm', 'loselnd', 'losefly', 'losesea', 'losepeasants', 'losewiz', 'losebld', 'losefood', 'loserunes', 'losecash'), get_defined_vars()));
} while (0);
if ($action == 'tax') do
{
	if (!isFormPost())
		break;
	$tax = fixInputNum(getFormVar('tax_newrate'));
	if (!$tax)
	{
		notice(lang('MANAGE_EMPIRE_TAX_NEED_RATE'));
		break;
	}
	if ($tax < 5)
	{
		notice(lang('MANAGE_EMPIRE_TAX_TOO_LOW'));
		break;
	}
	if ($tax > 70)
	{
		notice(lang('MANAGE_EMPIRE_TAX_TOO_HIGH'));
		break;
	}
	$emp1->e_tax = $tax;
	notice(lang('MANAGE_EMPIRE_TAX_COMPLETE'));
	logevent(varlist(array('tax'), get_defined_vars()));
} while (0);
if ($action == 'industry') do
{
	if (!isFormPost())
		break;
	$arm = fixInputNum(getFormVar('industry_arm'));
	$lnd = fixInputNum(getFormVar('industry_lnd'));
	$fly = fixInputNum(getFormVar('industry_fly'));
	$sea = fixInputNum(getFormVar('industry_sea'));
	if (($arm + $lnd + $fly + $sea) > 100)
	{
		notice(lang('MANAGE_EMPIRE_INDUSTRY_TOO_HIGH'));
		break;
	}
	$emp1->e_indarm = $arm;
	$emp1->e_indlnd = $lnd;
	$emp1->e_indfly = $fly;
	$emp1->e_indsea = $sea;
	notice(lang('MANAGE_EMPIRE_INDUSTRY_COMPLETE'));
	logevent(varlist(array('arm', 'lnd', 'fly', 'sea'), get_defined_vars()));
} while (0);
if ($action == 'vacation') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('vacation_confirm'));
	if (VACATION_LIMIT == 0)
	{
		notice(lang('MANAGE_EMPIRE_VACATION_DISABLED'));
		break;
	}
	if (!ROUND_STARTED)
	{
		notice(lang('MANAGE_EMPIRE_VACATION_UNAVAILABLE'));
		break;
	}
	if (!$confirm)
	{
		notice(lang('MANAGE_EMPIRE_VACATION_NEED_CONFIRM'));
		break;
	}
	if (!($emp1->e_flags & EFLAG_VALID))
	{
		notice(lang('MANAGE_EMPIRE_VACATION_NEED_VALIDATE'));
		break;
	}
	if (ROUND_CLOSING)
	{
		notice(lang('MANAGE_EMPIRE_VACATION_TOO_LATE'));
		break;
	}
	$emp1->e_vacation = 1;
	$emp1->setFlag(EFLAG_NOTIFY);
	notice(lang('MANAGE_EMPIRE_VACATION_COMPLETE', VACATION_START));
	logevent();
} while (0);
if ($action == 'unvacation') do
{
	if (!isFormPost())
		break;
	if ($emp1->e_vacation == 0)
	{
		notice(lang('MANAGE_EMPIRE_UNVACATION_NOT_NEEDED'));
		break;
	}
	if (!$emp1->is_vacation_done())
	{
		notice(lang('MANAGE_EMPIRE_UNVACATION_NOT_YET'));
		break;
	}

	$emp1->e_vacation = 0;
	$emp1->e_idle = CUR_TIME;
	$emp1->clrFlag(EFLAG_NOTIFY);
	notice(lang('MANAGE_EMPIRE_UNVACATION_COMPLETE'));
	logevent();
} while (0);
notices();
?>
<h2><?php echo lang('MANAGE_EMPIRE_HEADER'); ?></h2>
<table style="width:100%">
<tr><td class="ac" rowspan="2" style="width:30%">
        <form method="post" action="?location=manage/empire">
        <table class="inputtable">
        <tr><th><?php echo lang('MANAGE_EMPIRE_POLYMORPH_LABEL'); ?></th></tr>
        <tr><td class="ac"><?php
if ($emp1->e_turnsused != 0)
	echo lang('MANAGE_EMPIRE_POLYMORPH_REQUIREMENT', TURNS_INITIAL, number($emp1->e_land * 3), $emp1->era->trpwiz);
else	echo lang('MANAGE_EMPIRE_POLYMORPH_NOPENALTY');
?></td></tr>
<?php
foreach ($races as $rid => $rname)
{
?>
        <tr><td><?php echo radiobutton('polymorph_race', $rname, $rid, ($rid == $emp1->e_race)); ?></td></tr>
<?php
}
if ($emp1->e_turnsused != 0)
{
?>
        <tr><td class="ac"><?php echo checkbox('polymorph_confirm', lang('MANAGE_EMPIRE_POLYMORPH_CONFIRM')); ?></td></tr>
<?php
}
?>
        <tr><th><input type="hidden" name="action" value="polymorph" /><input type="submit" value="<?php echo lang('MANAGE_EMPIRE_POLYMORPH_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td>
    <td class="ac" style="width:35%">
        <form method="post" action="?location=manage/empire">
        <table class="inputtable">
        <tr><td><?php echo lang('MANAGE_EMPIRE_TAX_LABEL'); ?></td>
            <td class="ar"><input type="text" name="tax_newrate" size="3" value="<?php echo percent($emp1->e_tax); ?>" /></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="tax" /><input type="submit" value="<?php echo lang('MANAGE_EMPIRE_TAX_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td>
    <td class="ac" style="width:35%">
        <form method="post" action="?location=manage/empire">
        <table class="inputtable">
        <tr><th colspan="2"><?php echo lang('MANAGE_EMPIRE_INDUSTRY_LABEL'); ?></th></tr>
        <tr><td><?php echo lang($emp1->era->trparm); ?></td>
            <td class="ar"><input type="text" name="industry_arm" size="3" value="<?php echo percent($emp1->e_indarm); ?>" /></td></tr>
        <tr><td><?php echo lang($emp1->era->trplnd); ?></td>
            <td class="ar"><input type="text" name="industry_lnd" size="3" value="<?php echo percent($emp1->e_indlnd); ?>" /></td></tr>
        <tr><td><?php echo lang($emp1->era->trpfly); ?></td>
            <td class="ar"><input type="text" name="industry_fly" size="3" value="<?php echo percent($emp1->e_indfly); ?>" /></td></tr>
        <tr><td><?php echo lang($emp1->era->trpsea); ?></td>
            <td class="ar"><input type="text" name="industry_sea" size="3" value="<?php echo percent($emp1->e_indsea); ?>" /></td></tr>
        <tr><th colspan="2"><input type="hidden" name="action" value="industry" /><input type="submit" value="<?php echo lang('MANAGE_EMPIRE_INDUSTRY_SUBMIT'); ?>" /></th></tr>
        </table>
        </form>
    </td></tr>
<tr><td colspan="2" class="ac">
        <form method="post" action="?location=manage/empire">
        <table class="inputtable">
        <tr><th><?php echo lang('MANAGE_EMPIRE_VACATION_LABEL'); ?></th></tr>
<?php
if (VACATION_LIMIT == 0)
{
?>
        <tr><td class="ac"><?php echo lang('MANAGE_EMPIRE_VACATION_DISABLED'); ?></td></tr>
<?php
}
elseif (ROUND_CLOSING)
{
?>
        <tr><td class="ac"><?php echo lang('MANAGE_EMPIRE_VACATION_TOO_LATE'); ?></td></tr>
<?php
}
else
{
?>
        <tr><td class="ac"><?php echo lang('MANAGE_EMPIRE_VACATION_EXPLAIN', VACATION_LIMIT, VACATION_START); ?></td></tr>
        <tr><td class="ac"><?php echo checkbox('vacation_confirm', lang('MANAGE_EMPIRE_VACATION_CONFIRM')); ?></td></tr>
        <tr><th><input type="hidden" name="action" value="vacation" /><input type="submit" value="<?php echo lang('MANAGE_EMPIRE_VACATION_SUBMIT'); ?>" /></th></tr>
<?php
}
?>
        </table>
        </form>
    </td></tr>
</table>
<?php
page_footer();
?>
