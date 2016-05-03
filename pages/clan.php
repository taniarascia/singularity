<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clan.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'CLAN_TITLE';

// are we joining a clan?
if (($action == 'join') || ($action == 'invite'))
	$lock['clan1'] = fixInputNum(getFormVar('join_id'));

// no? then try to lock the one we're in (assuming we're in one)
if ($lock['clan1'] == 0)
	$lock['clan1'] = -1;

page_header();

if (ROUND_FINISHED)
	unavailable(lang('CLAN_UNAVAILABLE_END'));
if (!ROUND_STARTED)
	unavailable(lang('CLAN_UNAVAILABLE_START'));
if ($emp1->is_protected())
	unavailable(lang('CLAN_UNAVAILABLE_PROTECT'));
if ($emp1->e_flags & EFLAG_ADMIN)
	unavailable(lang('CLAN_UNAVAILABLE_ADMIN'));
if (!CLAN_ENABLE)
	unavailable(lang('CLAN_UNAVAILABLE_CONFIG'));

if ($action == 'create') do
{
	if (!isFormPost())
		break;
	if (ROUND_CLOSING)
	{
		notice(lang('CLAN_CREATE_TOO_LATE'));
		break;
	}
	if ($emp1->c_id)
	{
		notice(lang('CLAN_ALREADY_MEMBER'));
		break;
	}
	if ($emp1->effects->m_clan)
	{
		notice(lang('CLAN_CREATE_TOO_SOON', CLAN_MINREJOIN));
		break;
	}

	$cname = htmlspecialchars(getFormVar('create_name'));
	$cpass = getFormVar('create_pass');
	$cpass_verify = getFormVar('create_pass_verify');
	if (empty($cname))
	{
		notice(lang('CLAN_CREATE_NEED_NAME'));
		break;
	}
	if (lang_equals_any($cname, 'CLAN_NONE'))
	{
		notice(lang('CLAN_CREATE_NAME_INVALID'));
		break;
	}
	if (lang_isset($cname))
	{
		notice(lang('CLAN_CREATE_NAME_INVALID'));
		break;
	}
	if (strlen($cname) > 8)
	{
		notice(lang('CLAN_CREATE_NAME_TOO_LONG'));
		break;
	}
	if (empty($cpass))
	{
		notice(lang('INPUT_NEED_PASSWORD'));
		break;
	}
	if ($cpass != $cpass_verify)
	{
		notice(lang('INPUT_PASSWORD_MISMATCH'));
		break;
	}

	if ($db->queryCell('SELECT COUNT(*) FROM '. CLAN_TABLE .' WHERE c_name = ? AND c_members >= 0', array($cname)) > 0)
	{
		notice(lang('CLAN_CREATE_NAME_IN_USE'));
		break;
	}

	$clan1 = new prom_clan();
	$clan1->create($emp1, $cname, $cpass);
	$entities[] = $clan1;	// add it to the global entity list so it gets freed at the end of the page
	$lock['clan1'] = $clan1->c_id;

	$emp1->c_id = $clan1->c_id;
	$emp1->effects->m_clan = 3600 * CLAN_MINJOIN;

	addEmpireNews(EMPNEWS_CLAN_CREATE, $emp1, $emp1, 0);
	addClanNews(CLANNEWS_MEMBER_CREATE, $clan1, $emp1);
	notice(lang('CLAN_CREATE_COMPLETE', $cname));
	logevent(varlist(array('cname'), get_defined_vars()));
	// save to database so the lists below will update properly
	$emp1->save();
} while (0);
if (($action == 'join') || ($action == 'invite')) do
{
	if (!isFormPost())
		break;
	if ($emp1->c_id)
	{
		notice(lang('CLAN_ALREADY_MEMBER'));
		break;
	}
	if ($emp1->effects->m_clan)
	{
		notice(lang('CLAN_JOIN_TOO_SOON', CLAN_MINREJOIN));
		break;
	}
	if ($lock['clan1'] == 0)
	{
		notice(lang('CLAN_JOIN_NEED_CLAN'));
		break;
	}
	if ($clan1->c_members < 1)
	{
		notice(lang('CLAN_JOIN_DISBANDED'));
		break;
	}

	if ($action == 'join')
	{
		$cpass = getFormVar('join_pass');
		if (!$clan1->checkPassword($cpass))
		{
			notice(lang('INPUT_INCORRECT_PASSWORD'));
			break;
		}
	}
	elseif ($action == 'invite')
	{
		$inv_id = fixInputNum(getFormVar('invite_id'));
		$q = $db->prepare('SELECT * FROM '. CLAN_INVITE_TABLE .' WHERE ci_id = ?');
		$q->bindIntValue(1, $inv_id);
		$q->execute() or warning('Failed to load clan invite', 0);
		$invite = $q->fetch();
		if (!$invite)
		{
			notice(lang('CLAN_INVITE_NEED_INVITE'));
			break;
		}
		if ($invite['e_id_2'] != $emp1->e_id)
		{
			notice(lang('CLAN_INVITE_WRONG_EMPIRE'));
			break;
		}
		if ($invite['c_id'] != $clan1->c_id)
		{
			notice(lang('CLAN_INVITE_WRONG_CLAN'));
			break;
		}
	}
	$maxmembers = round(10 + $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0') / 100);
	if ($clan1->c_members >= $maxmembers)
	{
		notice(lang('CLAN_JOIN_IS_FULL'));
		break;
	}

	$q = $db->prepare('DELETE FROM '. CLAN_INVITE_TABLE .' WHERE e_id_2 = ? AND ci_flags & ? = 0');
	$q->bindIntValue(1, $emp1->e_id);
	$q->bindIntValue(2, CIFLAG_PERM);
	$q->execute() or warning('Failed to clear clan invites', 0);

	$emp1->c_id = $clan1->c_id;
	$emp1->e_sharing = 0;
	$emp1->effects->m_clan = 3600 * CLAN_MINJOIN;
	$clan1->c_members++;
	$emp_a = new prom_empire($clan1->e_id_leader);
	$emp_a->loadPartial();
	addEmpireNews(EMPNEWS_CLAN_JOIN, $emp1, $emp_a, 0);	// Send "join" notice to the leader
	addEmpireNews(EMPNEWS_CLAN_JOIN, $emp1, $emp1, 0);	// and to yourself
	addClanNews(CLANNEWS_MEMBER_JOIN, $clan1, $emp1);	// and to the clan itself
	notice(lang('CLAN_JOIN_COMPLETE', $clan1->c_name));
	logevent();
	// save to database so the lists below will update properly
	$emp1->save();
	$emp_a = NULL;
} while (0);
if ($action == 'leave') do
{
	if (!isFormPost())
		break;
	if ($emp1->c_id == 0)
	{
		notice(lang('CLAN_NOT_MEMBER'));
		break;
	}
	if ($emp1->effects->m_clan)
	{
		notice(lang('CLAN_LEAVE_TOO_SOON', CLAN_MINJOIN));
		break;
	}
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('CLAN_LEAVE_NEED_CONFIRM'));
		break;
	}
	if ($clan1->e_id_leader == $emp1->e_id)
	{
		if ($clan1->c_members != 1)
		{
			notice(lang('CLAN_LEAVE_OTHER_MEMBERS'));
			break;
		}

		$q = $db->prepare('SELECT e_id_2 FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ?');
		$q->bindIntValue(1, $clan1->c_id);
		$q->execute() or warning('Failed to fetch clan invites', 0);
		$invs = $q->fetchAll();
		foreach ($invs as $inv)
		{
			$emp_a = new prom_empire($inv['e_id_2']);
			$emp_a->loadPartial();
			addEmpireNews(EMPNEWS_CLAN_INVITE_DISBANDED, $emp1, $emp_a, 0);
			$emp_a = NULL;
		}
		$q = $db->prepare('DELETE FROM '. CLAN_INVITE_TABLE .' WHERE c_id = ?');
		$q->bindIntValue(1, $clan1->c_id);
		$q->execute() or warning('Failed to delete clan invites', 0);

		addEmpireNews(EMPNEWS_CLAN_DISBAND, $emp1, $emp1, 0);
		$emp1->c_id = 0;
		$emp1->e_sharing = 0;
		$emp1->effects->m_clan = 3600 * CLAN_MINREJOIN;
		$clan1->c_members = 0;
		$clan1->e_id_leader = 0;
		notice(lang('CLAN_LEAVE_COMPLETE_DISBAND', $clan1->c_name));
		logevent('disband');
	}
	else
	{
		if ($clan1->e_id_asst == $emp1->e_id)
			$clan1->e_id_asst = 0;
		if ($clan1->e_id_fa1 == $emp1->e_id)
			$clan1->e_id_fa1 = 0;
		if ($clan1->e_id_fa2 == $emp1->e_id)
			$clan1->e_id_fa2 = 0;
		$clan1->c_members--;
		$emp_a = new prom_empire($clan1->e_id_leader);
		$emp_a->loadPartial();
		addEmpireNews(EMPNEWS_CLAN_LEAVE, $emp1, $emp_a, 0);	// Send "left" notice to the leader
		addEmpireNews(EMPNEWS_CLAN_LEAVE, $emp1, $emp1, 0);	// and to yourself
		addClanNews(CLANNEWS_MEMBER_LEAVE, $clan1, $emp1);	// and to the clan

		$emp1->c_id = 0;
		$emp1->e_sharing = 0;
		$emp1->effects->m_clan = 3600 * CLAN_MINREJOIN;
		notice(lang('CLAN_LEAVE_COMPLETE', $clan1->c_name));
		logevent('leave');
		$emp_a = NULL;
	}
} while (0);
if ($action == 'share') do
{
	if (!isFormPost())
		break;
	if ($emp1->c_id == 0)
	{
		notice(lang('CLAN_NOT_MEMBER'));
		break;
	}
	$emp1->e_sharing = -1;
	addClanNews(CLANNEWS_MEMBER_SHARE, $clan1, $emp1);
	notice(lang('CLAN_SHARE_COMPLETE'));
	logevent();
	// save to database so the lists below will update properly
	$emp1->save();
} while (0);
if ($action == 'unshare') do
{
	if (!isFormPost())
		break;
	if ($emp1->c_id == 0)
	{
		notice(lang('CLAN_NOT_MEMBER'));
		break;
	}
	$emp1->e_sharing = CLAN_MINSHARE * (60 / TURNS_FREQ) - 1;
	addClanNews(CLANNEWS_MEMBER_UNSHARE, $clan1, $emp1);
	notice(lang('CLAN_UNSHARE_COMPLETE', CLAN_MINSHARE));
	logevent();
	// save to database so the lists below will update properly
	$emp1->save();
} while (0);
notices();

