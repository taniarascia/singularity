<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: farm.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'FARM_TITLE';

page_header();

if (ROUND_FINISHED)
	unavailable(lang('TURNS_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('TURNS_UNAVAILABLE_START'));

if ($action == 'farm') do
{
	if (!isFormPost())
		break;
	$turns = fixInputNum(getFormVar('farm_turns'));
	$condensed = fixInputBool(getFormVar('farm_condensed'));
	if (($turns < 0) || ($turns > $emp1->e_turns))
	{
		notice(lang('FARM_NOT_ENOUGH_TURNS'));
		break;
	}
	if ($turns == 0)
	{
		notice(lang('FARM_SPECIFY_TURNS'));
		break;
	}
	$turnresult = 0;
	$taken = $emp1->takeTurns($turns, 'farm', TRUE, $condensed, $turnresult);
	if ($taken < 0)
		$taken = -$taken;
	notice(lang('FARM_COMPLETE', number($turnresult), $emp1->era->food, plural($taken, 'TURNS_SINGLE', 'TURNS_PLURAL')));
	logevent(varlist(array('turnresult', 'turns', 'taken'), get_defined_vars()));
} while (0);
notices();
?>
<?php echo lang('FARM_HEADER', $emp1->era->bldfood, $emp1->era->food); ?>

<br/><img src="http://www.manaleak.com/mtguk/files/2014/02/stp.jpg" style="max-width: 550px;"/>
<form method="post" action="?location=farm">
<table class="inputtable">
<tr><td><?php echo lang('FARM_LABEL'); ?></td>
    <td><input type="text" name="farm_turns" size="5" value="0" /></td>
    <td><?php echo checkbox('farm_condensed', lang('COMMON_TURNS_SUMMARY'), 1, TRUE); ?></td></tr>
<tr><td colspan="3" class="ac"><input type="hidden" name="action" value="farm" /><input type="submit" value="<?php echo lang('FARM_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
