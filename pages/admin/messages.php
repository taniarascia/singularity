<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: messages.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_MESSAGES_TITLE';

$needpriv = UFLAG_MOD;

page_header();
?>
<h3><a href="?location=admin/messages&amp;action=reports"><?php echo lang('ADMIN_MESSAGES_SECTION_REPORTS'); ?></a> - <a href="?location=admin/messages&amp;action=form_search"><?php echo lang('ADMIN_MESSAGES_SECTION_SEARCH'); ?></a></h3>
<?php
if ($action == 'view') do
{
	$msg_id = fixInputNum(getFormVar('msg_id', 0));

	$q = $db->prepare('SELECT m_id,m_id_ref,m_time,e_id_src,e1.e_name AS e_name_src,e_id_dst,e2.e_name AS e_name_dst,m_subject,m_body,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' e1 ON (e_id_src = e1.e_id) LEFT OUTER JOIN '. EMPIRE_TABLE .' e2 ON (e_id_dst = e2.e_id) WHERE m_id = ?');
	$q->bindIntValue(1, $msg_id);
	$q->execute() or warning('Unable to retrieve message', 0);

	$message = $q->fetch();
	if (!$message)
	{
		echo lang('MESSAGES_READ_NOT_FOUND') .'<hr />';
		$action = 'reports';
		break;
	}
?>
<table>
<?php
	if ($message['m_id_ref'] != 0)
	{
?>
<tr><td></td><td colspan="2"><?php echo lang('MESSAGES_READ_IN_RESPONSE', '<a href="?location=admin/messages&amp;action=view&amp;msg_id='. $message['m_id_ref'] .'">', '</a>'); ?></td></tr>
<?php
	}
	if ($message['e_id_src'])
	{
		$emp_a = new prom_empire($message['e_id_src']);
		$emp_a->initdata(array('e_id' => $message['e_id_src'], 'e_name' => $message['e_name_src']));
	}
	else	$emp_a = lang('MESSAGES_LABEL_SYSTEM');
	if ($message['e_id_dst'])
	{
		$emp_b = new prom_empire($message['e_id_dst']);
		$emp_b->initdata(array('e_id' => $message['e_id_dst'], 'e_name' => $message['e_name_dst']));
	}
	else	$emp_b = lang('MESSAGES_LABEL_MODERATOR');
?>
<tr><td><?php echo lang('LABEL_FROM'); ?></td><td><b><?php echo $emp_a; ?></b></td></tr>
<tr><td><?php echo lang('LABEL_TO'); ?></td><td><b><?php echo $emp_b; ?></b></td></tr>
<tr><td><?php echo lang('LABEL_DATE'); ?></td><td><b><?php echo $user1->customdate($message['m_time']); ?></b></td></tr>
<tr><td><?php echo lang('LABEL_SUBJECT'); ?></td><td><?php echo $message['m_subject']; ?></td></tr>
<tr><td></td><td><?php echo str_replace("\n", '<br />', $message['m_body']); ?></td></tr>
</table>
<?php
	if (($message['e_id_dst'] == 0) && !($message['m_flags'] & MFLAG_READ))
	{
		$q = $db->prepare('UPDATE '. EMPIRE_MESSAGE_TABLE .' SET m_flags = m_flags | ? WHERE m_id = ?');
		$q->bindIntValue(1, MFLAG_READ);
		$q->bindIntValue(2, $msg_id);
		$q->execute() or warning('Failed to mark abuse report as read', 0);
		logevent(varlist(array('msg_id'), get_defined_vars()));
	}
	$emp_a = NULL;
	$emp_b = NULL;
} while (0);
elseif (($action == 'search') || ($action == 'form_search'))
{
	if (!isFormPost())
		$action = 'form_search';
	// This section is structured a bit differently, since we want the form at the top and the results at the bottom
	if ($action == 'search')
	{
		$timelimit = fixInputBool(getFormVar('msgs_timelimit'));
		$_age1 = fixInputNum(getFormVar('msgs_age1'));
		$_age2 = fixInputNum(getFormVar('msgs_age2'));
		$minage = min($_age1, $_age2);
		$maxage = max($_age1, $_age2);
		$empire1 = fixInputNum(getFormVar('msgs_emp1'));
		$empire2 = fixInputNum(getFormVar('msgs_emp2'));
		$bidir = fixInputBool(getFormVar('msgs_bidir'));
		$searchstr = getFormVar('msgs_str');
	}
	else
	{
		$timelimit = 1;
		$minage = 0;
		$maxage = 24;
		$empire1 = 0;
		$empire2 = 0;
		$bidir = 0;
		$searchstr = '';
	}
?>
<form method="post" action="?location=admin/messages">
<table class="inputtable">
<tr><th colspan="2"><?php echo checkbox('msgs_timelimit', '', 1, $timelimit) . lang('ADMIN_MESSAGES_FILTER_AGE', '<input type="text" name="msgs_age1" value="'. $minage .'" size="3" />', '<input type="text" name="msgs_age2" value="'. $maxage .'" size="3" />'); ?></th></tr>
<tr><th><?php echo lang('ADMIN_MESSAGES_FILTER_FROM'); ?></th>
    <td><input type="text" name="msgs_emp1" size="4" value="<?php echo prenum($empire1); ?>" /></td></tr>
<tr><th><?php echo lang('ADMIN_MESSAGES_FILTER_TO'); ?></th>
    <td><input type="text" name="msgs_emp2" size="4" value="<?php echo prenum($empire2); ?>" /></td></tr>
<tr><th colspan="2"><?php echo checkbox('msgs_bidir', lang('ADMIN_MESSAGES_FILTER_REPLIES'), 1, $bidir); ?></th></tr>
<tr><th><?php echo lang('ADMIN_MESSAGES_FILTER_STRING'); ?></th>
    <td><input type="text" name="msgs_str" size="12" value="<?php echo htmlspecialchars($searchstr); ?>" /></td></tr>
<tr><th colspan="2"><input type="hidden" name="action" value="search" /><input type="submit" value="<?php echo lang('ADMIN_MESSAGES_SEARCH_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
	if ($action == 'search')
	{
		$checks = array();
		$values = array();
		if ($bidir)
		{
			if ($empire1 && $empire2)
			{
				$checks[] = '((e_id_src = ? AND e_id_dst = ?) OR (e_id_dst = ? AND e_id_src = ?))';
				$values[] = $empire1;
				$values[] = $empire2;
				$values[] = $empire1;
				$values[] = $empire2;
			}
			elseif ($empire1)
			{
				$checks[] = '(e_id_src = ? OR e_id_dst = ?)';
				$values[] = $empire1;
				$values[] = $empire1;
			}
			elseif ($empire2)
			{
				$checks[] = '(e_id_src = ? OR e_id_dst = ?)';
				$values[] = $empire2;
				$values[] = $empire2;
			}
		}
		else
		{
			if ($empire1 && $empire2)
			{
				$checks[] = '(e_id_src = ? AND e_id_dst = ?)';
				$values[] = $empire1;
				$values[] = $empire2;
			}
			elseif ($empire1)
			{
				$checks[] = '(e_id_src = ?)';
				$values[] = $empire1;
			}
			elseif ($empire2)
			{
				$checks[] = '(e_id_dst = ?)';
				$values[] = $empire2;
			}
		}
		if ($searchstr != '')
		{
			$checks[] = '(m_body LIKE ?)';
			$values[] = '%'. $searchstr .'%';
		}
		if ($timelimit)
		{
			$checks[] = '(m_time BETWEEN ? AND ?)';
			$values[] = CUR_TIME - $maxage * 3600;
			$values[] = CUR_TIME - $minage * 3600;
		}
		// always filter out reports
		$checks[] = 'e_id_dst != 0';
		// and self notes
		$checks[] = 'e_id_src != 0';
		$query = implode(' AND ', $checks);
		$q = $db->prepare('SELECT m_id, m_time, m_subject, m_body, '.
				'e_id_src, e1.e_name AS e_name_src, '.
				'e_id_dst, e2.e_name AS e_name_dst FROM '. EMPIRE_MESSAGE_TABLE .' '.
				'LEFT OUTER JOIN '. EMPIRE_TABLE .' e1 ON (e_id_src = e1.e_id) '.
				'LEFT OUTER JOIN '. EMPIRE_TABLE .' e2 ON (e_id_dst = e2.e_id) '.
				'WHERE '. $query .' '.
				'ORDER BY m_id DESC');
		$q->bindAllValues($values);
		$q->execute() or warning('Failed to fetch messages', 0);
		$messages = $q->fetchAll();
		$numrows = count($messages);
		logevent(varlist(array('timelimit', 'minage', 'maxage', 'empire1', 'empire2', 'bidir', 'searchstr', 'numrows'), get_defined_vars()));
		if ($numrows > 0)
		{
?>
<table class="inputtable">
<tr><th><?php echo lang('MESSAGES_COLUMN_SUBJECT'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_INBOX'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_OUTBOX'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_DATE'); ?></th></tr>
<?php
			foreach ($messages as $message)
			{
				if ($message['e_id_src'])
				{
					$emp_a = new prom_empire($message['e_id_src']);
					$emp_a->initdata(array('e_id' => $message['e_id_src'], 'e_name' => $message['e_name_src']));
				}
				else	$emp_a = lang('MESSAGES_LABEL_SYSTEM');
				if ($message['e_id_dst'])
				{
					$emp_b = new prom_empire($message['e_id_dst']);
					$emp_b->initdata(array('e_id' => $message['e_id_dst'], 'e_name' => $message['e_name_dst']));
				}
				else	$emp_b = lang('MESSAGES_LABEL_MODERATOR');
?>
<tr><td><a href="?location=admin/messages&amp;action=view&amp;msg_id=<?php echo $message['m_id']; ?>"><?php if ($message['m_subject']) echo $message['m_subject']; else echo lang('MESSAGES_LABEL_NO_SUBJECT'); ?></a></td>
    <td><?php echo $emp_a; ?></td>
    <td><?php echo $emp_b; ?></td>
    <td><?php echo $user1->customdate($message['m_time']); ?></td></tr>
<?php
				$emp_a = NULL;
				$emp_b = NULL;
			}
?>
</table>
<?php
		}
		else	notice(lang('ADMIN_MESSAGES_NO_RESULTS'));
	}
}
else	$action = 'reports';

if ($action == 'reports')
{
	$q = $db->prepare('SELECT m_id,m_time,e_id_src,e_name AS e_name_src,m_subject,m_body,m_flags FROM '. EMPIRE_MESSAGE_TABLE .' LEFT OUTER JOIN '. EMPIRE_TABLE .' ON (e_id = e_id_src) WHERE e_id_dst = 0 ORDER BY m_id DESC');
	$q->execute() or warning('Unable to retrieve abuse report list', 0);

	$messages = $q->fetchAll();
	if (count($messages) > 0)
	{
?>
<table class="inputtable">
<tr><th><?php echo lang('MESSAGES_COLUMN_SUBJECT'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_INBOX'); ?></th>
    <th><?php echo lang('MESSAGES_COLUMN_DATE'); ?></th></tr>
<?php
		foreach ($messages as $message)
		{
			if ($message['e_id_src'])
			{
				$emp_a = new prom_empire($message['e_id_src']);
				$emp_a->initdata(array('e_id' => $message['e_id_src'], 'e_name' => $message['e_name_src']));
			}
			else	$emp_a = lang('MESSAGES_LABEL_SYSTEM');
?>
<tr><td><?php if (!($message['m_flags'] & MFLAG_READ)) echo '<b>'; ?><a href="?location=admin/messages&amp;action=view&amp;msg_id=<?php echo $message['m_id']; ?>"><?php echo $message['m_subject']; ?></a><?php if (!($message['m_flags'] & MFLAG_READ)) echo '</b>'; ?></td>
    <td><?php echo $emp_a; ?></td>
    <td><?php echo $user1->customdate($message['m_time']); ?></td></tr>
<?php
			$emp_a = NULL;
		}
?>
</table>
<?php
	}
	else	notice(lang('ADMIN_MESSAGES_NO_REPORTS'));
}

notices(1);	// print it in <h4></h4> and without a <hr /> afterwards
page_footer();
?>
