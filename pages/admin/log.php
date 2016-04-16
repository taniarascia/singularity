<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: log.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_LOG_TITLE';

$needpriv = UFLAG_MOD;

page_header();

// For compatibility with versions of PHP prior to 5.2, the constants E_RECOVERABLE_ERROR and E_DEPRECATED are not used below
// Since E_ERROR cannot be handled using the log handler, it is reused for empires flaged with EFLAG_LOGGED
$loglevels = array(
	0 => lang('ADMIN_LOG_LEVEL_EVENT'),
	E_ERROR => lang('ADMIN_LOG_LEVEL_LOGEVENT'),
	E_WARNING => lang('ADMIN_LOG_LEVEL_WARNING'),
	E_NOTICE => lang('ADMIN_LOG_LEVEL_NOTICE'),
	-1 => '',
	E_USER_ERROR => lang('ADMIN_LOG_LEVEL_CERROR'),
	E_USER_WARNING => lang('ADMIN_LOG_LEVEL_CWARNING'),
	E_USER_NOTICE => lang('ADMIN_LOG_LEVEL_CNOTICE'),
	-2 => '',
	E_STRICT => lang('ADMIN_LOG_LEVEL_STRICT'),
	4096 => lang('ADMIN_LOG_LEVEL_RECOVER'),
	8192 => lang('ADMIN_LOG_LEVEL_DEPRECATED'),
);

prom_session::initvar('admin_log_filter_before', 0);
prom_session::initvar('admin_log_filter_after', 0);
prom_session::initvar('admin_log_filter_levels', array());
prom_session::initvar('admin_log_filter_ip', '');
prom_session::initvar('admin_log_filter_page', '');
prom_session::initvar('admin_log_filter_action', '');
prom_session::initvar('admin_log_filter_locks', '');
prom_session::initvar('admin_log_filter_user', '');
prom_session::initvar('admin_log_filter_emp', '');
prom_session::initvar('admin_log_filter_clan', '');
prom_session::initvar('admin_log_column_date', 1);
prom_session::initvar('admin_log_column_type', 1);
prom_session::initvar('admin_log_column_ip', 1);
prom_session::initvar('admin_log_column_page', 1);
prom_session::initvar('admin_log_column_action', 1);
prom_session::initvar('admin_log_column_locks', 1);
prom_session::initvar('admin_log_column_user', 1);
prom_session::initvar('admin_log_column_emp', 1);
prom_session::initvar('admin_log_column_clan', 1);

if ($action == 'filter') do
{
	if (!isFormPost())
		break;
	$before = strtotime(getFormVar('log_filter_before'));
	if ($before == FALSE)
		$_SESSION['admin_log_filter_before'] = 0;
	else	$_SESSION['admin_log_filter_before'] = $before;

	$after = strtotime(getFormVar('log_filter_after'));
	if ($after == FALSE)
		$_SESSION['admin_log_filter_after'] = 0;
	else	$_SESSION['admin_log_filter_after'] = $after;

	$levels = getFormArr('log_filter_levels');
	$_SESSION['admin_log_filter_levels'] = array_intersect($levels, array_keys($loglevels));
	$_SESSION['admin_log_filter_ip'] = getFormVar('log_filter_ip');
	$_SESSION['admin_log_filter_page'] = getFormVar('log_filter_page');
	$_SESSION['admin_log_filter_action'] = getFormVar('log_filter_action');
	$_SESSION['admin_log_filter_locks'] = getFormVar('log_filter_locks');
	$_SESSION['admin_log_filter_user'] = getFormVar('log_filter_user');
	$_SESSION['admin_log_filter_emp'] = getFormVar('log_filter_emp');
	$_SESSION['admin_log_filter_clan'] = getFormVar('log_filter_clan');

	$_SESSION['admin_log_column_date'] = fixInputBool(getFormVar('log_column_date'));
	$_SESSION['admin_log_column_type'] = fixInputBool(getFormVar('log_column_type'));
	$_SESSION['admin_log_column_ip'] = fixInputBool(getFormVar('log_column_ip'));
	$_SESSION['admin_log_column_page'] = fixInputBool(getFormVar('log_column_page'));
	$_SESSION['admin_log_column_action'] = fixInputBool(getFormVar('log_column_action'));
	$_SESSION['admin_log_column_locks'] = fixInputBool(getFormVar('log_column_locks'));
	$_SESSION['admin_log_column_user'] = fixInputBool(getFormVar('log_column_user'));
	$_SESSION['admin_log_column_emp'] = fixInputBool(getFormVar('log_column_emp'));
	$_SESSION['admin_log_column_clan'] = fixInputBool(getFormVar('log_column_clan'));
} while (0);

