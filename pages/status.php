<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: status.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'STATUS_TITLE';

page_header();

$emp = $emp1;
if ((CLAN_ENABLE) && (CLAN_VIEW_STAT)) do
{
	$target = fixInputNum(getFormVar('empire'));
	// skip if parameter is absent (or if they try to view themselves)
	if (($target == 0) || ($target == $emp1->e_id))
		break;

	if ($emp1->c_id == 0)
	{
		notice(lang('STATUS_OTHER_NO_CLAN'));
		break;
	}

	$clan_a = new prom_clan($emp1->c_id);
	$clan_a->load();

	// only leaders and assistant leaders are allowed to do this
	if (!in_array($emp1->e_id, array($clan_a->e_id_leader, $clan_a->e_id_asst)))
	{
		notice(lang('STATUS_OTHER_NOT_LEADER'));
		break;
	}

	$emp_a = new prom_empire($target);
	if (!$emp_a->load())
	{
		notice(lang('STATUS_OTHER_NO_EMPIRE'));
		break;
	}

	// and only for members of their own clan
	if ($emp_a->c_id != $emp1->c_id)
	{
		notice(lang('STATUS_OTHER_WRONG_CLAN'));
		break;
	}

	$emp = $emp_a;
} while (0);
notices();
$emp->printDetailedStats();

page_footer();
?>
