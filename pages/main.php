<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: main.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MAIN_TITLE';

page_header();
require_once(PROM_BASEDIR .'includes/bbcode.php');

$notes_id = $db->queryCell('SELECT m_id FROM '. EMPIRE_MESSAGE_TABLE .' WHERE e_id_src = 0 AND e_id_dst = ?', array($emp1->e_id));
$notes_text = $db->queryCell('SELECT m_body FROM '. EMPIRE_MESSAGE_TABLE .' WHERE m_id = ?', array($notes_id));

// The action handler for 'setuser' is located within page_header() so it can manipulate locks before they are established
// At this point, the new empire has been marked as Online, so the previous empire needs to go offline
if (($action == 'setuser') && ($emp2->u_id == $user1->u_id))
	$emp2->clrFlag(EFLAG_ONLINE);
if ($action == 'rename') do
{
	if (!isFormPost())
		break;
	$newname = htmlspecialchars(getFormVar('rename_name'));
	if (!($user1->u_flags & UFLAG_ADMIN))
	{
		notice(lang('MAIN_RENAME_PERMISSION'));
		break;
	}
	if (!strlen($newname))
	{
		notice(lang('MAIN_RENAME_NEED_NAME'));
		break;
	}
	$emp1->e_name = $newname;
	notice(lang('MAIN_RENAME_COMPLETE', $emp1->e_name));
	logevent(varlist(array('newname'), get_defined_vars()));
} while (0);
if ($action == 'markread') do
{
	if (!isFormPost())
		break;
	$since = fixInputNum(getFormVar('since'));
	if ($since == 0)
		break;	// nothing to do
	$q = $db->prepare('UPDATE '. EMPIRE_NEWS_TABLE .' SET n_flags = n_flags | ? WHERE e_id_dst = ? AND n_id <= ?');
	$q->bindIntValue(1, NFLAG_READ);
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindIntValue(3, $since);
	$q->execute() or warning('Failed to mark news entries as read', 0);
	logevent();
} while (0);
if ($action == 'updatenotes') do
{
	if (!isFormPost())
		break;
	$notes_text = bbencode(htmlspecialchars(getFormVar('notes_text')));
	if (strlen($notes_text) > 65535)
	{
		notice(lang('MAIN_NOTES_TOO_LONG'));
		$action = 'editnotes';
		break;
	}
	$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_body = ? WHERE m_id = ?');
	$q->bindIntValue(1, $notes_text);
	$q->bindIntValue(2, $notes_id);
	$q->execute() or warning('Failed to update notes text', 0);
	logevent();
} while (0);
if ($action == 'allnews')
	$allnews = 1;
else	$allnews = 0;
notices();

