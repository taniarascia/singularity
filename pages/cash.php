<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: cash.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'CASH_TITLE';

page_header(); ?>

<br/><img src="/images/cash.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('TURNS_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('TURNS_UNAVAILABLE_START'));

if ($action == 'cash') do
{
	if (!isFormPost())
		break;
	$turns = fixInputNum(getFormVar('cash_turns'));
	$condensed = fixInputBool(getFormVar('cash_condensed'));
	if (($turns < 0) || ($turns > $emp1->e_turns))
	{
		notice(lang('CASH_NOT_ENOUGH_TURNS'));
		break;
	}
	if ($turns == 0)
	{
		notice(lang('CASH_SPECIFY_TURNS'));
		break;
	}
	$turnresult = 0;
	$taken = $emp1->takeTurns($turns, 'cash', TRUE, $condensed, $turnresult);
	if ($taken < 0)
		$taken = -$taken;
	notice(lang('CASH_COMPLETE', money($turnresult), plural($taken, 'TURNS_SINGLE', 'TURNS_PLURAL')));
	logevent(varlist(array('turnresult', 'turns', 'taken'), get_defined_vars()));
} while (0);
notices();
?>
<?php echo lang('CASH_HEADER', $emp1->era->peasants); ?>

<form method="post" action="?location=cash">
<table class="inputtable">
<tr><td><?php echo lang('CASH_LABEL'); ?></td>
    <td><input type="text" name="cash_turns" size="5" value="0" /></td>
    <td><?php echo checkbox('cash_condensed', lang('COMMON_TURNS_SUMMARY'), 1, TRUE); ?></td></tr>
<tr><td colspan="3" class="ac"><input type="hidden" name="action" value="cash" /><input type="submit" value="<?php echo lang('CASH_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
