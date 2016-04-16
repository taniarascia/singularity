<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: users.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_USERS_TITLE';

$needpriv = UFLAG_ADMIN;

if (in_array($action, array('edit', 'update', 'delete')))
	$lock['user2'] = fixInputNum(getFormVar('user_id'));

page_header();

if ($action == 'add') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_USERS_CREATE_NEED_CONFIRM'));
		break;
	}

	// proceed to Edit page afterwards
	$action = 'edit';

	$user2 = new prom_user();
	$username = 'newuser_'. md5(uniqid(mt_rand(), TRUE));
	$user2->create($username, 'changeme', 'New User', $username .'@example.com', DEFAULT_LANGUAGE);
	$entities[] = $user2;
	$lock['user2'] = $user2->u_id;
	notice(lang('ADMIN_USERS_CREATE_COMPLETE'));
	logevent();
} while (0);

if ($action == 'delete') do
{
	if (!isFormPost())
		break;
	$confirm = fixInputBool(getFormVar('confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_USERS_DELETE_NEED_CONFIRM'));
		break;
	}

	if ($lock['user2'] == 0)
	{
		notice(lang('ADMIN_USERS_NEED_USER'));
		break;
	}

	if ($db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = ? OR u_oldid = ?', array($user2->u_id, $user2->u_id)) > 0)
	{
		notice(lang('ADMIN_USERS_DELETE_IN_USE'));
		break;
	}

	if ($db->queryCell('SELECT COUNT(*) FROM '. HISTORY_EMPIRE_TABLE .' WHERE u_id = ?', array($user2->u_id)) > 0)
	{
		notice(lang('ADMIN_USERS_DELETE_IN_HISTORY'));
		break;
	}

	// need to log it here while it still exists
	logevent();
	// it's still be in $entities[], but nothing has actually been modified so save() won't do anything

	$q = $db->prepare('DELETE FROM '. USER_TABLE .' WHERE u_id = ?');
	$q->bindIntValue(1, $user2->u_id);
	$q->execute();
	$db->deleteLock(ENT_USER, $user2->u_id);
	$user2 = NULL;

	notice(lang('ADMIN_USERS_DELETE_COMPLETE'));
} while (0);