if ($user1->u_flags & UFLAG_ADMIN)
{
?>
<table class="inputtable">
<tr><td><form method="post" action="?location=main"><div><?php echo lang('MAIN_SELECT_EMPIRE') .' ';
	$userlist = array();
	$q = $db->prepare('SELECT e_id,e_name FROM '. EMPIRE_TABLE .' WHERE u_id != 0 AND e_flags & ? = 0 ORDER BY e_id ASC');
	$q->bindIntValue(1, EFLAG_DELETE);
	$q->execute() or warning('Failed to fetch empire list', 0);
	$empires = $q->fetchAll();
	foreach ($empires as $edata)
	{
		$emp_a = new prom_empire($edata['e_id']);
		$emp_a->initdata($edata);
		$userlist[$emp_a->e_id] = (string)$emp_a;
		$emp_a = NULL;
	}
	echo optionlist('setuser_id', $userlist, $emp1->e_id);
?> <input type="hidden" name="action" value="setuser" /><input type="submit" value="<?php echo lang('MAIN_SETUSER_SUBMIT'); ?>" /></div></form></td></tr>
<tr><td><form method="post" action="?location=<?php echo $page; ?>"><div><input type="text" name="rename_name" size="24" value="<?php echo $emp1->e_name; ?>" /> <input type="hidden" name="action" value="rename" /><input type="submit" value="<?php echo lang('MAIN_RENAME_SUBMIT'); ?>" /></div></form></td></tr>
</table>
<?php
}
else
{
	$q = $db->prepare('SELECT e_id,e_name FROM '. EMPIRE_TABLE .' WHERE u_id = ? AND e_flags & ? = 0 ORDER BY e_id ASC');
	$q->bindIntValue(1, $user1->u_id);
	$q->bindIntValue(2, EFLAG_DELETE);
	$q->execute() or warning('Failed to fetch empire list', 0);
	$empires = $q->fetchAll();
	if (count($empires) > 1)
	{
?>
<table class="inputtable">
<tr><td><form method="post" action="?location=main"><div><?php echo lang('MAIN_SELECT_EMPIRE') .' ';
		$userlist = array();
		foreach ($empires as $edata)
		{
			$emp_a = new prom_empire($edata['e_id']);
			$emp_a->initdata($edata);
			$userlist[$emp_a->e_id] = (string)$emp_a;
			$emp_a = NULL;
		}
		echo optionlist('setuser_id', $userlist, $emp1->e_id);
?> <input type="hidden" name="action" value="setuser" /><input type="submit" value="<?php echo lang('MAIN_SETUSER_SUBMIT'); ?>" /></div></form></td></tr>
</table>
<?php
	}
}

// Main page is always allowed to load so we can perform a user switch
// and this special variable is set when other pages aren't allowed to load
// (i.e. vacation, death, disabled, unvalidated, or deleted)
if ($shortmain)
	unavailable('');

if ($user1->u_flags & UFLAG_MOD)
{
	$num = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_MESSAGE_TABLE .' WHERE e_id_dst = 0 AND m_flags & ? = 0', array(MFLAG_READ));
	if ($num > 0)
		echo '<b><a href="?location=admin/messages&amp;action=reports">'. lang('MAIN_MODERATOR_REPORTS', $num) .'</a></b><br />';
}

$emp1->printMainStats();

if ($emp1->is_protected())
{
?>
<span class="mprotected"><?php echo lang('MAIN_UNDER_PROTECTION', TURNS_PROTECTION); ?></span><br />
<?php
}
if ($world->turns_next > CUR_TIME)
	$nextturn = $world->turns_next - CUR_TIME;
else	$nextturn = 0;
?>
<h3>Cheat Codes</h3>
<div style="max-width: 800px; margin: auto;">
	<p>Some important things to know.</p>
	<p>Everything is mathematical; if it didn't work the first time, it probably won't work.<br>
	Health is directly correlated to success in attacks. Higher health = higher chance of success.<br>
		Advancing through time requires a 90% ratio or more of wizards to mage towers, with your magic stat factored in. <span class="mprotected">Wizards * ( 100 - Magic Stat ) / Mage Towers >= 90%</span></p>
	<h3>Networth</h3>
	<p>Some rough networth calculations.</p>
	<p class="mprotected">Army Unit = 1<br>
	Land Unit = 2<br>
	Air Unit = 3<br>
	Sea Unit = 4<br>
	Wizards = 2<br>
		Peasants = 3<br>
		Built Land = 500<br>
		Empty Land = 100</p>
	
</div>
<h3>Basic Summary</h3>
<div style="max-width: 800px; margin: auto;">
<p>Use turns to farm, cash, explore (gain land), or build. One turn is gained every ten minutes. The aim is to have the most powerful and valuable empire. After 200 turns, you're out of protection, and can attack/be attacked by other empires.</p>
</div>
<p>The best way to spend your first turns is by exploring to gain land, and building structures.</p>
<b><?php echo $user1->customdate(CUR_TIME); ?></b><br />
<?php echo lang('MAIN_TURN_RATE', plural(TURNS_COUNT, 'TURNS_SINGLE', 'TURNS_PLURAL'), duration(TURNS_FREQ * 60)); ?><br />
<?php
if (!ROUND_STARTED)
	echo lang('MAIN_TURNS_STOPPED') .'<br /><br />';
