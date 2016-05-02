<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: land.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'LAND_TITLE';

page_header(); ?>

<br/><img src="/images/land.jpg" style="max-width: 550px;"/>
<br/>

<?php

if (ROUND_FINISHED)
	unavailable(lang('TURNS_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('TURNS_UNAVAILABLE_START'));

if ($action == 'explore') do
{
	if (!isFormPost())
		break;
	$turns = fixInputNum(getFormVar('land_turns'));
	$condensed = fixInputBool(getFormVar('land_condensed'));

	if (($turns < 0) || ($turns > $emp1->e_turns))
	{
		notice(lang('LAND_NOT_ENOUGH_TURNS'));
		break;
	}
	if ($turns == 0)
	{
		notice(lang('LAND_SPECIFY_TURNS'));
		break;
	}
	$turnresult = 0;
	$taken = $emp1->takeTurns($turns, 'land', TRUE, $condensed, $turnresult);
	if ($taken < 0)
		$taken = -$taken;
	notice(lang('LAND_COMPLETE', plural($turnresult, 'ACRES_SINGLE', 'ACRES_PLURAL'), plural($taken, 'TURNS_SINGLE', 'TURNS_PLURAL')));
	logevent(varlist(array('turnresult', 'turns', 'taken'), get_defined_vars()));
} while (0);
notices();
?>
<?php echo lang('LAND_HEADER', plural($emp1->give_land(), 'ACRES_SINGLE', 'ACRES_PLURAL')); ?>
<form method="post" action="?location=land">
<table class="inputtable">
<tr><td><?php echo lang('LAND_LABEL'); ?></td>
    <td><input type="text" name="land_turns" size="5" value="0" /></td>
    <td><?php echo checkbox('land_condensed', lang('COMMON_TURNS_SUMMARY'), 1, TRUE); ?></td></tr>
<tr><td colspan="3" class="ac"><input type="hidden" name="action" value="explore" /><input type="submit" value="<?php echo lang('LAND_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