if ($action == 'update') do
{
	if (!isFormPost())
		break;
	if ($lock['user2'] == 0)
	{
		notice(lang('ADMIN_USERS_NEED_USER'));
		break;
	}

	// return to Edit page afterwards
	$action = 'edit';

	$u_username = getFormVar('u_username');
	$u_password = getFormVar('u_password');
	$u_password_verify = getFormVar('u_password_verify');
	$u_flags = array_sum(getFormArr('u_flags'));
	$u_name = htmlspecialchars(getFormVar('u_name'));
	$u_email = getFormVar('u_email');
	$u_comment = getFormVar('u_comment');
	$u_timezone = getFormVar('u_timezone');
	$u_style = getFormVar('u_style');
	$u_lang = getFormVar('u_lang');
	$u_dateformat = getFormVar('u_dateformat');

	// don't allow admins to remove their own privileges
	if ($user2->u_id == $user1->u_id)
		$u_flags |= UFLAG_ADMIN | UFLAG_MOD;

	if (empty($u_username))
	{
		notice(lang('INPUT_NEED_USERNAME'));
		break;
	}
	if (strlen($u_username) > 255)
	{
		notice(lang('INPUT_USERNAME_TOO_LONG'));
		break;
	}
	if (lang_isset($u_username))
	{
		notice(lang('INPUT_USERNAME_INVALID'));
		break;
	}
	if (empty($u_name))
	{
		notice(lang('INPUT_NEED_NICKNAME'));
		break;
	}
	if (strlen($u_name) > 255)
	{
		notice(lang('INPUT_NICKNAME_TOO_LONG'));
		break;
	}
	if (lang_isset($u_name))
	{
		notice(lang('INPUT_NICKNAME_INVALID'));
		break;
	}
	if (!validate_email($u_email))
	{
		notice(lang('INPUT_NEED_EMAIL'));
		break;
	}
	if (strlen($u_email) > 255)
	{
		notice(lang('INPUT_EMAIL_TOO_LONG'));
		break;
	}
	if (strlen($u_dateformat) > 64)
	{
		notice(lang('INPUT_DATEFORMAT_TOO_LONG'));
		break;
	}
	if ($db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE .' WHERE u_id != ? AND u_username = ?', array($user2->u_id, $u_username)) > 0)
	{
		notice(lang('INPUT_USERNAME_IN_USE'));
		break;
	}
	if ($db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE .' WHERE u_id != ? AND u_email = ?', array($user2->u_id, $u_email)) > 0)
	{
		notice(lang('INPUT_EMAIL_IN_USE'));
		break;
	}

	if (strlen($u_password) > 0)
	{
		if ($u_password != $u_password_verify)
		{
			notice(lang('INPUT_PASSWORD_MISMATCH'));
			break;
		}
		$user2->setPassword($u_password);
	}
	$user2->u_username = $u_username;
	$user2->u_flags = $u_flags;
	$user2->u_name = $u_name;
	$user2->u_email = $u_email;
	$user2->u_comment = $u_comment;
	$user2->u_timezone = $u_timezone;
	$user2->u_style = $u_style;
	$user2->u_lang = $u_lang;
	$user2->u_dateformat = $u_dateformat;

	notice(lang('ADMIN_USERS_UPDATE_COMPLETE'));
	logevent(varlist(array('u_username', 'u_flags', 'u_name', 'u_email', 'u_timezone', 'u_style', 'u_lang'), get_defined_vars()));
} while (0);