else	echo lang('MAIN_TURNS_NEXT', plural(TURNS_COUNT, 'TURNS_SINGLE', 'TURNS_PLURAL'), duration($nextturn)) .'<br /><br />';

if (defined('TXT_TIMENOTICE'))
	echo TXT_TIMENOTICE .'<br /><br />';
if (CLAN_ENABLE && $emp1->c_id)
{
	$topic_id = $db->queryCell('SELECT ct_id FROM '. CLAN_TOPIC_TABLE .' WHERE c_id = ? AND ct_flags & ? != 0', array($emp1->c_id, CTFLAG_NEWS));
	$newstext = $db->queryCell('SELECT cm_body FROM '. CLAN_MESSAGE_TABLE .' WHERE ct_id = ? AND cm_flags & ? = 0 ORDER BY cm_time DESC', array($topic_id, CMFLAG_DELETE));
?>
<table class="inputtable" style="width:50%">
<tr><td><hr /></td></tr>
<tr><th><?php echo lang('LABEL_CLAN_NEWS'); ?></th></tr>
<tr><td><?php echo str_replace("\n", '<br />', $newstext); ?></td></tr>
<tr><td><hr /></td></tr>
</table>
<?php
}
if ($action == 'editnotes')
{
?>
<form method="post" action="?location=main">
<table class="inputtable" style="width:50%">
<tr><td><hr /></td></tr>
<tr><th><?php echo lang('MAIN_NOTES_LABEL'); ?></th></tr>
<tr><td class="ac"><textarea rows="15" cols="76" name="notes_text"><?php echo bbdecode($notes_text); ?></textarea></td></tr>
<tr><td class="ac"><input type="hidden" name="action" value="updatenotes" /><input type="submit" value="<?php echo lang('MAIN_NOTES_SUBMIT'); ?>" /></td></tr>
<tr><td><hr /></td></tr>
</table>
</form>
<?php
}
else
{
?>
<table class="inputtable" style="width:50%">
<tr><td><hr /></td></tr>
<tr><th><?php echo lang('MAIN_NOTES_LABEL'); ?></th></tr>
<tr><td><?php echo str_replace("\n", '<br />', $notes_text); ?></td></tr>
<tr><td class="ac"><a href="?location=main&amp;action=editnotes"><?php echo lang('MAIN_NOTES_EDIT'); ?></a></td></tr>
<tr><td><hr /></td></tr>
</table>
<?php
}
$newmsgs = $emp1->numNewMessages();
$oldmsgs = $emp1->numTotalMessages() - $newmsgs;
?>
<a href="?location=messages"><b><?php
if ($newmsgs + $oldmsgs > 0)
	echo lang('MAIN_HAVE_MESSAGES', plural($newmsgs, 'NEW_MESSAGES_SINGLE', 'NEW_MESSAGES_PLURAL'), plural($oldmsgs, 'OLD_MESSAGES_SINGLE', 'OLD_MESSAGES_PLURAL'));
else	echo lang('MAIN_NO_MESSAGES');
?></b></a><br />
<?php
$lastnews = printEmpireNews($emp1, $allnews);
if ($allnews)
{
	if (!$lastnews)
		echo '<b>'. lang('MAIN_NO_ARCHIVED_NEWS') .'</b><br />';
}
else
{
	if ($lastnews)
		echo '<form method="post" action="?location=main"><div><input type="hidden" name="action" value="markread" /><input type="hidden" name="since" value="'. $lastnews .'" /><input type="submit" value="'. lang('MAIN_MARK_NEWS_READ') .'" /></div></form><br />';
	else	echo '<b>'. lang('MAIN_NO_NEWS') .'</b><br />';
	echo '<a href="?location=main&amp;action=allnews">'. lang('MAIN_VIEW_NEWS_ARCHIVE') .'</a><br />';
}
page_footer();
?>
