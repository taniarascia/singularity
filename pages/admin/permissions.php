<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: permissions.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_PERMISSIONS_TITLE';

$needpriv = UFLAG_ADMIN;

page_header();

$permlabels = array(PERM_IPV4 => lang('ADMIN_PERMISSIONS_TYPE_IPV4'), PERM_IPV6 => lang('ADMIN_PERMISSIONS_TYPE_IPV6'), PERM_EMAIL => lang('ADMIN_PERMISSIONS_TYPE_EMAIL'));

$id = 0;

if (($action == 'add') || ($action == 'update')) do
{
	if (!isFormPost())
		break;
	if ($action == 'update')
		$id = fixInputNum(getFormVar('perm_id'));
	// the value -1 is special - since it's nonzero, it won't blank the form,
	// and since it's not greater than zero, it'll still present an Add button instead of Update/Delete
	elseif ($action == 'add')
		$id = -1;

	$indata = getFormVar('perm_data');
	$duration = getFormVar('perm_duration');
	$type = getFormVar('perm_type');
	$except = fixInputBool(getFormVar('perm_except'));
	$comment = htmlspecialchars(getFormVar('perm_comment'));
	$reason = htmlspecialchars(getFormVar('perm_reason'));

	if (!isset($permlabels[$type]))
	{
		notice(lang('ADMIN_PERMISSIONS_INVALID_TYPE'));
		break;
	}
	if (strlen($comment) > 255)
	{
		notice(lang('ADMIN_PERMISSIONS_COMMENT_TOO_LONG'));
		break;
	}
	if (lang_isset($comment))
	{
		notice(lang('ADMIN_PERMISSIONS_COMMENT_INVALID'));
		break;
	}
	if (strlen($reason) > 255)
	{
		notice(lang('ADMIN_PERMISSIONS_REASON_TOO_LONG'));
		break;
	}
	if (lang_isset($reason))
	{
		notice(lang('ADMIN_PERMISSIONS_REASON_INVALID'));
		break;
	}

	if ($duration == 0)
		$expire = 0;
	elseif (is_numeric($duration))
		$expire = $duration * 86400 + CUR_TIME;
	else	$expire = strtotime($duration);

	if ($id == -1)
		$data = explode(' ', $indata);
	else	$data = array($indata);
	foreach ($data as $indata)
	{
		if ($type == PERM_IPV4)
		{
			// Straight IP address: "192.168.1.1"
			if (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $indata))
			{
				$addr = $indata;
				$mask = '255.255.255.255';
			}
			// Wildcard class C: "192.168.1.*"
			elseif (preg_match('/^(?:\d{1,3}\.){3}\*$/', $indata))
			{
				$addr = str_replace('*', '0', $indata);
				$mask = '255.255.255.0';
			}
			// Wildcard class B: "192.168.*.*"
			elseif (preg_match('/^(?:\d{1,3}\.){2}\*\.\*$/', $indata))
			{
				$addr = str_replace('*', '0', $indata);
				$mask = '255.255.0.0';
			}
			// Wildcard class A: "192.*.*.*"
			elseif (preg_match('/^(?:\d{1,3}\.)\*\.\*\.\*$/', $indata))
			{
				$addr = str_replace('*', '0', $indata);
				$mask = '255.0.0.0';
			}
			// CIDR notation: "192.168.1.0/24"
			elseif (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $indata))
			{
				list($addr, $cidr) = explode('/', $indata);
				if (($cidr < 1) || ($cidr > 32))
				{
					notice(lang('ADMIN_PERMISSIONS_INVALID_DATA', $indata));
					break;
				}
				// generate mask
				$mask = long2ip(ip2long('255.255.255.255') << (32 - $cidr));
				// and mask off the address
				$addr = long2ip(ip2long($addr) & ip2long($mask));
			}
			// Address/mask notation: "192.168.1.0/255.255.255.0"
			elseif (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}\/(?:\d{1,3}\.){3}\d{1,3}$/', $indata))
			{
				list($addr, $mask) = explode('/', $indata);
				// mask off the address
				$addr = long2ip(ip2long($addr) & ip2long($mask));
			}
			else
			{
				notice(lang('ADMIN_PERMISSIONS_INVALID_DATA', $indata));
				break;
			}
			$outdata = $addr.'/'.$mask;
		}
		if ($type == PERM_IPV6)
		{
			// Add CIDR suffix if not present - all formats require it
			$addr = explode('/', $indata);
			if (count($addr) == 1)
				$cidr = 128;
			else	$cidr = $addr[1];
			$addr = $addr[0];
			if (($cidr < 1) || ($cidr > 128))
			{
				notice(lang('ADMIN_PERMISSIONS_INVALID_DATA', $indata));
				break;
			}
			// Regular expressions borrowed from Net-IPv6Addr-0.2
			// Full IP address
			if (preg_match('/^(?:[0-9A-F]{1,4}:){7}[0-9A-F]{1,4}$/i', $addr))
				;
			// Partial addresses
			elseif (preg_match('/^[0-9A-F]{0,4}::$/i', $addr))
				;
			elseif (preg_match('/^:(?::[0-9A-F]{1,4}){1,6}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){1,6}:$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:)(?::[0-9A-F]{1,4}){1,6}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){2}(?::[0-9A-F]{1,4}){1,5}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){3}(?::[0-9A-F]{1,4}){1,4}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){4}(?::[0-9A-F]{1,4}){1,3}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){5}(?::[0-9A-F]{1,4}){1,2}$/i', $addr))
				;
			elseif (preg_match('/^(?:[0-9A-F]{1,4}:){6}(?::[0-9A-F]{1,4})$/i', $addr))
				;
			// IPv4 compatibility addresses
			elseif (preg_match('/^(?:0:){5}ffff:(?:\d{1,3}\.){3}\d{1,3}$/i', $addr))
				;
			elseif (preg_match('/^(?:0:){6}(?:\d{1,3}\.){3}\d{1,3}$/i', $addr))
				;
			elseif (preg_match('/^::(?:ffff:)?(?:\d{1,3}\.){3}\d{1,3}$/i', $addr))
				;
			else
			{
				notice(lang('ADMIN_PERMISSIONS_INVALID_DATA', $indata));
				break;
			}
			$outdata = $addr.'/'.$cidr;
		}
		if ($type == PERM_EMAIL)
		{
			// validate the email address with wildcards replaced with innocuous text
			if (!validate_email(str_replace('*', 'a', $indata)))
			{
				notice(lang('ADMIN_PERMISSIONS_INVALID_DATA', $indata));
				break;
			}
			$outdata = $indata;
		}

		if ($except)
		{
			$type |= PERM_EXCEPT;
			$reason = '';
		}

		if ($id > 0)
		{
			$action = 'update';
			$q = $db->prepare('UPDATE '. PERMISSION_TABLE .' SET p_type=?, p_criteria=?, p_comment=?, p_reason=?, p_updatetime=?, p_expire=? WHERE p_id=?');
			$q->bindIntValue(1, $type);
			$q->bindIntValue(2, $outdata);
			$q->bindStrValue(3, $comment);
			$q->bindStrValue(4, $reason);
			$q->bindIntValue(5, CUR_TIME);
			$q->bindIntValue(6, $expire);
			$q->bindIntValue(7, $id);
			$q->execute() or warning('Failed to update record in permissions table', 0);
			notice(lang('ADMIN_PERMISSIONS_UPDATE_COMPLETE'));
			logevent(varlist(array('id', 'type', 'indata', 'outdata', 'duration', 'expire', 'comment', 'reason'), get_defined_vars()));
			$id = 0;
		}
		else
		{
			$q = $db->prepare('INSERT INTO '. PERMISSION_TABLE .' (p_type,p_criteria,p_comment,p_reason,p_createtime,p_updatetime,p_expire) VALUES (?,?,?,?,?,?,?)');
			$q->bindIntValue(1, $type);
			$q->bindIntValue(2, $outdata);
			$q->bindStrValue(3, $comment);
			$q->bindStrValue(4, $reason);
			$q->bindIntValue(5, CUR_TIME);
			$q->bindIntValue(6, CUR_TIME);
			$q->bindIntValue(7, $expire);
			$q->execute() or warning('Failed to add record to permissions table', 0);
			notice(lang('ADMIN_PERMISSIONS_ADD_COMPLETE', $indata));
			logevent(varlist(array('type', 'indata', 'outdata', 'duration', 'expire', 'comment', 'reason'), get_defined_vars()));
			$id = 0;
		}
	}
} while (0);
if ($action == 'remove') do
{
	if (!isFormPost())
		break;
	$id = fixInputNum(getFormVar('perm_id'));

	$q = $db->prepare('DELETE FROM '. PERMISSION_TABLE .' WHERE p_id = ?');
	$q->bindIntValue(1, $id);
	$q->execute() or warning('Failed to delete record from permissions table', 0);
	notice(lang('ADMIN_PERMISSIONS_REMOVE_COMPLETE'));
	logevent(varlist(array('id'), get_defined_vars()));
	$id = 0;
} while (0);
notices();

