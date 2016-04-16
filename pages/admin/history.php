<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: history.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_HISTORY_TITLE';

$needpriv = UFLAG_MOD;

// lock everything
if ($action == 'record')
	$db->lockAll();

page_header();

if ($action == 'delete') do
{
	if (!isFormPost())
		break;
	if (!($user1->u_flags & UFLAG_ADMIN))
	{
		notice(lang('ADMIN_HISTORY_DELETE_NEED_PERMISSION'));
		break;
	}
	$round_id = fixInputNum(getFormVar('round_id'));
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_HISTORY_DELETE_NEED_CONFIRM'));
		break;
	}

	if ($db->queryCell('SELECT COUNT(*) FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id = ?', array($round_id)) == 0)
	{
		notice(lang('ADMIN_HISTORY_DELETE_NEED_ROUND'));
		break;
	}

	$q = $db->prepare('DELETE FROM '. HISTORY_CLAN_TABLE .' WHERE hr_id = ?');
	$q->bindIntValue(1, $round_id);
	$q->execute();

	$q = $db->prepare('DELETE FROM '. HISTORY_EMPIRE_TABLE .' WHERE hr_id = ?');
	$q->bindIntValue(1, $round_id);
	$q->execute();

	$q = $db->prepare('DELETE FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id = ?');
	$q->bindIntValue(1, $round_id);
	$q->execute();

	notice(lang('ADMIN_HISTORY_DELETE_COMPLETE'));
	logevent(varlist(array('round_id'), get_defined_vars()));
} while (0);

if ($action == 'update') do
{
	if (!isFormPost())
		break;
	$round_id = fixInputNum(getFormVar('round_id'));
	$q = $db->prepare('SELECT hr_flags FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id = ?');
	$q->bindIntValue(1, $round_id);
	$q->execute();
	$round = $q->fetch();
	if (!$round)
	{
		notice(lang('ADMIN_HISTORY_NEED_ROUND'));
		break;
	}

	// return to Edit page afterwards
	$action = 'edit';

	$name = getFormVar('name');
	$description = getFormVar('description');
	$startdate = getFormVar('startdate');
	$stopdate = getFormVar('stopdate');
	$_startdate = strtotime($startdate);
	$_stopdate = strtotime($stopdate);

	if ($round['hr_flags'] & HRFLAG_CLANS)
	{
		$smallclansize = fixInputNum(getFormVar('smallclansize'));
		$smallclans = $db->queryCell('SELECT COUNT(*) FROM '. HISTORY_CLAN_TABLE .' WHERE hr_id = ? AND hc_members < ?', array($round_id, $smallclansize));
	}
	else
	{
		$smallclansize = 0;
		$smallclans = 0;
	}

	$q = $db->prepare('UPDATE '. HISTORY_ROUND_TABLE .' SET hr_name = ?, hr_description = ?, hr_startdate = ?, hr_stopdate = ?, hr_smallclansize = ?, hr_smallclans = ? WHERE hr_id = ?');
	$q->bindStrValue(1, $name);
	$q->bindStrValue(2, $description);
	$q->bindIntValue(3, $_startdate);
	$q->bindIntValue(4, $_stopdate);
	$q->bindIntValue(5, $smallclansize);
	$q->bindIntValue(6, $smallclans);
	$q->bindIntValue(7, $round_id);
	$q->execute();

	notice(lang('ADMIN_HISTORY_UPDATE_COMPLETE'));
	logevent(varlist(array('round_id', 'name', 'startdate', 'stopdate', 'smallclansize'), get_defined_vars()));
} while (0);

