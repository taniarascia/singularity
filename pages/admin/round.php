<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: round.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_ROUND_TITLE';

$needpriv = UFLAG_ADMIN;

if ($action == 'update')
	$db->lockAll();

function resetRoundTimes ($_begin, $_closing, $_end)
{
	global $world;

	$world->round_time_begin = $_begin;
	$world->round_time_closing = $_closing;
	$world->round_time_end = $_end;

	// set next timestamps for giving out turns
	if (CUR_TIME < $world->round_time_begin)
	{
		$world->turns_next = $world->round_time_begin + 60 * TURNS_OFFSET;
		$world->turns_next_hourly = $world->round_time_begin + 60 * TURNS_OFFSET_HOURLY;
		$world->turns_next_daily = $world->round_time_begin + 60 * TURNS_OFFSET_DAILY;
	}
	elseif (CUR_TIME < $world->round_time_end)
	{
		$world->turns_next = $world->round_time_begin + 60 * TURNS_OFFSET;
		$world->turns_next_hourly = $world->round_time_begin + 60 * TURNS_OFFSET_HOURLY;
		$world->turns_next_daily = $world->round_time_begin + 60 * TURNS_OFFSET_DAILY;

		while ($world->turns_next < CUR_TIME)
			$world->turns_next += 60 * TURNS_FREQ;
		while ($world->turns_next_hourly < CUR_TIME)
			$world->turns_next_hourly += 60 * 60;
		while ($world->turns_next_daily < CUR_TIME)
			$world->turns_next_daily += 60 * 60 * 24;
	}
	else
	{
		$world->turns_next = 0;
		$world->turns_next_hourly = 0;
		$world->turns_next_daily = 0;
	}
}

page_header();