if ($action == 'edit') do
{
	$empcount = 0;
?>
<form method="post" action="?location=admin/users">
<table class="inputtable">
<tr><th colspan="4"><?php echo lang('ADMIN_USERS_EDIT_HEADER', $user2); ?><input type="hidden" name="user_id" value="<?php echo $user2->u_id; ?>" /></th></tr>
<tr><th><?php echo lang('LABEL_USERNAME'); ?></th><td><input type="text" name="u_username" value="<?php echo htmlspecialchars($user2->u_username); ?>" /></td>
    <th><?php echo lang('LABEL_PASSWORD_NEW'); ?></th><td><input type="password" name="u_password" value="" /></td></tr>
<tr><th><?php echo lang('LABEL_NICKNAME'); ?></th><td><input type="text" name="u_name" value="<?php echo $user2->u_name; ?>" /></td>
    <th><?php echo lang('LABEL_PASSWORD_VERIFY'); ?></th><td><input type="password" name="u_password_verify" value="" /></td></tr>
<tr><th><?php echo lang('LABEL_EMAIL'); ?></th><td><input type="text" name="u_email" value="<?php echo $user2->u_email; ?>" /></td>
    <th><?php echo lang('ADMIN_USERS_EDIT_LANGUAGE'); ?></th><td><?php
	$langlist = array();
	foreach ($lang as $id => $data)
		$langlist[$id] = $data['LANG_ID'];
	echo optionlist('u_lang', $langlist, $user2->u_lang);
?></td></tr>
<tr><th><?php echo lang('ADMIN_USERS_EDIT_TIMEZONE'); ?></th><td><?php
	$zonelist = array();
	foreach ($timezones as $offset => $name)
		$zonelist[$offset] = $name;
	echo optionlist('u_timezone', $zonelist, $user2->u_timezone);
?></td>
    <th rowspan="3"><?php echo lang('ADMIN_USERS_EDIT_FLAGS'); ?></th>
    <td rowspan="3"><?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_ADMIN'), UFLAG_ADMIN, ($user2->u_flags & UFLAG_ADMIN), ($user2->u_id != $user1->u_id)); ?> -
        <?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_MOD'), UFLAG_MOD, ($user2->u_flags & UFLAG_MOD), ($user2->u_id != $user1->u_id)); ?><br />
        <?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_DISABLE'), UFLAG_DISABLE, ($user2->u_flags & UFLAG_DISABLE)); ?> -
        <?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_VALID'), UFLAG_VALID, ($user2->u_flags & UFLAG_VALID)); ?><br />
        <?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_CLOSED'), UFLAG_CLOSED, ($user2->u_flags & UFLAG_CLOSED)); ?> -
        <?php echo checkbox('u_flags[]', lang('ADMIN_USERS_EDIT_FLAG_WATCH'), UFLAG_WATCH, ($user2->u_flags & UFLAG_WATCH)); ?></td></tr>
<tr><th><?php echo lang('ADMIN_USERS_EDIT_DATEFORMAT'); ?></th><td><input type="text" name="u_dateformat" value="<?php echo htmlspecialchars($user2->u_dateformat); ?>" /></td></tr>
<tr><th><?php echo lang('ADMIN_USERS_EDIT_STYLE'); ?></th><td><?php
	$stylelist = array();
	foreach ($styles as $name => $style)
		$stylelist[$name] = $style['name'];
	echo optionlist('u_style', $stylelist, $user2->u_style);
?></td></tr>
<tr><th><?php echo lang('ADMIN_USERS_EDIT_COMMENT'); ?></th><td colspan="3"><input type="text" name="u_comment" value="<?php echo htmlspecialchars($user2->u_comment); ?>" size="64" /></td></tr>
<tr><th colspan="2"><?php echo lang('ADMIN_USERS_EDIT_LIVE_EMPIRES'); ?></th>
    <th colspan="2"><?php echo lang('ADMIN_USERS_EDIT_DEAD_EMPIRES'); ?></th></tr>
<tr><td colspan="2"><?php
	$q = $db->prepare('SELECT e_id,e_name FROM '. EMPIRE_TABLE .' WHERE u_id = ?');
	$q->bindIntValue(1, $user2->u_id);
	$q->execute();
	$emps = $q->fetchAll();
	if (count($emps) == 0)
		echo lang('ADMIN_USERS_EDIT_NONE') .'<br />';
	else foreach ($emps as $edata)
	{
		$emp_a = new prom_empire($edata['e_id']);
		$emp_a->initdata($edata);
		echo $emp_a .'<br />';
		$emp_a = NULL;
	}
	$empcount += count($emps);
?></td>
    <td colspan="2"><?php
	$q = $db->prepare('SELECT e_id,e_name FROM '. EMPIRE_TABLE .' WHERE u_oldid = ?');
	$q->bindIntValue(1, $user2->u_id);
	$q->execute();
	$emps = $q->fetchAll();
	if (count($emps) == 0)
		echo lang('ADMIN_USERS_EDIT_NONE') .'<br />';
	else foreach ($emps as $edata)
	{
		$emp_a = new prom_empire($edata['e_id']);
		$emp_a->initdata($edata);
		echo $emp_a .'<br />';
		$emp_a = NULL;
	}
	$empcount += count($emps);
?></td></tr>
<tr><th colspan="4"><?php echo lang('ADMIN_USERS_EDIT_STATS'); ?></th></tr>
<tr><th><?php echo lang('COLUMN_KILLS'); ?></th><td><?php echo $user2->u_kills; ?></td>
    <th><?php echo lang('COLUMN_DEATHS'); ?></th><td><?php echo $user2->u_deaths; ?></td></tr>
<tr><th><?php echo lang('COLUMN_ATTACKS'); ?></th><td><?php echo lang('COMMON_NUMBER_PERCENT', $user2->u_offtotal, percent($user2->u_offsucc / max($user2->u_offtotal, 1) * 100)); ?></td>
    <th><?php echo lang('COLUMN_DEFENDS'); ?></th><td><?php echo lang('COMMON_NUMBER_PERCENT', $user2->u_deftotal, percent($user2->u_defsucc / max($user2->u_deftotal, 1) * 100)); ?></td></tr>
<tr><th><?php echo lang('COLUMN_AVGRANK'); ?></th><td><?php echo percent($user2->u_avgrank * 100, 2); ?></td>
    <th><?php echo lang('COLUMN_BESTRANK'); ?></th><td><?php echo percent($user2->u_bestrank * 100, 2); ?></td></tr>
<tr><th><?php echo lang('ADMIN_USERS_EDIT_CREATE'); ?></th><td><?php echo $user1->customdate($user2->u_createdate); ?></td>
    <th><?php echo lang('ADMIN_USERS_EDIT_ACCESS'); ?></th><td><?php echo $user1->customdate($user2->u_lastdate); ?></td></tr>
<tr><th><?php echo lang('COLUMN_ROUNDSPLAYED'); ?></th><td><?php echo lang('COMMON_NUMBER_PERCENT', $user2->u_numplays, percent($user2->u_sucplays / max($user2->u_numplays, 1) * 100)); ?></td>
    <th colspan="2"><input type="hidden" name="action" value="update" /><input type="submit" value="<?php echo lang('ADMIN_USERS_EDIT_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
	// once an empire is recorded in history, its owner can never be deleted
	$empcount += $db->queryCell('SELECT COUNT(*) FROM '. HISTORY_EMPIRE_TABLE .' WHERE u_id = ?', array($user2->u_id));
	if ($empcount == 0)
	{
?>
<hr />
<form method="post" action="?location=admin/users">
<table class="inputtable">
<tr><th><?php echo lang('ADMIN_USERS_DELETE_HEADER', $user2); ?><input type="hidden" name="user_id" value="<?php echo $user2->u_id; ?>" /></th></tr>
<tr><td><?php echo checkbox('confirm', lang('ADMIN_USERS_DELETE_CONFIRM')); ?></td></tr>
<tr><th><input type="hidden" name="action" value="delete" /><input type="submit" value="<?php echo lang('ADMIN_USERS_DELETE_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
	}
?>
<hr />
<?php
} while (0);
notices(1);

