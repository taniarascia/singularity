<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: empedit.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_EMPEDIT_TITLE';

$needpriv = UFLAG_ADMIN;

$emp_id = fixInputNum(getFormVar('emp_id'));

if (in_array($action, array('modify')))
	$lock['emp2'] = $emp_id;

function fixInputNumMin ($num, $min)
{
	$num = fixInputNumSigned($num);
	if ($num < -$min)
		$num = -$min;
	return $num;
}

page_header();

if ($action == 'modify') do
{
	if (!isFormPost())
		break;
	if ($lock['emp2'] == 0)
	{
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}

	$e_race = fixInputNum(getFormVar('e_race'));
	if (prom_race::exists($e_race))
		$emp2->e_race = $e_race;

	$e_era = fixInputNum(getFormVar('e_era'));
	if (prom_era::exists($e_era))
		$emp2->e_era = $e_era;

	$emp2->e_turns += fixInputNumMin(getFormVar('e_turns'), $emp2->e_turns);
	$emp2->e_storedturns += fixInputNumMin(getFormVar('e_storedturns'), $emp2->e_storedturns);

	$emp2->e_cash += fixInputNumMin(getFormVar('e_cash'), $emp2->e_cash);
	$emp2->e_food += fixInputNumMin(getFormVar('e_food'), $emp2->e_food);
	$emp2->e_runes += fixInputNumMin(getFormVar('e_runes'), $emp2->e_runes);

	$emp2->e_health += fixInputNumMin(getFormVar('e_health'), $emp2->e_health);
	if ($emp2->e_health > 100)
		$emp2->e_health = 100;

	$emp2->e_trparm += fixInputNumMin(getFormVar('e_trparm'), $emp2->e_trparm);
	$emp2->e_trplnd += fixInputNumMin(getFormVar('e_trplnd'), $emp2->e_trplnd);
	$emp2->e_trpfly += fixInputNumMin(getFormVar('e_trpfly'), $emp2->e_trpfly);
	$emp2->e_trpsea += fixInputNumMin(getFormVar('e_trpsea'), $emp2->e_trpsea);
	$emp2->e_trpwiz += fixInputNumMin(getFormVar('e_trpwiz'), $emp2->e_trpwiz);
	$emp2->e_peasants += fixInputNumMin(getFormVar('e_peasants'), $emp2->e_peasants);

	$freeland = fixInputNumMin(getFormVar('e_freeland'), $emp2->e_freeland);

	$emp2->e_freeland += $freeland;
	$emp2->e_land += $freeland;

	notice(lang('ADMIN_EMPEDIT_COMPLETE'));
	logevent(varlist(array('emp_id'), get_defined_vars()));
} while (0);

notices(1);
?>
<form method="post" action="?location=admin/empedit"><div>
<table class="inputtable">
<tr><th><?php echo lang('ADMIN_EMPEDIT_LABEL_EMPIRE_ID'); ?></th><td><input type="text" name="emp_id" value="<?php echo $emp_id; ?>" /></td></tr>
<tr><th><?php echo label('ROW_RACE'); ?></th><td><?php
$races = prom_race::getNames();
$racelist = array();
$racelist[-1] = lang('ADMIN_EMPEDIT_RACE_UNCHANGED');
foreach ($races as $rid => $rname)
        $racelist[$rid] = $rname;
echo optionlist('e_race', $racelist);
?></td></tr>
<tr><th><?php echo label('ROW_ERA'); ?></th><td><?php
$eras = prom_era::getNames();
$eralist = array();
$eralist[-1] = lang('ADMIN_EMPEDIT_ERA_UNCHANGED');
foreach ($eras as $eid => $ename)
        $eralist[$eid] = $ename;
echo optionlist('e_era', $eralist);
?></td></tr>
<tr><th><?php echo label('ROW_TURNS'); ?></th><td><input type="text" name="e_turns" value="+0" /></td></tr>
<tr><th><?php echo label('ROW_STOREDTURNS'); ?></th><td><input type="text" name="e_storedturns" value="+0" /></td></tr>
<tr><th><?php echo label('ROW_CASH'); ?></th><td><input type="text" name="e_cash" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->food); ?></th><td><input type="text" name="e_food" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->runes); ?></th><td><input type="text" name="e_runes" value="+0" /></td></tr>
<tr><th><?php echo label('ROW_HEALTH'); ?></th><td><input type="text" name="e_health" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->trparm); ?></th><td><input type="text" name="e_trparm" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->trplnd); ?></th><td><input type="text" name="e_trplnd" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->trpfly); ?></th><td><input type="text" name="e_trpfly" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->trpsea); ?></th><td><input type="text" name="e_trpsea" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->trpwiz); ?></th><td><input type="text" name="e_trpwiz" value="+0" /></td></tr>
<tr><th><?php echo label($emp1->era->peasants); ?></th><td><input type="text" name="e_peasants" value="+0" /></td></tr>
<tr><th><?php echo label('ROW_LAND'); ?></th><td><input type="text" name="e_freeland" value="+0" /></td></tr>
</table>
<input type="hidden" name="action" value="modify" /><input type="submit" value="<?php echo lang('ADMIN_EMPEDIT_SUBMIT'); ?>" />
</div></form>
<?php
page_footer();
?>