if ($emp1->c_id)
{
	$allies1 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY | CRFLAG_MUTUAL, RELATION_OUTBOUND | RELATION_INBOUND);
	$allies2 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY, RELATION_OUTBOUND);
	$allies3 = $clan1->listRelations(CRFLAG_ALLY | CRFLAG_MUTUAL, CRFLAG_ALLY, RELATION_INBOUND);
	$allies = array();
	if (count($allies1) > 0)
	{
		$allies[] = lang('CLAN_ALLY_MUTUAL_LABEL');
		foreach ($allies1 as $id)
			$allies[] = $id;
	}
	if (count($allies2) > 0)
	{
		$allies[] = lang('CLAN_ALLY_OUTBOUND_LABEL');
		foreach ($allies2 as $id)
			$allies[] = $id;
	}
	if (count($allies3) > 0)
	{
		$allies[] = lang('CLAN_ALLY_INBOUND_LABEL');
		foreach ($allies3 as $id)
			$allies[] = $id;
	}
	if (count($allies) == 0)
		$allies[] = lang('CLAN_ALLY_NONE_LABEL');

	$wars1 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR | CRFLAG_MUTUAL, RELATION_OUTBOUND | RELATION_INBOUND);
	$wars2 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR, RELATION_OUTBOUND);
	$wars3 = $clan1->listRelations(CRFLAG_WAR | CRFLAG_MUTUAL, CRFLAG_WAR, RELATION_INBOUND);
	$wars = array();

	if (count($wars1) > 0)
	{
		$wars[] = lang('CLAN_WAR_MUTUAL_LABEL');
		foreach ($wars1 as $id)
			$wars[] = $id;
	}
	if (count($wars2) > 0)
	{
		$wars[] = lang('CLAN_WAR_OUTBOUND_LABEL');
		foreach ($wars2 as $id)
			$wars[] = $id;
	}
	if (count($wars3) > 0)
	{
		$wars[] = lang('CLAN_WAR_INBOUND_LABEL');
		foreach ($wars3 as $id)
			$wars[] = $id;
	}
	if (count($wars) == 0)
		$wars[] = lang('CLAN_WAR_NONE_LABEL');

	$troops = lookup('list_mil');
	$shared = array();
	foreach ($troops as $troop)
		$shared[$troop] = 0;

	$q = $db->prepare('SELECT e_id,e_name,e_sharing,e_rank,e_race,e_era,e_networth,e_trparm,e_trplnd,e_trpfly,e_trpsea FROM '. EMPIRE_TABLE .' WHERE c_id = ?');
	$q->bindIntValue(1, $clan1->c_id);
	$q->execute() or warning('Failed to fetch clan member stats', 0);
	$empires = $q->fetchAll();
	$members = array();
	foreach ($empires as $data)
	{
		$emp_a = new prom_empire($data['e_id']);
		$emp_a->initdata($data);
		// need to explicitly load effect data
		$emp_a->effects = new prom_empire_effects($emp_a);
		if ($emp_a->e_sharing)
		{
			foreach ($troops as $troop)
				$shared[$troop] += round($emp_a->getData('e_'.$troop) * 0.10);
		}
		$members[] = $emp_a;
	}

	if ($clan1->c_url)
	{
?><a href="<?php echo $clan1->c_url; ?>" rel="external"><?php
	}
	if ($clan1->c_pic)
	{
?><img src="<?php echo $clan1->c_pic; ?>" style="border:0" alt="<?php echo lang('CLAN_LINK_LABEL', $clan1->c_title); ?>" /><?php
	}
	elseif ($clan1->c_url)
	{
		echo lang('CLAN_LINK_LABEL', $clan1->c_title);
	}
	if ($clan1->c_url)
	{
?></a><?php
	}