if ($action == 'edit') do
{
	$round_id = fixInputNum(getFormVar('round_id'));
	$q = $db->prepare('SELECT * FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id = ?');
	$q->bindIntValue(1, $round_id);
	$q->execute();
	$round = $q->fetch();
	if (!$round)
	{
		notice(lang('ADMIN_HISTORY_NEED_ROUND'));
		break;
	}
?>
<form method="post" action="?location=admin/history">
<table class="inputtable">
<tr><th colspan="4"><?php echo lang('ADMIN_HISTORY_EDIT_HEADER'); ?><input type="hidden" name="round_id" value="<?php echo $round_id; ?>" /></th></tr>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_NAME'); ?></th><td><input type="text" name="name" value="<?php echo htmlspecialchars($round['hr_name']); ?>" size="24" /></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_START'); ?></th><td><input type="text" name="startdate" value="<?php echo gmdate('Y/m/d H:i:s O', $round['hr_startdate']); ?>" size="24" /></td></tr>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_FLAGS'); ?></th><td><?php echo checkbox('', lang('ADMIN_HISTORY_FLAG_CLANS'), 0, $round['hr_flags'] & HRFLAG_CLANS, FALSE); ?> - <?php echo checkbox('', lang('ADMIN_HISTORY_FLAG_SCORE'), 0, $round['hr_flags'] & HRFLAG_SCORE, FALSE); ?></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_STOP'); ?></th><td><input type="text" name="stopdate" value="<?php echo gmdate('Y/m/d H:i:s O', $round['hr_stopdate']); ?>" size="24" /></td></tr>
<tr><th colspan="4"><?php echo lang('ADMIN_HISTORY_LABEL_DESC'); ?></th></tr>
<tr><td colspan="4" class="ac"><textarea rows="4" cols="60" name="description"><?php echo htmlspecialchars($round['hr_description']); ?></textarea></td></tr>
<tr><th colspan="4"><?php echo lang('ADMIN_HISTORY_EDIT_STATS'); ?></th></tr>
<?php	if ($round['hr_flags'] & HRFLAG_CLANS) { ?>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_SMALLCLANSIZE'); ?></th><td><input type="text" name="smallclansize" value="<?php echo $round['hr_smallclansize']; ?>" size="3" /></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_SMALLCLANS'); ?></th><td><?php echo $round['hr_smallclans']; ?></td></tr>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_UNCLANNED'); ?></th><td><?php echo $round['hr_nonclanempires']; ?></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_ALLCLANS'); ?></th><td><?php echo $round['hr_allclans']; ?></td></tr>
<?php	} ?>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_ALLEMPIRES'); ?></th><td><?php echo $round['hr_allempires']; ?></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_DEADEMPIRES'); ?></th><td><?php echo $round['hr_deadempires']; ?></td></tr>
<tr><th><?php echo lang('ADMIN_HISTORY_LABEL_LIVEEMPIRES'); ?></th><td><?php echo $round['hr_liveempires']; ?></td>
    <th><?php echo lang('ADMIN_HISTORY_LABEL_DELEMPIRES'); ?></th><td><?php echo $round['hr_delempires']; ?></td></tr>
<tr><th colspan="4"><input type="hidden" name="action" value="update" /><input type="submit" value="<?php echo lang('ADMIN_HISTORY_EDIT_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
	if ($user1->u_flags & UFLAG_ADMIN)
	{
?>
<hr />
<form method="post" action="?location=admin/history">
<table class="inputtable">
<tr><th><?php echo lang('ADMIN_HISTORY_DELETE_HEADER'); ?><input type="hidden" name="round_id" value="<?php echo $round['hr_id']; ?>" /></th></tr>
<tr><td><?php echo checkbox('confirm', lang('ADMIN_HISTORY_DELETE_CONFIRM')); ?></td></tr>
<tr><th><input type="hidden" name="action" value="delete" /><input type="submit" value="<?php echo lang('ADMIN_HISTORY_DELETE_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
	}
?>
<hr />
<?php
} while (0);