if ($action == 'update') do
{
	if (!isFormPost())
		break;
	$begin = getFormVar('round_begin');
	$closing = getFormVar('round_closing');
	$end = getFormVar('round_end');

	$_begin = strtotime($begin);
	$_closing = strtotime($closing);
	$_end = strtotime($end);

	if ($_begin == FALSE)
	{
		notice(lang('SETUP_BAD_START'));
		break;
	}
	if ($_closing == FALSE)
	{
		notice(lang('SETUP_BAD_COOLDOWN'));
		break;
	}
	if ($_end == FALSE)
	{
		notice(lang('SETUP_BAD_END'));
		break;
	}

	if ($_begin >= $_closing)
	{
		notice(lang('SETUP_COOLDOWN_AFTER_START'));
		break;
	}

	if ($_closing + (VACATION_START + VACATION_LIMIT) * 3600 >= $_end)
	{
		notice(lang('SETUP_COOLDOWN_LENGTH', ceil((VACATION_START + VACATION_LIMIT) / 24)));
		break;
	}

	// if the reset checkbox isn't selected, then reset the round dates and exit
	$doreset = fixInputBool(getFormVar('round_reset'));
	if (!$doreset)
	{
		resetRoundTimes($_begin, $_closing, $_end);
		// since we didn't use $lock['world'], need to save these explicitly
		$world->save();

		notice(lang('ADMIN_ROUND_DATES_UPDATED'));
		logevent(varlist(array('begin', 'closing', 'end'), get_defined_vars()));
		break;
	}

	$confirm = fixInputBool(getFormVar('reset_confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_ROUND_RESTART_NEED_CONFIRM'));
		break;
	}
	$list = getFormArr('reset_acctlist');
	$newemps = array();
	foreach ($list as $id)
	{
		$name = htmlspecialchars(getFormVar('reset_empname_'. $id));
		if (!strlen($name))
		{
			notice(lang('ADMIN_ROUND_RESTART_NEED_NAMES'));
			break 2;
		}
		$newemps[$id] = $name;
	}
	if (count($newemps) == 0)
	{
		notice(lang('ADMIN_ROUND_RESTART_NEED_EMPIRES'));
		break;
	}

	// empty out all database tables in an appropriate order
	// always clear out clans, even if they're not enabled
	if (!$db->clearTable(SESSION_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', SESSION_TABLE));
		break;
	}
	if (!$db->clearTable(EMPIRE_NEWS_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', EMPIRE_NEWS_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_INVITE_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_INVITE_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_RELATION_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_RELATION_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_MESSAGE_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_MESSAGE_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_TOPIC_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_TOPIC_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_NEWS_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_NEWS_TABLE));
		break;
	}
	if (!$db->clearTable(CLAN_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', CLAN_TABLE));
		break;
	}
	if (!$db->clearTable(LOTTERY_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', LOTTERY_TABLE));
		break;
	}
	if (!$db->clearTable(MARKET_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', MARKET_TABLE));
		break;
	}
	if (!$db->clearTable(EMPIRE_MESSAGE_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', EMPIRE_MESSAGE_TABLE));
		break;
	}
	if (!$db->clearTable(EMPIRE_EFFECT_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', EMPIRE_EFFECT_TABLE));
		break;
	}
	if (!$db->clearTable(EMPIRE_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', EMPIRE_TABLE));
		break;
	}
	if (!$db->clearTable(VAR_ADJUST_TABLE))
	{
		notice(lang('ADMIN_ROUND_RESTART_ERROR_CLEAR_TABLE', VAR_ADJUST_TABLE));
		break;
	}

	// delete any locks belonging to empires or clans that just got deleted
	$db->deleteLock(ENT_EMPIRE, 0);
	$db->deleteLock(ENT_CLAN, 0);

	// reinitialize all appropriate world variables
	$world->lotto_current_jackpot = LOTTERY_JACKPOT;
	$world->lotto_yesterday_jackpot = LOTTERY_JACKPOT;
	$world->lotto_last_picked = 0;
	$world->lotto_last_winner = 0;
	$world->lotto_jackpot_increase = 0;

	// reset empire stored in session
	$_SESSION['empire'] = $want_emp = 0;
	// create new empires
	foreach ($newemps as $num => $name)
	{
		$user = new prom_user($num);
		$user->load();
		$emp = new prom_empire();
		$emp->create($user, $name, RACE_HUMAN);
		// if we're creating a new empire for your account, then bind the session to it
		if ($user->u_id == $user1->u_id)
			$_SESSION['empire'] = $emp->e_id;
		else	$want_emp = $emp->e_id;
		// copy privileges from user account, and pre-mark them as validated
		if ($user->u_flags & (UFLAG_ADMIN | UFLAG_MOD))
			$emp->setFlag(EFLAG_ADMIN);
		$emp->setFlag(EFLAG_VALID);
		$emp->save();
		$user->save();
	}
	// if you didn't make a new empire for yourself, set-user yourself to the last account anyways
	// you'll be logging out shortly anyways, so it works out
	if (!$_SESSION['empire'])
		$_SESSION['empire'] = $want_emp;

	// all done - reset the round times and re-enable signups
	resetRoundTimes($_begin, $_closing, $_end);
	$world->save();

	notice(lang('ADMIN_ROUND_RESTART_COMPLETE'));
	logevent(varlist(array('begin', 'closing', 'end', 'doreset'), get_defined_vars()));
} while (0);

notices();
?>
<form method="post" action="?location=admin/round">
<table class="inputtable">
<tr><th class="ar"><?php echo lang('LABEL_ROUND_START'); ?></th>
    <td><input type="text" name="round_begin" value="<?php echo gmdate('Y/m/d H:i:s O', $world->round_time_begin); ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_ROUND_COOLDOWN'); ?></th>
    <td><input type="text" name="round_closing" value="<?php echo gmdate('Y/m/d H:i:s O', $world->round_time_closing); ?>" size="24" /></td></tr>
<tr><th class="ar"><?php echo lang('LABEL_ROUND_END'); ?></th>
    <td><input type="text" name="round_end" value="<?php echo gmdate('Y/m/d H:i:s O', $world->round_time_end); ?>" size="24" /></td></tr>
</table>
<table>
<tr><td colspan="6" class="ac"><?php echo checkbox('round_reset', lang('ADMIN_ROUND_RESTART')); ?></td></tr>
<tr><th><?php echo lang('COLUMN_ADMIN_USERID'); ?></th>
    <th><?php echo lang('COLUMN_ADMIN_NICKNAME'); ?></th>
    <th><?php echo lang('COLUMN_ADMIN_USERNAME'); ?></th>
    <th><?php echo lang('COLUMN_ADMIN_EMAIL'); ?></th>
    <th><?php echo lang('COLUMN_ADMIN_FLAGS'); ?></th>
    <th><?php echo lang('ADMIN_ROUND_RESTART_CREATE_EMPIRE'); ?></th></tr>
<?php
$q = $db->prepare('SELECT u_id,u_name,u_username,u_email,u_flags FROM '. USER_TABLE .' WHERE u_flags & ? != 0 ORDER BY u_id ASC');
$q->bindIntValue(1, UFLAG_ADMIN | UFLAG_MOD);
$q->execute() or warning('Failed to fetch privileged user list', 0);
$users = $q->fetchAll();
foreach ($users as $user)
{
?>
<tr><th class="ar"><?php echo $user['u_id']; ?></th>
    <td class="al"><?php echo $user['u_name']; ?></td>
    <td class="ar"><?php echo htmlspecialchars($user['u_username']); ?></td>
    <td class="ac"><?php echo $user['u_email']; ?></td>
    <td class="ac"><?php
	echo ($user['u_flags'] & UFLAG_ADMIN) ? 'A' : '-';
	echo ($user['u_flags'] & UFLAG_MOD) ? 'M' : '-';
	echo ($user['u_flags'] & UFLAG_DISABLE) ? 'D' : '-';
	echo ($user['u_flags'] & UFLAG_VALID) ? 'V' : '-';
	echo ($user['u_flags'] & UFLAG_CLOSED) ? 'C' : '-';
?></td>
    <td><?php echo checkbox('reset_acctlist[]', '', $user['u_id']); ?> <input type="text" name="reset_empname_<?php echo $user['u_id']; ?>" value="" /></td></tr>
<?php
}
?>
<tr><td colspan="5"><?php echo checkbox('reset_confirm', lang('ADMIN_ROUND_RESTART_CONFIRM')); ?></td>
    <td><input type="hidden" name="action" value="update" /><input type="submit" value="<?php echo lang('ADMIN_ROUND_SUBMIT'); ?>" /></td></tr>
<tr><td colspan="6" class="ac"><b><?php echo lang('ADMIN_ROUND_RESTART_REMIND_HISTORY'); ?></b></td></tr>
</table>
</form>
<?php
page_footer();
?>