?>
<br />
<table style="background-color:#1F1F1F">
<tr><th class="era<?php echo $emp1->e_era; ?>"><?php echo lang('CLAN_MEMBER_HEADER', $clan1->c_name); ?></th></tr>
</table>
<h3><a href="?location=clanforum"><?php echo lang('CLAN_LINK_FORUM'); ?></a></h3>
<?php	if (in_array($emp1->e_id, array($clan1->e_id_leader, $clan1->e_id_asst, $clan1->e_id_fa1, $clan1->e_id_fa2))) { ?>
<h3><a href="?location=manage/clan"><?php echo lang('CLAN_LINK_MANAGE'); ?></a></h3>
<?php	} ?>
<h3><?php echo lang('CLAN_RELATIONS_HEADER', $clan1->c_title); ?></h3>
<table class="inputtable">
<tr><th style="width:50%"><span class="cgood"><?php echo lang('CLAN_ALLY_LABEL'); ?></span><br /><?php echo lang('CLAN_ALLY_DESC'); ?></th>
    <th style="width:50%"><span class="cbad"><?php echo lang('CLAN_WAR_LABEL'); ?></span><br /><?php echo lang('CLAN_WAR_DESC'); ?></th></tr>
<?php
	$lines = max(count($allies), count($wars));
	for ($i = 0; $i < $lines; $i++)
	{
		echo '<tr>';
		if (isset($allies[$i]))
		{
			$ally = $allies[$i];
			if (!is_numeric($ally))
				echo '<td class="ac"><b>'. $ally .'</b></td>';
			else	echo '<td class="ac">'. $cnames[$ally] .'</td>';
		}
		else	echo '<td></td>';
		if (isset($wars[$i]))
		{
			$war = $wars[$i];
			if (!is_numeric($war))
				echo '<td class="ac"><b>'. $war .'</b></td>';
			else	echo '<td class="ac">'. $cnames[$war] .'</td>';
		}
		else	echo '<td></td>';
		echo '</tr>'."\n";
	}