if ($action == 'record') do
{
	if (!isFormPost())
		break;
	// only Admins can record history
	if (!($user1->u_flags & UFLAG_ADMIN))
	{
		notice(lang('ADMIN_HISTORY_RECORD_NEED_PERMISSION'));
		break;
	}
	// with confirmation
	$confirm = fixInputBool(getFormVar('record_confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_HISTORY_RECORD_NEED_CONFIRM'));
		break;
	}
	// and the round needs to have ended first
	if (CUR_TIME < $world->round_time_end)
	{
		notice(lang('ADMIN_HISTORY_RECORD_TOO_EARLY'));
		break;
	}

	$history_clans = array();
	$history_stats = array('smallclans' => 0, 'allclans' => 0, 'nonclanempires' => 0, 'liveempires' => 0, 'deadempires' => 0, 'delempires' => 0, 'allempires' => 0);

	$roundflags = 0;
	if (CLAN_ENABLE)
		$roundflags |= HRFLAG_CLANS;
	if (SCORE_ENABLE)
		$roundflags |= HRFLAG_SCORE;
	$q = $db->prepare('INSERT INTO '. HISTORY_ROUND_TABLE .' (hr_name, hr_startdate, hr_stopdate, hr_flags, hr_smallclansize) VALUES (?,?,?,?,?)');
	$q->bindStrValue(1, GAME_TITLE);
	$q->bindIntValue(2, $world->round_time_begin);
	$q->bindIntValue(3, $world->round_time_end);
	$q->bindIntValue(4, $roundflags);
	$q->bindIntValue(5, CLAN_ENABLE ? CLANSTATS_MINSIZE : 0);
	if (!$q->execute())
	{
		notice(lang('ADMIN_HISTORY_RECORD_FAIL_ADD'));
		break;
	}
	$round_id = $db->lastInsertId($db->getSequence(HISTORY_ROUND_TABLE));

	// immediately unlink all dead/disabled/deleted empires

	$q = $db->prepare('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 AND e_flags & ? = 0 AND ((e_land = 0) OR (e_flags & ? != 0))');
	$q->bindIntValue(1, EFLAG_ADMIN);
	$q->bindIntValue(2, EFLAG_DISABLE | EFLAG_DELETE);
	$q->execute();
	$emps = $q->fetchAll();
	foreach ($emps as $data)
	{
		$emp_a = new prom_empire($data['e_id']);
		$emp_a->load();
		$emp_a->u_oldid = $emp_a->u_id;
		$emp_a->u_id = 0;
		if (CLAN_ENABLE)
		{
			$emp_a->c_oldid = $emp_a->c_id;
			$emp_a->c_id = 0;
		}
		$emp_a->save();
		$emp_a = NULL;
	}

	// and update all rankings to remove any gaps
	$db->query('UPDATE '. EMPIRE_TABLE .' SET e_rank = 0 WHERE u_id = 0');
	if (SCORE_ENABLE)
		$q = $db->query('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 ORDER BY e_score DESC, e_networth DESC, e_id ASC');
	else	$q = $db->query('SELECT e_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0 ORDER BY e_networth DESC, e_id ASC');
	$emps = $q->fetchAll();
	$urank = 0;
	$rq = $db->prepare('UPDATE '. EMPIRE_TABLE .' SET e_rank = ? WHERE e_id = ?');
	foreach ($emps as $emp)
	{
		$urank++;
		$rq->bindIntValue(1, $urank);
		$rq->bindIntValue(2, $emp['e_id']);
		if (!$rq->execute())
			notice(lang('ADMIN_HISTORY_RECORD_FAIL_RANK', prenum($emp['e_id'])));
	}

	// update statistics for each user account, and save relevant empire records to history
	$ranks = array();
	$q = $db->query('SELECT e_id,u_id,u_oldid,e_offsucc,e_offtotal,e_defsucc,e_deftotal,e_kills,e_rank,e_flags,e_name,c_id,e_score,e_networth,e_land,e_vacation,e_turnsused,e_race,e_era FROM '. EMPIRE_TABLE);
	$emps = $q->fetchAll();
	$maxrank = $db->queryCell('SELECT MAX(e_rank) FROM '. EMPIRE_TABLE);
	foreach ($emps as $data)
	{
		$emp2 = new prom_empire($data['e_id']);
		$emp2->initdata($data);

		if ($emp2->u_id != 0)
			$id = $emp2->u_id;
		else	$id = $emp2->u_oldid;

		$q = $db->prepare('UPDATE '. USER_TABLE .' SET u_kills = u_kills + ?, u_deaths = u_deaths + ?, u_offsucc = u_offsucc + ?, u_offtotal = u_offtotal + ?, u_defsucc = u_defsucc + ?, u_deftotal = u_deftotal + ? WHERE u_id = ?');
		$q->bindIntValue(1, $emp2->e_kills);
		if ($emp2->u_id != 0)
			$q->bindIntValue(2, 0);
		else	$q->bindIntValue(2, 1);
		$q->bindIntValue(3, $emp2->e_offsucc);
		$q->bindIntValue(4, $emp2->e_offtotal);
		$q->bindIntValue(5, $emp2->e_defsucc);
		$q->bindIntValue(6, $emp2->e_deftotal);
		$q->bindIntValue(7, $id);
		if (!$q->execute())
			notice(lang('ADMIN_HISTORY_RECORD_FAIL_USERSTATS', $emp2));

		if (!isset($ranks[$id]))
			$ranks[$id] = $maxrank + 1;
		if (($emp2->u_id != 0) && ($emp2->e_rank < $ranks[$emp2->u_id]))
			$ranks[$emp2->u_id] = $emp2->e_rank;

		$history_stats['allempires']++;
		// count kills by non-admin empires only - kills by admins are really just glorified deletions
		if ($emp2->e_kills && !($emp2->e_flags & EFLAG_ADMIN))
		{
			$history_stats['deadempires'] += $emp2->e_kills;
			$history_stats['delempires'] -= $emp2->e_kills;
		}
		// ignore empires which were killed/disabled/deleted
		if ($emp2->u_id == 0)
		{
			$history_stats['delempires']++;
			continue;
		}
		$history_stats['liveempires']++;
		$heflags = 0;
		if ($emp2->e_flags & EFLAG_ADMIN)
			$heflags |= HEFLAG_ADMIN;
		if ($emp2->is_protected() || $emp2->is_vacation())
			$heflags |= HEFLAG_PROTECT;
		$q = $db->prepare('INSERT INTO '. HISTORY_EMPIRE_TABLE .' (hr_id,he_flags,u_id,he_id,he_name,he_race,he_era,hc_id,he_offsucc,he_offtotal,he_defsucc,he_deftotal,he_kills,he_score,he_networth,he_land,he_rank) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$q->bindIntValue(1, $round_id);
		$q->bindIntValue(2, $heflags);
		$q->bindIntValue(3, $emp2->u_id);
		$q->bindIntValue(4, $emp2->e_id);
		$q->bindIntValue(5, $emp2->e_name);
		$q->bindIntValue(6, prom_race::lookup($emp2->e_race, 'name'));
		$q->bindIntValue(7, prom_era::lookup($emp2->e_era, 'name'));
		$q->bindIntValue(8, CLAN_ENABLE ? $emp2->c_id : 0);
		$q->bindIntValue(9, $emp2->e_offsucc);
		$q->bindIntValue(10, $emp2->e_offtotal);
		$q->bindIntValue(11, $emp2->e_defsucc);
		$q->bindIntValue(12, $emp2->e_deftotal);
		$q->bindIntValue(13, $emp2->e_kills);
		$q->bindIntValue(14, SCORE_ENABLE ? $emp2->e_score : 0);
		$q->bindIntValue(15, $emp2->e_networth);
		$q->bindIntValue(16, $emp2->e_land);
		$q->bindIntValue(17, $emp2->e_rank);
		if (!$q->execute())
			notice(lang('ADMIN_HISTORY_RECORD_FAIL_EMP', $emp2));
		if (CLAN_ENABLE)
		{
			if ($emp2->c_id)
			{
				if (!isset($history_clans[$emp2->c_id]))
					$history_clans[$emp2->c_id] = array('totalnet' => 0, 'members' => 0);
				// Clan members/totalnet need to be recalculated, since members may have just been discarded above
				$history_clans[$emp2->c_id]['totalnet'] += $emp2->e_networth;
				$history_clans[$emp2->c_id]['members']++;
			}
			else	$history_stats['nonclanempires']++;
		}
	}
	// ranks are stored as a percentile - 0.00 is the worst (currently only for those who have never survived), and 1.00 is the best
	foreach ($ranks as $uid => $rank)
	{
		// if the player died, just skip this entirely
		if ($rank > $maxrank)
			continue;

		$q = $db->prepare('UPDATE '. USER_TABLE .' SET u_avgrank = ((u_avgrank * u_sucplays) + ?) / (u_sucplays + 1), u_sucplays = u_sucplays + 1 WHERE u_id = ?');
		// last place is just above 0%
		$relrank = 1 - ($rank - 1) / $maxrank;
		$q->bindFltValue(1, $relrank);
		$q->bindIntValue(2, $uid);
		if (!$q->execute())
			notice(lang('ADMIN_HISTORY_RECORD_FAIL_AVGRANK', prenum($uid)));

		$q = $db->prepare('UPDATE '. USER_TABLE .' SET u_bestrank = ? WHERE u_bestrank < ? AND u_id = ?');
		$q->bindFltValue(1, $relrank);
		$q->bindFltValue(2, $relrank);
		$q->bindIntValue(3, $uid);
		if (!$q->execute())
			notice(lang('ADMIN_HISTORY_RECORD_FAIL_BESTRANK', prenum($uid)));
	}
	if (!$db->query('UPDATE '. USER_TABLE .' SET u_numplays = u_numplays + 1 WHERE u_id IN (SELECT DISTINCT u_id FROM '. EMPIRE_TABLE .' WHERE u_id != 0) OR u_id IN (SELECT DISTINCT u_oldid FROM '. EMPIRE_TABLE .' WHERE u_oldid != 0)'))
		notice(lang('ADMIN_HISTORY_RECORD_FAIL_PLAYSTATS'));
	// store clans in history
	if (CLAN_ENABLE)
	{
		$q = $db->query('SELECT c_id,c_name,c_title FROM '. CLAN_TABLE);
		$clans = $q->fetchAll();
		foreach ($clans as $data)
		{
			$clan2 = new prom_clan($data['c_id']);
			$clan2->initdata($data);
			// skip deleted or now-empty clans
			if (!isset($history_clans[$clan2->c_id]) || ($history_clans[$clan2->c_id]['members'] < 1))
				continue;

			$q = $db->prepare('INSERT INTO '. HISTORY_CLAN_TABLE .' (hr_id,hc_id,hc_members,hc_name,hc_title,hc_totalnet) VALUES (?,?,?,?,?,?)');
			$q->bindIntValue(1, $round_id);
			$q->bindIntValue(2, $clan2->c_id);
			$q->bindIntValue(3, $history_clans[$clan2->c_id]['members']);
			$q->bindIntValue(4, $clan2->c_name);
			$q->bindIntValue(5, $clan2->c_title);
			$q->bindIntValue(6, $history_clans[$clan2->c_id]['totalnet']);
			if (!$q->execute())
				notice(lang('ADMIN_HISTORY_RECORD_FAIL_CLAN', $clan2));
			$history_stats['allclans']++;
			if ($history_clans[$clan2->c_id]['members'] < CLANSTATS_MINSIZE)
				$history_stats['smallclans']++;
		}
	}
	$q = $db->prepare('UPDATE '. HISTORY_ROUND_TABLE .' SET hr_smallclans = ?, hr_allclans = ?, hr_nonclanempires = ?, hr_liveempires = ?, hr_deadempires = ?, hr_delempires = ?, hr_allempires = ? WHERE hr_id = ?');
	$q->bindIntValue(1, $history_stats['smallclans']);
	$q->bindIntValue(2, $history_stats['allclans']);
	$q->bindIntValue(3, $history_stats['nonclanempires']);
	$q->bindIntValue(4, $history_stats['liveempires']);
	$q->bindIntValue(5, $history_stats['deadempires']);
	$q->bindIntValue(6, $history_stats['delempires']);
	$q->bindIntValue(7, $history_stats['allempires']);
	$q->bindIntValue(8, $round_id);
	if (!$q->execute())
		notice(lang('ADMIN_HISTORY_RECORD_FAIL_SAVE'));

	notice(lang('ADMIN_HISTORY_RECORD_COMPLETE'));
	logevent();
} while (0);
notices(1);
?>
<table>
<tr><th><?php echo lang('ADMIN_HISTORY_COLUMN_ID'); ?></th>
    <th><?php echo lang('ADMIN_HISTORY_COLUMN_NAME'); ?></th>
    <th><?php echo lang('ADMIN_HISTORY_COLUMN_DESC'); ?></th>
    <th><?php echo lang('ADMIN_HISTORY_COLUMN_START'); ?></th>
    <th><?php echo lang('ADMIN_HISTORY_COLUMN_STOP'); ?></th></tr>