prom_session::initvar('admin_users_sortcol', 'uid');
prom_session::initvar('admin_users_sortdir', 'asc');
prom_session::initvar('admin_users_page', 1);

$sortcol = getFormVar('sortcol', $_SESSION['admin_users_sortcol']);
$sortdir = getFormVar('sortdir', $_SESSION['admin_users_sortdir']);
$curpage = fixInputNum(getFormVar('page', $_SESSION['admin_users_page']));
$per = 50;	// 50 rows per page

$total = $db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE);
$pages = ceil($total / $per);

$sorttypes = array(
	'_default'	=> 'uid',
	'uid'		=> array('u_id {DIR}', 'u_id'),
	'name'		=> array('u_name {DIR}', 'u_name'),
	'user'		=> array('u_username {DIR}', 'u_username'),
	'mail'		=> array('u_email {DIR}', 'u_email'),
	'ip'		=> array('u_lastip {DIR}', 'u_lastip'),
	'idle'		=> array('u_lastdate {DIR}', 'u_lastdate'),
	'flags'		=> array('u_flags {DIR}', 'u_flags'),
);
$sortby = parsesort($sortcol, $sortdir, $sorttypes);
$offset = parsepage($curpage, $total, $per);
$sortcomp = $sorttypes[$sortcol][1];

$_SESSION['admin_users_sortcol'] = $sortcol;
$_SESSION['admin_users_sortdir'] = $sortdir;
$_SESSION['admin_users_page'] = $curpage;