?>
</table>
<br />
<form method="post" action="?location=clan">
<div>
<?php	if ($emp1->e_sharing < 0) { ?>
<input type="hidden" name="action" value="unshare" />
<input type="submit" value="<?php echo lang('CLAN_UNSHARE_SUBMIT'); ?>" />
<?php	} else { ?>
<input type="hidden" name="action" value="share" />
<input type="submit" value="<?php echo lang('CLAN_SHARE_SUBMIT'); ?>" />
<?php	} ?>
</div>
</form>
<?php echo lang('CLAN_MEMBERS_HEADER', $clan1->c_title, $clan1->c_members); ?><br /><br />
<table class="inputtable">
<caption><b><?php echo lang('CLAN_MEMBERS_LABEL'); ?></b></caption>
<tr><th><?php echo lang('COLUMN_CLANRANK'); ?></th>
    <th><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th><?php echo lang('COLUMN_NETWORTH'); ?></th>
    <th><?php echo lang('COLUMN_RANK'); ?></th>
    <th><?php echo lang('COLUMN_RACE'); ?></th>
    <th><?php echo lang('COLUMN_ERA'); ?></th>
    <th><?php echo lang('COLUMN_SHARING'); ?></th></tr>
<?php
	foreach ($members as $emp_a)
	{
?>
<tr><td class="ac"><?php
		if ($emp_a->e_id == $clan1->e_id_leader)
			echo lang('CLAN_LEADER_LABEL');
		elseif ($emp_a->e_id == $clan1->e_id_asst)
			echo lang('CLAN_ASSISTANT_LABEL');
		elseif (($emp_a->e_id == $clan1->e_id_fa1) || ($emp_a->e_id == $clan1->e_id_fa2))
			echo lang('CLAN_FA_LABEL');
		else	echo '&nbsp;';
?></td>
    <td class="ac"><?php echo $emp_a; ?></td>
    <td class="ar"><?php echo money($emp_a->e_networth); ?></td>
    <td class="ar"><?php echo prenum($emp_a->e_rank); ?></td>
    <td class="ac"><?php echo $races[$emp_a->e_race]; ?></td>
    <td class="ac"><span class="<?php
		if (($emp_a->e_era == $emp1->e_era) || ($emp1->effects->m_gate) || ($emp_a->effects->m_gate))
			echo 'cgood';
		else	echo 'cwarn';
?>"><?php
		if ($emp1->effects->m_gate)
			echo '*';
		echo $eras[$emp_a->e_era];
		if ($emp_a->effects->m_gate)
			echo '*';
?></span></td>
    <td class="ac"><span class="<?php
		if ($emp_a->e_sharing)
			echo 'cgood">'. lang('COMMON_YES');
		else	echo 'cbad">'. lang('COMMON_NO');
?></span></td></tr>
<?php
	}
	$emp_a = NULL;