<?php
$q = $db->query('SELECT * FROM '. HISTORY_ROUND_TABLE .' ORDER BY hr_id ASC') or warning('Failed to fetch round list', 0);
$rounds = $q->fetchAll();
foreach ($rounds as $round)
{
?>
<tr><th class="ar"><a href="?location=admin/history&amp;action=edit&amp;round_id=<?php echo $round['hr_id']; ?>"><?php echo $round['hr_id']; ?></a></th>
    <td class="al"><b><?php echo htmlspecialchars($round['hr_name']); ?></b></td>
    <td class="al"><?php echo htmlspecialchars(truncate($round['hr_description'], 50)); ?></td>
    <td class="ac"><?php echo gmdate('Y/m/d', $round['hr_startdate']); ?></td>
    <td class="ac"><?php echo gmdate('Y/m/d', $round['hr_stopdate']); ?></td></tr>
<?php
}
?>
</table>
<?php
if (($user1->u_flags & UFLAG_ADMIN) && (CUR_TIME >= $world->round_time_end))
{
?>
<form method="post" action="?location=admin/history">
<table>
<tr><td><?php echo checkbox('record_confirm', lang('ADMIN_HISTORY_RECORD_CONFIRM')); ?></td></tr>
<tr><th><input type="hidden" name="action" value="record" /><input type="submit" value="<?php echo lang('ADMIN_HISTORY_RECORD_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
}
page_footer();
?>