$sortlink = '?location=admin/users&amp;';
?>
<table>
<tr><th><?php echo sortlink(lang('COLUMN_ADMIN_USERID'), $sortlink, $sortcol, $sortdir, 'uid', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_NICKNAME'), $sortlink, $sortcol, $sortdir, 'name', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_USERNAME'), $sortlink, $sortcol, $sortdir, 'user', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_EMAIL'), $sortlink, $sortcol, $sortdir, 'mail', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_IPADDR'), $sortlink, $sortcol, $sortdir, 'ip', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_IDLE'), $sortlink, $sortcol, $sortdir, 'idle', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_FLAGS'), $sortlink, $sortcol, $sortdir, 'flags', 'asc', $curpage); ?></th>
    <th><?php echo lang('ADMIN_USERS_LABEL_EMPIRES'); ?></th></tr>
<?php
$lastsort = '';

$sql = 'SELECT u_id,u_name,u_username,u_email,u_lastip,u_lastdate,u_flags,(SELECT COUNT(*) FROM '. EMPIRE_TABLE .' e1 WHERE e1.u_id = u.u_id) AS u_emps,(SELECT COUNT(*) FROM '. EMPIRE_TABLE .' e2 WHERE e2.u_oldid = u.u_id) AS u_deademps,(SELECT COUNT(*) FROM '. HISTORY_EMPIRE_TABLE .' he WHERE he.u_id = u.u_id) AS u_histemps FROM '. USER_TABLE .' u ORDER BY '. $sortby;
$sql = $db->setLimit($sql, $per, $offset);
$q = $db->query($sql) or warning('Failed to fetch user list', 0);
$users = $q->fetchAll();
foreach ($users as $user)
{
	$idle = CUR_TIME - $user['u_lastdate'];
	if ($user['u_flags'] & UFLAG_CLOSED)
		echo '<tr class="cbad">'."\n";
	elseif ($user['u_flags'] & UFLAG_DISABLE)
		echo '<tr class="cbad">'."\n";
	elseif ($user[$sortcomp] == $lastsort)
		echo '<tr class="cwarn">'."\n";
	else	echo "<tr>\n";
?>
    <th class="ar"><a href="?location=admin/users&amp;action=edit&amp;user_id=<?php echo $user['u_id']; ?>"><?php echo $user['u_id']; ?></a></th>
    <td class="al"><?php echo $user['u_name']; ?></td>
    <td class="ar"><?php echo htmlspecialchars($user['u_username']); ?></td>
    <td class="ac"><?php echo $user['u_email']; ?></td>
    <td class="ac"><?php echo $user['u_lastip']; ?></td>
    <td class="ar"><?php echo floor($idle / 86400).gmdate(':H:i:s', $idle); ?></td>
    <td class="ac"><?php
	echo ($user['u_flags'] & UFLAG_ADMIN) ? 'A' : '-';
	echo ($user['u_flags'] & UFLAG_MOD) ? 'M' : '-';
	echo ($user['u_flags'] & UFLAG_DISABLE) ? 'D' : '-';
	echo ($user['u_flags'] & UFLAG_VALID) ? 'V' : '-';
	echo ($user['u_flags'] & UFLAG_CLOSED) ? 'C' : '-';
	echo ($user['u_flags'] & UFLAG_WATCH) ? 'W' : '-';
?></td>
    <td class="ac"><?php echo $user['u_emps']; ?> / <?php echo $user['u_deademps']; ?> / <?php echo $user['u_histemps']; ?></td></tr>
<?php
	$lastsort = $user[$sortcomp];
}
if ($pages > 0)
	echo '<tr><td colspan="7" class="ar">'. pagelist($curpage, $pages, $sortlink, $sortcol, $sortdir) .'</td></tr>';
?>
</table>
<hr />
<form method="post" action="?location=admin/users"><div>
<?php echo checkbox('confirm', lang('ADMIN_USERS_CREATE_CONFIRM')); ?><br />
<input type="hidden" name="action" value="add" /><input type="submit" value="<?php echo lang('ADMIN_USERS_CREATE_SUBMIT'); ?>" />
</div></form>
<?php
page_footer();
?>