$q = $db->query('SELECT * FROM '. PERMISSION_TABLE .' ORDER BY p_type ASC, p_criteria ASC') or warning('Failed to read permission table', 0);
$permissions = $q->fetchAll();
?>
<table>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_ID'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_TYPE'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_CRITERIA'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_CREATED'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_UPDATED'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_LASTHIT'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_HITCOUNT'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_EXPIRE'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_COMMENT'); ?></th>
    <th><?php echo lang('ADMIN_PERMISSIONS_COLUMN_REASON'); ?></th></tr>
<?php
foreach ($permissions as $record)
{
?>
<tr><td class="ac"><a href="?location=admin/permissions&amp;action=edit&amp;perm_id=<?php echo $record['p_id']; ?>"><?php echo $record['p_id']; ?></a></td>
    <td><?php
	if ($record['p_type'] & PERM_EXCEPT)
		echo lang('ADMIN_PERMISSIONS_FORMAT_ALLOW', $permlabels[$record['p_type'] & PERM_MASK]);
	else	echo lang('ADMIN_PERMISSIONS_FORMAT_DENY', $permlabels[$record['p_type'] & PERM_MASK]);
?></td>
    <td><?php echo $record['p_criteria']; ?></td>
    <td><?php echo $user1->customdate($record['p_createtime']); ?></td>
    <td><?php echo $user1->customdate($record['p_updatetime']); ?></td>
    <td><?php
	if ($record['p_type'] & PERM_EXCEPT)
		echo lang('ADMIN_PERMISSIONS_UNUSED');
	elseif ($record['p_lasthit'] == 0)
		echo lang('ADMIN_PERMISSIONS_DATE_NEVER');
	else	echo $user1->customdate($record['p_lasthit']);
?></td>
    <td><?php if ($record['p_type'] & PERM_EXCEPT) echo lang('ADMIN_PERMISSIONS_UNUSED'); else echo $record['p_hitcount']; ?></td>
    <td><?php if ($record['p_expire'] == 0) echo lang('ADMIN_PERMISSIONS_DATE_FOREVER'); else echo $user1->customdate($record['p_expire']); ?></td>
    <td><?php echo $record['p_comment']; ?></td>
    <td><?php if ($record['p_type'] & PERM_EXCEPT) echo lang('ADMIN_PERMISSIONS_UNUSED'); else echo $record['p_reason']; ?></td></tr>
<?php
}
?>
</table>
<?php
// fill in blanks for form fields if there was either no action selected or the action was successful
// otherwise, these will be left intact in case there was an error in the form inputs
if ($id == 0)
{
	$indata = '';
	$duration = 0;
	$type = PERM_IPV4;
	$except = false;
	$comment = '';
	$reason = '';
}
// if a permission entry was just selected, load its values into the table
if ($action == 'edit')
{
	$q = $db->prepare('SELECT * FROM '. PERMISSION_TABLE .' WHERE p_id = ?');
	$q->bindIntValue(1, fixInputNum(getFormVar('perm_id')));
	$q->execute() or warning('Failed to fetch record from permissions table', 0);
	$row = $q->fetch();

	$id = $row['p_id'];
	$indata = $row['p_criteria'];
	$duration = $row['p_expire'] ? gmdate('Y/m/d H:i:s O', $row['p_expire']) : 0;
	$type = $row['p_type'] & PERM_MASK;
	$except = $row['p_type'] & PERM_EXCEPT;
	$comment = $row['p_comment'];
	$reason = $row['p_reason'];
}
?>
<form method="post" action="?location=admin/permissions">
<table class="inputtable">
<tr><th colspan="2"><?php if ($id > 0) echo lang('ADMIN_PERMISSIONS_HEADER_UPDATE', prenum($id)); else echo lang('ADMIN_PERMISSIONS_HEADER_ADD'); ?></th></tr>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_LABEL_TYPE'); ?></th><td><?php echo radiobutton('perm_type', $permlabels[PERM_IPV4], PERM_IPV4, ($type & PERM_MASK) == PERM_IPV4) .' '. radiobutton('perm_type', $permlabels[PERM_IPV6], PERM_IPV6, ($type & PERM_MASK) == PERM_IPV6) .' '. radiobutton('perm_type', $permlabels[PERM_EMAIL], PERM_EMAIL, ($type & PERM_MASK) == PERM_EMAIL); ?><br /><?php echo checkbox('perm_except', lang('ADMIN_PERMISSIONS_TYPE_EXCEPT'), 1, $except); ?></td></tr>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_LABEL_CRITERIA'); ?></th><td><input type="text" name="perm_data" value="<?php echo $indata; ?>" /></td></tr>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_LABEL_COMMENT'); ?></th><td><input type="text" name="perm_comment" maxlength="255" value="<?php echo $comment; ?>" /><br /><?php echo lang('ADMIN_PERMISSIONS_LABEL_COMMENT_NOTE'); ?></td></tr>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_LABEL_REASON'); ?></th><td><input type="text" name="perm_reason" maxlength="255" value="<?php echo $reason; ?>" /><br /><?php echo lang('ADMIN_PERMISSIONS_LABEL_REASON_NOTE'); ?></td></tr>
<tr><th><?php echo lang('ADMIN_PERMISSIONS_LABEL_DURATION'); ?></th><td><input type="text" name="perm_duration" value="<?php echo $duration; ?>" /><br /><?php echo lang('ADMIN_PERMISSIONS_LABEL_DURATION_NOTE'); ?></td></tr>
<tr><td colspan="2" class="ac">
<?php if ($id > 0) { ?>
<input type="hidden" name="action" value="update" /><input type="hidden" name="perm_id" value="<?php echo $id; ?>" /><input type="submit" value="<?php echo lang('ADMIN_PERMISSIONS_UPDATE_SUBMIT'); ?>" />
<?php } else { ?>
<input type="hidden" name="action" value="add" /><input type="submit" value="<?php echo lang('ADMIN_PERMISSIONS_ADD_SUBMIT'); ?>" />
<?php } ?>
</td></tr>
</table>
</form>
<?php
if ($id > 0)
{
?>
<form method="post" action="?location=admin/permissions">
<table class="inputtable">
<tr><th><input type="hidden" name="action" value="remove" /><input type="hidden" name="perm_id" value="<?php echo $id; ?>" /><input type="submit" value="<?php echo lang('ADMIN_PERMISSIONS_REMOVE_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
}
page_footer();
?>