?>
</table><br />
<?php
	if ($emp1->e_sharing)
	{
?>
<table class="inputtable">
<caption><b><?php echo lang('CLAN_SHARE_HEADER'); if ($emp1->e_sharing > 0) echo lang('CLAN_UNSHARE_HEADER', $emp1->e_sharing * TURNS_FREQ); ?></b></caption>
<tr><th><?php echo lang('COLUMN_UNIT'); ?></th>
    <th><?php echo lang('COLUMN_SHAREYOU'); ?></th>
    <th><?php echo lang('COLUMN_SHARETOTAL'); ?></th></tr>
<?php		foreach ($troops as $troop) { ?>
<tr><th><?php echo lang($emp1->era->getData($troop)); ?></th>
    <td class="ar"><?php echo number(round($emp1->getData('e_'.$troop) * 0.10)); ?></td>
    <td class="ar"><?php echo number($shared[$troop]); ?></td></tr>
<?php		} ?>
</table>
<?php
	}
	// the leader can only leave if he's the only one
	if (($clan1->e_id_leader == $emp1->e_id) && ($clan1->c_members == 1))
	{
?>
<form method="post" action="?location=clan">
<div>
<input type="hidden" name="action" value="leave" />
<?php echo checkbox('confirm', lang('CLAN_DISBAND_CONFIRM')); ?><br />
<input type="submit" value="<?php echo lang('CLAN_DISBAND_SUBMIT'); ?>" />
</div>
</form>
<?php
	}
	elseif ($clan1->e_id_leader != $emp1->e_id)
	{
?>
<form method="post" action="?location=clan">
<div>
<input type="hidden" name="action" value="leave" />
<?php echo checkbox('confirm', lang('CLAN_LEAVE_CONFIRM')); ?><br />
<input type="submit" value="<?php echo lang('CLAN_LEAVE_SUBMIT'); ?>" />
</div>
</form>
<?php
	}
}
else
{
	// are empires permitted to join a clan at this time?
	if ((!ROUND_CLOSING || CLAN_LATE_JOIN))
	{
		$q = $db->query('SELECT c_id,c_name,c_title FROM '. CLAN_TABLE .' WHERE c_members > 0 ORDER BY c_id ASC') or warning('Failed to get list of active clan names', 0);
		$clans = $q->fetchAll();
		if (count($clans) > 0)
		{
			$clanlist = array();
			foreach ($clans as $clan)
			{
				if ($clan['c_title'])
					$clanlist[$clan['c_id']] = lang('CLAN_JOIN_LABEL_WITH_TITLE', $clan['c_name'], $clan['c_title']);
				else	$clanlist[$clan['c_id']] = $clan['c_name'];
			}
?>
<?php echo lang('CLAN_NONMEMBER_HEADER'); ?><br />
<form method="post" action="?location=clan">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('CLAN_JOIN_LABEL'); ?></th></tr>
<tr><td class="ar"><?php echo lang('LABEL_CLAN'); ?></td>
    <td><?php echo optionlist('join_id', $clanlist); ?></td></tr>
<tr><td class="ar"><?php echo lang('LABEL_PASSWORD'); ?></td>
    <td><input type="password" name="join_pass" size="8" /></td></tr>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="join" /><input type="submit" value="<?php echo lang('CLAN_JOIN_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<hr />
<?php
			$q = $db->prepare('SELECT ci.ci_id,ci.c_id,c.c_name,c.c_title,ci.ci_flags FROM '. CLAN_INVITE_TABLE .' ci LEFT OUTER JOIN '. CLAN_TABLE .' c USING (c_id) WHERE ci.e_id_2 = ?');
			$q->bindIntValue(1, $emp1->e_id);
			$q->execute() or warning('Failed to fetch clan invite list', 0);
			$invites = $q->fetchAll();
			if (count($invites) > 0)
			{
?>
<table class="inputtable">
<tr><th colspan="3"><?php echo lang('CLAN_INVITE_HEADER'); ?></th></tr>
<?php
				foreach ($invites as $invite)
				{
?>
<tr><td><?php
					if ($invite['c_title'])
						echo lang('CLAN_JOIN_LABEL_WITH_TITLE', $invite['c_name'], $invite['c_title']);
					else	echo $invite['c_name'];
?></td>
    <td><?php echo lang(($invite['ci_flags'] & CIFLAG_PERM) ? 'CLAN_INVITE_PERM_LABEL' : 'CLAN_INVITE_TEMP_LABEL'); ?></td>
    <td><form method="post" action="?location=clan"><input type="hidden" name="action" value="invite" /><input type="hidden" name="join_id" value="<?php echo $invite['c_id']; ?>" /><input type="hidden" name="invite_id" value="<?php echo $invite['ci_id']; ?>" /><input type="submit" value="<?php echo lang('CLAN_JOIN_SUBMIT'); ?>" /></form></td></tr>
<?php
				}
?>
</table>
<hr />
<?php
			}
		}
		elseif (ROUND_CLOSING)
			echo lang('CLAN_JOIN_NONE_UNAVAILABLE') .'<br />';
		else	echo lang('CLAN_JOIN_NONE_AVAILABLE') .'<br />';
	}
	else	echo lang('CLAN_JOIN_LATE') .'<br />';

	// are they permitted to create one?
	if (!ROUND_CLOSING)
	{
?>
<form method="post" action="?location=clan">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('CLAN_CREATE_LABEL'); ?></th></tr>
<tr><td class="ar"><?php echo lang('LABEL_CLAN_NAME'); ?></td>
    <td><input type="text" name="create_name" size="10" maxlength="10" /></td></tr>
<tr><td class="ar"><?php echo lang('LABEL_PASSWORD'); ?></td>
    <td><input type="password" name="create_pass" size="8" /></td></tr>
<tr><td class="ar"><?php echo lang('LABEL_PASSWORD_VERIFY'); ?></td>
    <td><input type="password" name="create_pass_verify" size="8" /></td></tr>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="create" /><input type="submit" value="<?php echo lang('CLAN_CREATE_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
	}
}
page_footer();
?>
