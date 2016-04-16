<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: messages.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'MESSAGES_TITLE';

page_header();
require_once(PROM_BASEDIR .'includes/bbcode.php');

$message_id = 0;
$message_dest = 0;
$message_subj = '';
$message_body = '';

if ($action == 'send') do
{
	if (!isFormPost())
		break;
	$dst = fixInputNum(getFormVar('msg_to'));
	$subj = htmlspecialchars(getFormVar('msg_subject'));
	$body = htmlspecialchars(getFormVar('msg_body'));

	if (($emp1->effects->m_message >= (MESSAGES_MAXCREDITS - 1) * MESSAGES_DELAY) && !($emp1->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_NOCREDITS'));
		$action = 'inbox';
		break;
	}

	if ($dst == 0)
	{
		notice(lang('MESSAGES_NO_SUCH_EMPIRE'));
		$action = 'inbox';
		$message_subj = $subj;
		$message_body = $body;
		break;
	}

	$emp_a = new prom_empire($dst);
	if (!$emp_a->load())
	{
		notice(lang('MESSAGES_NO_SUCH_EMPIRE'));
		$action = 'inbox';
		$message_subj = $subj;
		$message_body = $body;
		break;
	}
	if (($emp_a->e_flags & EFLAG_DELETE) || ($emp_a->u_id == 0))
	{
		notice(lang('MESSAGES_SEND_EMPIRE_DEAD'));
		$action = 'inbox';
		break;
	}
	if (($emp_a->e_flags & EFLAG_SILENT) && !($emp1->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_SEND_EMPIRE_SILENCED'));
		$action = 'inbox';
		break;
	}
	if (($emp1->e_flags & EFLAG_SILENT) && !($emp_a->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_SEND_SILENCED'));
		$action = 'inbox';
		break;
	}
	if (VALIDATE_REQUIRE && !($emp1->e_flags & EFLAG_VALID) && ($emp1->e_turnsused >= TURNS_VALIDATE) && !($emp_a->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_SEND_VALIDATE'));
		$action = 'inbox';
		break;
	}

	if (strlen($subj) > 255)
	{
		notice(lang('MESSAGES_SUBJECT_TOO_LONG'));
		$action = 'inbox';
		$message_dest = $dst;
		$message_body = $body;
		break;
	}
	if (lang_isset($subj))
	{
		notice(lang('MESSAGES_SUBJECT_INVALID'));
		$action = 'inbox';
		$message_dest = $dst;
		$message_body = $body;
		break;
	}

	$encbody = bbencode($body);
	if (strlen($encbody) > 65535)
	{
		notice(lang('MESSAGES_BODY_TOO_LONG'));
		$action = 'inbox';
		$message_dest = $dst;
		$message_subj = $subj;
		$message_body = $body;
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('MESSAGES_BODY_INVALID'));
		$action = 'inbox';
		$message_dest = $dst;
		$message_subj = $subj;
		$message_body = $body;
		break;
	}

	$q = $db->prepare('INSERT INTO '. EMPIRE_MESSAGE_TABLE .' (m_id_ref,m_time,e_id_src,e_id_dst,m_subject,m_body,m_flags) VALUES (0,?,?,?,?,?,0)');
	$q->bindIntValue(1, CUR_TIME);
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindIntValue(3, $dst);
	$q->bindStrValue(4, $subj);
	$q->bindStrValue(5, $encbody);
	$q->execute() or warning('Failed to deliver message', 0);

	// Moderators get unlimited message credits
	if (!($emp1->e_flags & EFLAG_ADMIN))
		$emp1->effects->m_message += MESSAGES_DELAY;

	notice(lang('MESSAGES_SEND_COMPLETE', $emp_a));
	$emp_a = NULL;
	logevent(varlist(array('dst'), get_defined_vars()));
	$action = 'inbox';
} while (0);
elseif ($action == 'reply') do
{
	if (!isFormPost())
		break;
	$reply = fixInputNum(getFormVar('msg_replyid', 0));
	$subj = htmlspecialchars(getFormVar('msg_subject'));
	$body = htmlspecialchars(getFormVar('msg_body'));

	$q = $db->prepare('SELECT * FROM '. EMPIRE_MESSAGE_TABLE .' WHERE m_id = ? AND e_id_src != 0');
	$q->bindIntValue(1, $reply);
	$q->execute() or warning('Failed to fetch original message', 0);

	$oldmsg = $q->fetch();
	if (!$oldmsg)
	{
		notice(lang('MESSAGES_REPLY_NOT_EXIST'));
		$action = 'inbox';
		break;
	}
	if ($oldmsg['e_id_dst'] != $emp1->e_id)
	{
		notice(lang('MESSAGES_REPLY_NOT_YOURS'));
		$action = 'inbox';
		break;
	}
	if ($oldmsg['e_id_src'] == $emp1->e_id)
	{
		notice(lang('MESSAGES_REPLY_NOT_SELF'));
		$action = 'inbox';
		break;
	}
	if ($oldmsg['m_flags'] & MFLAG_REPLY)
	{
		notice(lang('MESSAGES_REPLY_ALREADY'));
		$action = 'inbox';
		break;
	}
	if ($oldmsg['m_flags'] & MFLAG_DELETE)
	{
		notice(lang('MESSAGES_REPLY_DELETED'));
		$action = 'inbox';
		break;
	}
	if ($oldmsg['m_flags'] & MFLAG_DEAD)
	{
		notice(lang('MESSAGES_REPLY_EMPIRE_DEAD'));
		$action = 'inbox';
		break;
	}
	$dst = $oldmsg['e_id_src'];
	$emp_a = new prom_empire($dst);
	if (!$emp_a->load())
	{
		// this should never happen
		notice(lang('MESSAGES_NO_SUCH_EMPIRE'));
		$action = 'inbox';
		break;
	}
	if (($emp_a->e_flags & EFLAG_DELETE) || ($emp_a->u_id == 0))
	{
		notice(lang('MESSAGES_REPLY_EMPIRE_DEAD'));
		$action = 'inbox';
		break;
	}
	if (($emp_a->e_flags & EFLAG_SILENT) && !($emp1->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_REPLY_EMPIRE_SILENCED'));
		$action = 'inbox';
		break;
	}
	if (($emp1->e_flags & EFLAG_SILENT) && !($emp_a->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_REPLY_SILENCED'));
		$action = 'inbox';
		break;
	}
	if (VALIDATE_REQUIRE && !($emp1->e_flags & EFLAG_VALID) && ($emp1->e_turnsused >= TURNS_VALIDATE) && !($emp_a->e_flags & EFLAG_ADMIN))
	{
		notice(lang('MESSAGES_REPLY_VALIDATE'));
		$action = 'inbox';
		break;
	}

	if (strlen($subj) > 255)
	{
		notice(lang('MESSAGES_SUBJECT_TOO_LONG'));
		$action = 'read';
		$message_id = $reply;
		$message_body = $body;
		break;
	}
	if (lang_isset($subj))
	{
		notice(lang('MESSAGES_SUBJECT_INVALID'));
		$action = 'read';
		$message_id = $reply;
		$message_body = $body;
		break;
	}

	$encbody = bbencode($body);
	if (strlen($encbody) > 65535)
	{
		notice(lang('MESSAGES_BODY_TOO_LONG'));
		$action = 'read';
		$message_id = $reply;
		$message_subj = $subj;
		$message_body = $body;
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('MESSAGES_BODY_INVALID'));
		$action = 'read';
		$message_id = $reply;
		$message_subj = $subj;
		$message_body = $body;
		break;
	}

	$q = $db->prepare('INSERT INTO '. EMPIRE_MESSAGE_TABLE .' (m_id_ref,m_time,e_id_src,e_id_dst,m_subject,m_body,m_flags) VALUES (?,?,?,?,?,?,0)');
	$q->bindIntValue(1, $reply);
	$q->bindIntValue(2, CUR_TIME);
	$q->bindIntValue(3, $emp1->e_id);
	$q->bindIntValue(4, $dst);
	$q->bindStrValue(5, $subj);
	$q->bindStrValue(6, $encbody);
	$q->execute() or warning('Failed to deliver message', 0);

	$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id = ?');
	$q->bindIntValue(1, MFLAG_REPLY);
	$q->bindIntValue(2, $reply);
	$q->execute() or warning('Failed to mark original message as replied', 0);

	notice(lang('MESSAGES_REPLY_COMPLETE', $emp_a));
	$emp_a = NULL;
	logevent(varlist(array('reply', 'dst'), get_defined_vars()));
	$action = 'inbox';
} while (0);
elseif ($action == 'report') do
{
	if (!isFormPost())
		break;
	$id = fixInputNum(getFormVar('msg_id', 0));
	$body = htmlspecialchars(getFormVar('msg_reason'));

	$q = $db->prepare('SELECT m_time,e_id_src,e_name AS e_name_src,e_id_dst,m_subject,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' ON (e_id = e_id_src) WHERE m_id = ? AND e_id_src != 0');
	$q->bindIntValue(1, $id);
	$q->execute() or warning('Failed to retrieve message to report', 0);

	$message = $q->fetch();
	if (!$message)
	{
		notice(lang('MESSAGES_REPORT_NOT_EXIST'));
		$action = 'inbox';
		break;
	}
	if ($message['e_id_dst'] != $emp1->e_id)
	{
		notice(lang('MESSAGES_REPORT_NOT_YOURS'));
		$action = 'inbox';
		break;
	}
	if ($message['e_id_src'] == $emp1->e_id)
	{
		notice(lang('MESSAGES_REPORT_NOT_SELF'));
		$action = 'inbox';
		break;
	}
	if ($message['m_flags'] & MFLAG_REPORT)
	{
		notice(lang('MESSAGES_REPORT_ALREADY'));
		$action = 'inbox';
		break;
	}

	$encbody = bbencode($body);

	if (strlen($encbody) > 65535)
	{
		notice(lang('MESSAGES_REASON_TOO_LONG'));
		$action = 'form_report';
		$message_id = $id;
		break;
	}
	if (lang_isset($encbody))
	{
		notice(lang('MESSAGES_REASON_INVALID'));
		$action = 'form_report';
		$message_id = $id;
		break;
	}

	$emp_a = new prom_empire($message['e_id_src']);
	$emp_a->initdata(array('e_id' => $message['e_id_src'], 'e_name' => $message['e_name_src']));
	$subj = def_lang('MESSAGES_REPORT_SUBJ_LONG', prenum($id), $emp_a, $user1->customdate($message['m_time']));
	// if the empire name is really long, this could overflow
	if (strlen($subj) > 255)
		$subj = def_lang('MESSAGES_REPORT_SUBJ_SHORT', prenum($id), prenum($message['e_id_src']), $user1->customdate($message['m_time']));
	$emp_a = NULL;

	// destination 0 == moderator mailbox
	$q = $db->prepare('INSERT INTO '. EMPIRE_MESSAGE_TABLE .' (m_id_ref,m_time,e_id_src,e_id_dst,m_subject,m_body,m_flags) VALUES (?,?,?,0,?,?,?)');
	$q->bindIntValue(1, $id);
	$q->bindIntValue(2, CUR_TIME);
	$q->bindIntValue(3, $emp1->e_id);
	$q->bindStrValue(4, $subj);
	$q->bindStrValue(5, $encbody);
	$q->bindStrValue(6, MFLAG_REPORT);	// mark the report itself as reported
	$q->execute() or warning('Failed to send abuse report', 0);

	$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id = ?');
	$q->bindIntValue(1, MFLAG_REPORT);
	$q->bindIntValue(2, $id);
	$q->execute() or warning('Failed to mark message as reported', 0);
	notice(lang('MESSAGES_REPORT_COMPLETE'));
	logevent(varlist(array('id'), get_defined_vars()));
	$action = 'inbox';
} while (0);
elseif ($action == 'delete')
{
	if (!isFormPost())
		break;
	$id = fixInputNum(getFormVar('msg_id', 0));
	$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id = ? AND e_id_dst = ? AND e_id_src != 0');
	$q->bindIntValue(1, MFLAG_DELETE);
	$q->bindIntValue(2, $id);
	$q->bindIntValue(3, $emp1->e_id);
	$q->execute() or warning('Failed to delete message', 0);

	if ($q->rowCount() > 0)
		notice(lang('MESSAGES_DELETE_COMPLETE'));
	else	notice(lang('MESSAGES_DELETE_FAILED'));
	logevent(varlist(array('id'), get_defined_vars()));
	$action = 'inbox';
}
elseif ($action == 'delete_marked') do
{
	if (!isFormPost())
		break;
	$ids = getFormArr('msg_ids');
	$idlist = array();
	foreach ($ids as $idx => $id)
	{
		$id = fixInputNum($id);
		if ($id > 0)
			$idlist[] = $id;
	}
	if (count($idlist) == 0)
	{
		notice(lang('MESSAGES_DELETE_FAILED'));
		$action = 'inbox';
		break;
	}

	$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id IN '. sqlArgList($idlist) .' AND e_id_dst = ? AND e_id_src != 0');
	$parms = array();
	$parms[] = MFLAG_DELETE;
	foreach ($idlist as $id)
		$parms[] = $id;
	$parms[] = $emp1->e_id;
	
	$q->bindAllValues($parms);
	$q->execute() or warning('Failed to delete messages', 0);
	
	if ($q->rowCount() > 0)
		notice(lang('MESSAGES_DELETE_COMPLETE'));
	else	notice(lang('MESSAGES_DELETE_FAILED'));

	logevent(varlist(array('idlist'), get_defined_vars()));
	$action = 'inbox';
} while (0);
elseif ($action == 'contact')
{
	$message_dest = fixInputNum(getFormVar('msg_to'));
	$action = 'inbox';
}
elseif (!in_array($action, array('read', 'quote', 'form_report', 'outbox')))
	$action = 'inbox';
notices();
?>
<h3><a href="?location=messages&amp;action=inbox"><?php echo lang('MESSAGES_HEADER_INBOX'); ?></a> - <a href="?location=messages&amp;action=outbox"><?php echo lang('MESSAGES_HEADER_SENT'); ?></a></h3>
<?php
// if reading, replying, or reporting, show the original message
if (in_array($action, array('read', 'quote', 'form_report'))) do
{
	$id = $message_id ? $message_id : fixInputNum(getFormVar('msg_id', 0));

	$q = $db->prepare('SELECT m_id,m_id_ref,m_time,e_id_src,e1.e_name AS e_name_src,e_id_dst,e2.e_name AS e_name_dst,m_subject,m_body,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' e1 ON (e_id_src = e1.e_id) LEFT OUTER JOIN '. EMPIRE_TABLE .' e2 ON (e_id_dst = e2.e_id) WHERE m_id = ? AND (e_id_src = ? OR e_id_dst = ?) AND e_id_src != 0');
	$q->bindIntValue(1, $id);
	$q->bindIntValue(2, $emp1->e_id);
	$q->bindIntValue(3, $emp1->e_id);
	$q->execute() or warning('Unable to retrieve message', 0);

	$message = $q->fetch();
	if (!$message)
	{
		echo lang('MESSAGES_READ_NOT_FOUND') .'<hr />';
		$action = 'inbox';
		break;
	}

	// if no message subject is given, use "no subject" instead
	// both when displaying and for the Reply form
	if (!strlen($message['m_subject']))
		$message['m_subject'] = lang('MESSAGES_LABEL_NO_SUBJECT');

	// if it's being quoted, prefill the decoded message body
	// remove all existing quotes (they can't be nested) and enclose it in a new quote tag
	if ($action == 'quote')
	{
		$action = 'read';
		$message_body = '[quote]'. preg_replace('/\[quote\].*?\[\/quote\]/s', '', bbdecode($message['m_body'])) .'[/quote]';
	}
?>
<table>
<?php
	if ($message['m_id_ref'] != 0)
	{
?>
<tr><td></td><td colspan="2"><?php echo lang('MESSAGES_READ_IN_RESPONSE', '<a href="?location=messages&amp;action=read&amp;msg_id='. $message['m_id_ref'] .'">', '</a>'); ?></td></tr>
<?php
	}

	$emp_a = new prom_empire($message['e_id_src']);
	$emp_a->initdata(array('e_id' => $message['e_id_src'], 'e_name' => $message['e_name_src']));
	if ($message['e_id_dst'])
	{
		$emp_b = new prom_empire($message['e_id_dst']);
		$emp_b->initdata(array('e_id' => $message['e_id_dst'], 'e_name' => $message['e_name_dst']));
	}
	else	$emp_b = lang('MESSAGES_LABEL_MODERATOR');
	$msg_dead = ($message['m_flags'] & MFLAG_DEAD);
?>
<tr><td><?php echo lang('LABEL_FROM'); ?></td><td colspan="2"><b><?php if ($msg_dead) echo '<i>'; echo $emp_a; if ($msg_dead) echo '</i>'; ?></b></td></tr>
<tr><td><?php echo lang('LABEL_TO'); ?></td><td colspan="2"><b><?php echo $emp_b; ?></b></td></tr>
<tr><td><?php echo lang('LABEL_DATE'); ?></td><td colspan="2"><b><?php echo $user1->customdate($message['m_time']); ?></b></td></tr>
<tr><td><?php echo lang('LABEL_SUBJECT'); ?></td><td colspan="2"><?php echo $message['m_subject']; ?></td></tr>
<tr><td rowspan="2"></td><td colspan="2"><?php echo str_replace("\n", '<br />', $message['m_body']); ?></td></tr>
<?php
	// only display quote/report/delete buttons when it's sent TO you and you aren't reporting it
	if (($action == 'read') && ($message['e_id_dst'] == $emp1->e_id))
	{
		$numlinks = 0;
?>
<tr><td><?php
		// only include Quote link if you can reply
		if (!($message['m_flags'] & MFLAG_REPLY) && !$msg_dead)
		{
			if ($numlinks++)
				echo ' ';
?><a href="?location=messages&amp;action=quote&amp;msg_id=<?php echo $message['m_id']; ?>"><?php echo lang('MESSAGES_SUBMIT_QUOTE'); ?></a><?php
		}
		// only include Report link if you haven't reported already (and if the message wasn't from yourself)
		if (!($message['m_flags'] & MFLAG_REPORT) && ($message['e_id_src'] != $emp1->e_id))
		{
			if ($numlinks++)
				echo ' ';
?><a href="?location=messages&amp;action=form_report&amp;msg_id=<?php echo $message['m_id']; ?>"><?php echo lang('MESSAGES_SUBMIT_REPORT'); ?></a><?php
		}
?></td><td class="ar"><?php
		if (!($message['m_flags'] & MFLAG_DELETE))
		{
?>
<form method="post" action="?location=messages">
<div>
<input type="hidden" name="action" value="delete" />
<input type="hidden" name="msg_id" value="<?php echo $message['m_id']; ?>" />
<input type="submit" value="<?php echo lang('MESSAGES_SUBMIT_DELETE'); ?>" />
</div>
</form>
<?php
		}
?></td></tr>
<?php
		// Also, mark it as being read (if it isn't already)
		if (!($message['m_flags'] & MFLAG_READ))
		{
			$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id = ?');
			$q->bindIntValue(1, MFLAG_READ);
			$q->bindIntValue(2, $id);
			$q->execute() or warning('Failed to mark message as read', 0);
			logevent(varlist(array('id'), get_defined_vars()));
		}
	}
?>
</table>
<?php
	if ($action == 'form_report')
	{
?>
<form method="post" action="?location=messages">
<div>
<input type="hidden" name="action" value="report" />
<input type="hidden" name="msg_id" value="<?php echo $id; ?>" />
<?php echo lang('MESSAGES_REPORT_HEADER'); ?><br />
<textarea rows="3" cols="60" name="msg_reason"><?php echo $message_body; ?></textarea><br />
<?php echo lang('MESSAGES_REPORT_WARNING'); ?><br />
<input type="submit" value="<?php echo lang('MESSAGES_SUBMIT_SEND_REPORT'); ?>" />
</div>
</form>
<?php
	}
	elseif (!($message['m_flags'] & MFLAG_REPLY) && !$msg_dead)
	{
		$replyprefix = def_lang('MESSAGES_REPLY_PREFIX');
?>
<form method="post" action="?location=messages">
<div>
<input type="hidden" name="action" value="reply" />
<input type="hidden" name="msg_replyid" value="<?php echo $id; ?>" />
<b><?php echo lang('MESSAGES_REPLY_HEADER'); ?></b><br />
<?php echo lang('LABEL_SUBJECT'); ?> <input type="text" name="msg_subject" size="40" value="<?php
		if ($message_subj)
			echo $message_subj;
		elseif (substr($message['m_subject'], 0, strlen($replyprefix)) != $replyprefix)
			echo truncate($replyprefix . $message['m_subject'], 255);
		else	echo $message['m_subject'];
?>" /><br />
<textarea rows="15" cols="60" name="msg_body"><?php echo $message_body; ?></textarea><br />
<input type="submit" value="<?php echo lang('MESSAGES_SUBMIT_SEND_REPLY'); ?>" />
</div>
</form>
<?php
	}
	$emp_a = NULL;
	$emp_b = NULL;
} while (0);

if (in_array($action, array('inbox', 'outbox')))
{
	if ($action == 'outbox')
	{
		$q = $db->prepare('SELECT m_id,m_time,e_id_dst AS e_id_other,e_name AS e_name_other,m_subject,m_body,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' ON (e_id = e_id_dst) WHERE e_id_src = ? AND e_id_dst != 0 ORDER BY m_id DESC');
		$q->bindIntValue(1, $emp1->e_id);
	}
	else
	{
		$q = $db->prepare('SELECT m_id,m_time,e_id_src AS e_id_other,e_name AS e_name_other,m_subject,m_body,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' ON (e_id = e_id_src) WHERE e_id_dst = ? AND e_id_src != 0 AND m_flags & ? = 0 ORDER BY m_id DESC');
		$q->bindIntValue(1, $emp1->e_id);
		$q->bindIntValue(2, MFLAG_DELETE);
	}
	$q->execute() or warning('Unable to retrieve message list', 0);

	$messages = $q->fetchAll();
	if (count($messages) > 0)
	{
		if ($action == 'outbox')
		{
?>
<table class="inputtable">
<tr><th><?php echo lang('MESSAGES_COLUMN_SUBJECT'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_OUTBOX'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_DATE'); ?></th></tr>
<?php
		}
		else
		{
?>
<form method="post" action="?location=messages">
<table class="inputtable">
<tr><th><?php echo lang('MESSAGES_COLUMN_SUBJECT'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_INBOX'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_DATE'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_MARK'); ?></th></tr>
<?php
		}
		foreach ($messages as $message)
		{
			$emp_a = new prom_empire($message['e_id_other']);
			$emp_a->initdata(array('e_id' => $message['e_id_other'], 'e_name' => $message['e_name_other']));
			if (!strlen($message['m_subject']))
				$message['m_subject'] = lang('MESSAGES_LABEL_NO_SUBJECT');
			$msg_read = ($message['m_flags'] & MFLAG_READ);
			$msg_dead = ($message['m_flags'] & MFLAG_DEAD) && ($action == 'inbox');
?>
<tr><td><?php if (!$msg_read) echo '<b>'; ?><a href="?location=messages&amp;action=read&amp;msg_id=<?php echo $message['m_id']; ?>"><?php echo $message['m_subject']; ?></a><?php if (!$msg_read) echo '</b>'; ?></td>
    <td><?php if ($msg_dead) echo '<i>'; echo $emp_a; if ($msg_dead) echo '</i>'; ?></td>
    <td><?php echo $user1->customdate($message['m_time']); ?></td>
<?php
			if ($action == 'inbox')
				echo '    <td>'. checkbox('msg_ids[]', '', $message['m_id'], FALSE, TRUE, 'msg_ids_'. $message['m_id']) .'</td>';
?></tr>
<?php
			$emp_a = NULL;
		}
		if ($action == 'outbox')
		{
?>
</table>
<?php
		}
		else
		{
?>
<tr><td colspan="4" class="ar"><a href="javascript:togglechecks('msg_ids')"><?php echo lang('MESSAGES_TOGGLE_MARK'); ?></a><br /><input type="hidden" name="action" value="delete_marked" /><input type="submit" value="<?php echo lang('MESSAGES_SUBMIT_DELETE_MARKED'); ?>" /></td></tr>
</table>
</form>
<?php
		}

	}
	else
	{
		if ($action == 'outbox')
			notice(lang('MESSAGES_NONE_SENT'));
		else	notice(lang('MESSAGES_NONE_RECEIVED'));
		notices();
	}

	if ($emp1->e_flags & EFLAG_SILENT)
		echo '<h3>'. lang('MESSAGES_YOU_ARE_SILENCED') .'</h3>';

	if (VALIDATE_REQUIRE && !($emp1->e_flags & EFLAG_VALID) && ($emp1->e_turnsused >= TURNS_VALIDATE))
		echo '<h3>'. lang('MESSAGES_NOT_VALIDATED') .'</h3>';

	if (($emp1->effects->m_message < (MESSAGES_MAXCREDITS - 1) * MESSAGES_DELAY) || ($emp1->e_flags & EFLAG_ADMIN))
	{
?>
<form method="post" action="?location=messages">
<div>
<input type="hidden" name="action" value="send" />
<?php echo lang('MESSAGES_LABEL_SEND', '<input type="text" name="msg_to" size="4" value="'. prenum($message_dest) .'" />'); ?><br />
<?php echo lang('LABEL_SUBJECT'); ?> <input type="text" name="msg_subject" size="40" value="<?php echo $message_subj; ?>" /><br />
<textarea rows="15" cols="60" name="msg_body"><?php echo $message_body; ?></textarea><br />
<input type="submit" value="<?php echo lang('MESSAGES_SUBMIT_SEND_NEW'); ?>" />
</div>
</form>
<?php
	}
	if (!($emp1->e_flags & EFLAG_ADMIN))
		echo lang('MESSAGES_CREDITS_REMAINING', max(0, MESSAGES_MAXCREDITS - ceil($emp1->effects->m_message / MESSAGES_DELAY)));
}
page_footer();
?>