if ($action == 'delete') do
{
	if (!isFormPost())
		break;

	if (!($user1->u_flags & UFLAG_ADMIN))
	{
		notice(lang('ADMIN_LOG_DELETE_NEED_PERMISSION'));
		break;
	}

	$confirm = fixInputBool(getFormVar('delete_confirm'));
	if (!$confirm)
	{
		notice(lang('ADMIN_LOG_DELETE_NEED_CONFIRM'));
		break;
	}

	$id = fixInputNum(getFormVar('log_last'));
	$q = $db->prepare('DELETE FROM '. LOG_TABLE .' WHERE log_id <= ?');
	$q->bindIntValue(1, $id);
	if ($q->execute())
		notice(lang('ADMIN_LOG_DELETE_SUCCESS'));
	else	notice(lang('ADMIN_LOG_DELETE_FAIL'));
	logevent();
} while (0);
notices();

$checks = array();
$values = array();

if ($_SESSION['admin_log_filter_before'] > 0)
{
	$checks[] = 'log_time <= ?';
	$values[] = $_SESSION['admin_log_filter_before'];
}
if ($_SESSION['admin_log_filter_after'] > 0)
{
	$checks[] = 'log_time >= ?';
	$values[] = $_SESSION['admin_log_filter_after'];
}
if (count($_SESSION['admin_log_filter_levels']) > 0)
{
	$filter = $_SESSION['admin_log_filter_levels'];
	$checks[] = 'log_type IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
// Known issue - with PostgreSQL, trying to filter by something that isn't an IP address will generate query warnings
if ($_SESSION['admin_log_filter_ip'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_ip']);
	$checks[] = 'log_ip IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
if ($_SESSION['admin_log_filter_page'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_page']);
	$checks[] = 'log_page IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
if ($_SESSION['admin_log_filter_action'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_action']);
	$checks[] = 'log_action IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
// TODO - allow complex syntax for more detailed filtering (e.g. "e1,e2 e3,e4" for actions involving empires 1 and 2 OR empires 3 and 4)
if ($_SESSION['admin_log_filter_locks'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_locks']);
	$checks[] = 'log_locks IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
if ($_SESSION['admin_log_filter_user'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_user']);
	$checks[] = 'u_id IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
if ($_SESSION['admin_log_filter_emp'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_emp']);
	$checks[] = 'e_id IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}
if ($_SESSION['admin_log_filter_clan'] != '')
{
	$filter = explode(' ', $_SESSION['admin_log_filter_clan']);
	$checks[] = 'c_id IN '. sqlArgList($filter);
	foreach ($filter as $val)
		$values[] = $val;
}

if (count($checks) > 0)
	$where = ' WHERE '. implode(' AND ', $checks);
else	$where = '';

prom_session::initvar('admin_log_sortcol', 'time');
prom_session::initvar('admin_log_sortdir', 'asc');
prom_session::initvar('admin_log_page', 1);

$sortcol = getFormVar('sortcol', $_SESSION['admin_log_sortcol']);
$sortdir = getFormVar('sortdir', $_SESSION['admin_log_sortdir']);
$curpage = fixInputNum(getFormVar('page', $_SESSION['admin_log_page']));
$per = 100;	// 100 rows per page

$last = $db->queryCell('SELECT MAX(log_id) FROM '. LOG_TABLE);
$total = $db->queryCell('SELECT COUNT(*) FROM '. LOG_TABLE . $where, $values);
$pages = ceil($total / $per);

$sorttypes = array(
	'_default'	=> 'time',
	'time'		=> array('log_time {DIR}, log_id ASC', 'log_time'),
	'type'		=> array('log_type {DIR}, log_id ASC', 'log_type'),
	'ip'		=> array('log_ip {DIR}, log_id ASC', 'log_ip'),
	'page'		=> array('log_page {DIR}, log_id ASC', 'log_page'),
	'action'	=> array('log_action {DIR}, log_id ASC', 'log_action'),
	'locks'		=> array('log_locks {DIR}, log_id ASC', 'log_locks'),
	'user'		=> array('u_id {DIR}, log_id ASC', 'u_id'),
	'emp'		=> array('e_id {DIR}, log_id ASC', 'e_id'),
	'clan'		=> array('c_id {DIR}, log_id ASC', 'c_id'),
);
$sortby = parsesort($sortcol, $sortdir, $sorttypes);
$offset = parsepage($curpage, $total, $per);

$_SESSION['admin_log_sortcol'] = $sortcol;
$_SESSION['admin_log_sortdir'] = $sortdir;
$_SESSION['admin_log_page'] = $curpage;

$sortlink = '?location=admin/log&amp;';
?>
<form method="post" action="?location=admin/log">
<table class="inputtable">
<tr><th><?php echo checkbox('log_column_date', lang('ADMIN_LOG_COLUMN_DATE'), 1, $_SESSION['admin_log_column_date']); ?></th>
    <th><?php echo checkbox('log_column_type', lang('ADMIN_LOG_COLUMN_TYPE'), 1, $_SESSION['admin_log_column_type']); ?></th>
    <th><?php echo checkbox('log_column_ip', lang('ADMIN_LOG_COLUMN_IPADDR'), 1, $_SESSION['admin_log_column_ip']); ?></th>
    <th><?php echo checkbox('log_column_page', lang('ADMIN_LOG_COLUMN_PAGE'), 1, $_SESSION['admin_log_column_page']); ?></th>
    <th><?php echo checkbox('log_column_action', lang('ADMIN_LOG_COLUMN_ACTION'), 1, $_SESSION['admin_log_column_action']); ?></th>
    <th><?php echo checkbox('log_column_locks', lang('ADMIN_LOG_COLUMN_LOCKS'), 1, $_SESSION['admin_log_column_locks']); ?></th>
    <th><?php echo checkbox('log_column_user', lang('ADMIN_LOG_COLUMN_USER'), 1, $_SESSION['admin_log_column_user']); ?></th>
    <th><?php echo checkbox('log_column_emp', lang('ADMIN_LOG_COLUMN_EMP'), 1, $_SESSION['admin_log_column_emp']); ?></th>
<?php	if (CLAN_ENABLE) { ?>
    <th><?php echo checkbox('log_column_clan', lang('ADMIN_LOG_COLUMN_CLAN'), 1, $_SESSION['admin_log_column_clan']); ?></th>
<?php	} ?>
    <th></th></tr>
<tr><td><?php echo lang('ADMIN_LOG_FILTER_BEFORE'); ?> <input type="text" name="log_filter_before" value="<?php if ($_SESSION['admin_log_filter_before'] > 0) echo gmdate('Y/m/d H:i:s O', $_SESSION['admin_log_filter_before']); ?>" size="24" /><br />
        <?php echo lang('ADMIN_LOG_FILTER_AFTER'); ?> <input type="text" name="log_filter_after" value="<?php if ($_SESSION['admin_log_filter_after'] > 0) echo gmdate('Y/m/d H:i:s O', $_SESSION['admin_log_filter_after']); ?>" size="24"  /></td>
    <td><?php
$i = 0;
foreach ($loglevels as $level => $desc)
{
	if ($level < 0)
		echo '<br />';
	else	echo checkbox('log_filter_levels[]', $desc, $level, in_array($level, $_SESSION['admin_log_filter_levels'])) .' ';
}
?></td>
    <td><input type="text" name="log_filter_ip" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_ip']); ?>" size="15"  /></td>
    <td><input type="text" name="log_filter_page" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_page']); ?>" size="8"  /></td>
    <td><input type="text" name="log_filter_action" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_action']); ?>" size="8"  /></td>
    <td><input type="text" name="log_filter_locks" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_locks']); ?>" size="12"  /></td>
    <td><input type="text" name="log_filter_user" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_user']); ?>" size="4"  /></td>
    <td><input type="text" name="log_filter_emp" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_emp']); ?>" size="4"  /></td>
<?php	if (CLAN_ENABLE) { ?>
    <td><input type="text" name="log_filter_clan" value="<?php echo htmlspecialchars($_SESSION['admin_log_filter_clan']); ?>" size="4"  /></td>
<?php	} ?>
    <td><input type="hidden" name="action" value="filter" /><input type="submit" value="<?php echo lang('ADMIN_LOG_FILTER_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<hr />
<table class="inputtable">
<tr>
<?php	if ($_SESSION['admin_log_column_date']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_DATE'), $sortlink, $sortcol, $sortdir, 'time', 'desc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_type']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_TYPE'), $sortlink, $sortcol, $sortdir, 'type', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_ip']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_IPADDR'), $sortlink, $sortcol, $sortdir, 'ip', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_page']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_PAGE'), $sortlink, $sortcol, $sortdir, 'page', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_action']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_ACTION'), $sortlink, $sortcol, $sortdir, 'action', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_locks']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_LOCKS'), $sortlink, $sortcol, $sortdir, 'locks', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_user']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_USER'), $sortlink, $sortcol, $sortdir, 'user', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_emp']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_EMP'), $sortlink, $sortcol, $sortdir, 'emp', 'asc', $curpage); ?></th>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_clan']) { ?>
    <th><?php echo sortlink(lang('ADMIN_LOG_COLUMN_CLAN'), $sortlink, $sortcol, $sortdir, 'clan', 'asc', $curpage); ?></th>
<?php	} ?>
    <th><?php echo lang('ADMIN_LOG_COLUMN_DATA'); ?></th></tr>
<?php
$sql = 'SELECT * FROM '. LOG_TABLE . $where .' ORDER BY '. $sortby;
$sql = $db->setLimit($sql, $per, $offset);
$q = $db->prepare($sql);
$q->bindAllValues($values);
$q->execute() or warning('Failed to fetch log entries', 0);
$logs = $q->fetchAll();
foreach ($logs as $log)
{
?>
<tr>
<?php	if ($_SESSION['admin_log_column_date']) { ?>
    <td><?php echo gmdate('Y/m/d H:i:s O', $log['log_time']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_type']) { ?>
    <td><?php if (isset($loglevels[$log['log_type']])) echo $loglevels[$log['log_type']]; else echo lang('ADMIN_LOG_LEVEL_UNKNOWN'); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_ip']) { ?>
    <td><?php echo $log['log_ip']; ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_page']) { ?>
    <td><?php echo htmlspecialchars($log['log_page']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_action']) { ?>
    <td><?php echo htmlspecialchars($log['log_action']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_locks']) { ?>
    <td><?php echo htmlspecialchars($log['log_locks']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_user']) { ?>
    <td><?php echo htmlspecialchars($log['u_id']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_emp']) { ?>
    <td><?php echo htmlspecialchars($log['e_id']); ?></td>
<?php	} ?>
<?php	if ($_SESSION['admin_log_column_clan']) { ?>
    <td><?php echo htmlspecialchars($log['c_id']); ?></td>
<?php	} ?>
    <td><?php echo htmlspecialchars($log['log_text']); ?></td></tr>
<?php
}
if ($pages > 0)
{
?>
<tr><td colspan="7" class="ar"><?php echo pagelist($curpage, $pages, $sortlink, $sortcol, $sortdir); ?><br />
<?php
	if ($user1->u_flags & UFLAG_ADMIN)
	{
?>
<form method="post" action="?location=admin/log">
<div>
<input type="hidden" name="action" value="delete" />
<input type="hidden" name="log_last" value="<?php echo $last; ?>" />
<?php echo checkbox('delete_confirm', lang('ADMIN_LOG_DELETE_CONFIRM')); ?> <input type="submit" value="<?php echo lang('ADMIN_LOG_DELETE_SUBMIT'); ?>" />
</div>
</form>
<?php
	}
?>
</td></tr>
<?php
}
else	echo '<tr><td colspan="7" class="ac">'. lang('ADMIN_LOG_NO_DATA') .'</td></tr>';
?>
</table>
<?php
page_footer();
?>
