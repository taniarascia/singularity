<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: empires.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_EMPIRES_TITLE';

$needpriv = UFLAG_MOD;

if ($action == 'add')
	$lock['user2'] = fixInputNum(getFormVar('add_user'));
// Ideally, we should only lock the empires we're going to modify here
// but in the case of toggling Deleted, we need to alter the killer's
// empire too, and we don't know what it is yet
if ($action == 'modify')
	$db->lockGroup(ENT_EMPIRE);

page_header();

$linkfilter = getFormVar('linked', 1);

if ($action == 'add') do
{
	if (!isFormPost())
		break;
	if (!($user1->u_flags & UFLAG_ADMIN))
	{
		notice(lang('ADMIN_EMPIRES_ADD_NEED_PERMISSION'));
		break;
	}
	if ($lock['user2'] == 0)
	{
		notice(lang('ADMIN_EMPIRES_ADD_NEED_USER'));
		break;
	}
	$empname = getFormVar('add_name');
	if (!$empname)
	{
		notice(lang('ADMIN_EMPIRES_ADD_NEED_NAME'));
		break;
	}
	if (lang_isset($empname))
	{
		notice(lang('INPUT_EMPIRE_INVALID'));
		break;
	}
	$emp2 = new prom_empire();
	$emp2->create($user2, $empname, RACE_HUMAN);
	if ($user2->u_flags & (UFLAG_ADMIN | UFLAG_MOD))
		$emp2->setFlag(EFLAG_ADMIN);
	notice(lang('ADMIN_EMPIRES_ADD_COMPLETE'));

	if (VALIDATE_ALLOW)
	{
		$mailerror = $emp2->sendValidationMail($user2);
		if ($mailerror)
			notice(lang('ADMIN_EMPIRES_ADD_SENDMAIL_FAIL', $mailerror));
	}
	$emp2->save();
} while (0);

$modmulti = getFormVar('modify_multi', -1);
$moddisable = getFormVar('modify_disable', -1);
$modvalidate = getFormVar('modify_validate', -1);
$modsendmail = getFormVar('modify_sendmail');
$modsilence = getFormVar('modify_silence', -1);
$modlogged = getFormVar('modify_logged', -1);
$moddelete = getFormVar('modify_delete', -1);
$modsetreason = getFormVar('modify_setreason');
$modreason = htmlspecialchars(getFormVar('modify_reason'));
$modadmin = getFormVar('modify_admin', -1);
$modlink = getFormVar('modify_link', -1);
$moduser = getFormVar('modify_user', -1);

// keep track of stuff we failed to modify, so we can do it again
$checked = array();
if ($action == 'modify') do
{
	if (!isFormPost())
		break;
	$list = getFormArr('modify');

	if (count($list) == 0)
	{
		notice(lang('ADMIN_EMPIRES_NEED_SELECT'));
		break;
	}

	$emod = NULL;
	foreach ($list as $num)
	{
		if (isset($emod))
			$emod->save();

		if ($num == $emp1->e_id)
		{
			notice(lang('ADMIN_EMPIRES_MODIFY_SELF'));
			break;
		}

		$num = fixInputNum($num);
		$emod = new prom_empire($num);
		// Make sure the lock was acquired successfully
		if (!$emod->locked())
		{
			notice(lang('ADMIN_EMPIRES_MODIFY_LOCK_FAILED', prenum($num)));
			$checked[$num] = TRUE;
			$emod = NULL;
			continue;
		}
		$emod->load();

		if ($emod->e_land == 0)
		{
			notice(lang('ADMIN_EMPIRES_MODIFY_ALREADY_DEAD', $emod));
			break;
		}

		if ($modmulti != -1) do
		{
			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($modmulti == 0)
			{
				if (!($emod->e_flags & EFLAG_MULTI))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_MULTI_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_MULTI);
				notice(lang('ADMIN_EMPIRES_MODIFY_MULTI_CLEAR', $emod));
				logevent(varlist(array('num', 'modmulti'), get_defined_vars()));
			}
			if ($modmulti == 1)
			{
				if ($emod->e_flags & EFLAG_MULTI)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_MULTI_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_MULTI);
				notice(lang('ADMIN_EMPIRES_MODIFY_MULTI_SET', $emod));
				logevent(varlist(array('num', 'modmulti'), get_defined_vars()));
			}
		} while (0);

		if ($moddisable != -1) do
		{
			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($moddisable == 0)
			{
				if (!($emod->e_flags & EFLAG_DISABLE))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_DISABLE_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_DISABLE);
				$emod->clrFlag(EFLAG_NOTIFY);
				$emod->e_killedby = 0;
				$emod->e_idle = CUR_TIME;
				notice(lang('ADMIN_EMPIRES_MODIFY_DISABLE_CLEAR', $emod));
				logevent(varlist(array('num', 'moddisable'), get_defined_vars()));
			}
			if ($moddisable == 1)
			{
				if ($emod->e_flags & EFLAG_DISABLE)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_DISABLE_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_DISABLE);
				$emod->setFlag(EFLAG_NOTIFY);
				$emod->e_killedby = $emp1->e_id;
				notice(lang('ADMIN_EMPIRES_MODIFY_DISABLE_SET', $emod));
				logevent(varlist(array('num', 'moddisable'), get_defined_vars()));
			}
		} while (0);

		if ($modvalidate != -1) do
		{
			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($modvalidate == 0)
			{
				if (!($emod->e_flags & EFLAG_VALID))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_VALID);
				$emod->clrFlag(EFLAG_NOTIFY);
				$emod->e_valcode = md5(uniqid(mt_rand(), TRUE));
				notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_CLEAR', $emod));
				logevent(varlist(array('num', 'modvalidate'), get_defined_vars()));
			}
			if ($modvalidate == 1)
			{
				if ($emod->e_flags & EFLAG_VALID)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_VALID);
				$emod->clrFlag(EFLAG_NOTIFY);
				notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_SET', $emod));
				logevent(varlist(array('num', 'modvalidate'), get_defined_vars()));
			}
		} while (0);

		if ((VALIDATE_ALLOW) && ($modsendmail == 1)) do
		{
			if ($emod->u_id == 0)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_UNLINKED', $emod));
				break;
			}
			if ($emod->e_flags & EFLAG_VALID)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_VALIDATE_NO_SET', $emod));
				break;
			}
			$user_a = new prom_user($emod->u_id);
			$user_a->load();
			$mailerror = $emod->sendValidationMail($user_a);
			$user_a = NULL;
			if ($mailerror)
				notice(lang('ADMIN_EMPIRES_MODIFY_SENDMAIL_FAIL', $emod, $mailerror));
			else	notice(lang('ADMIN_EMPIRES_MODIFY_SENDMAIL_SUCCESS', $emod));
			logevent(varlist(array('num', 'modsendmail', 'mailerror'), get_defined_vars()));
		} while (0);

		if ($modsilence != -1) do
		{
			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($modsilence == 0)
			{
				if (!($emod->e_flags & EFLAG_SILENT))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_SILENT_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_SILENT);
				notice(lang('ADMIN_EMPIRES_MODIFY_SILENT_CLEAR', $emod));
				logevent(varlist(array('num', 'modsilence'), get_defined_vars()));
			}
			if ($modsilence == 1)
			{
				if ($emod->e_flags & EFLAG_SILENT)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_SILENT_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_SILENT);
				notice(lang('ADMIN_EMPIRES_MODIFY_SILENT_SET', $emod));
				logevent(varlist(array('num', 'modsilence'), get_defined_vars()));
			}
		} while (0);

		if ($modlogged != -1) do
		{
			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($modlogged == 0)
			{
				if (!($emod->e_flags & EFLAG_LOGGED))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_LOGGED_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_LOGGED);
				notice(lang('ADMIN_EMPIRES_MODIFY_LOGGED_CLEAR', $emod));
				logevent(varlist(array('num', 'modlogged'), get_defined_vars()));
			}
			if ($modlogged == 1)
			{
				if ($emod->e_flags & EFLAG_LOGGED)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_LOGGED_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_LOGGED);
				notice(lang('ADMIN_EMPIRES_MODIFY_LOGGED_SET', $emod));
				logevent(varlist(array('num', 'modlogged'), get_defined_vars()));
			}
		} while (0);

		if ($moddelete != -1) do
		{
			if (!($user1->u_flags & UFLAG_ADMIN))
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_DELETE_NEED_PERMISSION'));
				break;
			}

			if ($moddelete == 0)
			{
				if (!($emod->e_flags & EFLAG_DELETE))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_DELETE_NO_CLEAR', $emod));
					break;
				}
				// revoke any kill credit previously granted
				if ($emod->e_killedby != 0)
				{
					// if self-deleted, do nothing
					if ($emod->e_killedby == $emod->e_id)
						;
					elseif ($emod->e_killedby == $emp1->e_id)
						$emp1->e_kills--;
					else
					{
						$emod2 = new prom_empire($emod->e_killedby);
						if (!$emod2->locked())
						{
							notice(lang('ADMIN_EMPIRES_MODIFY_LOCK_FAILED', $emod->e_killedby));
							$checked[$num] = TRUE;
							break;
						}
						$emod2->load();
						$emod2->e_kills--;
						$emod2->save();
						$emod2 = NULL;
					}
					$emod->e_killedby = 0;
				}
				$emod->clrFlag(EFLAG_DELETE);
				$emod->clrFlag(EFLAG_NOTIFY);
				$emod->e_idle = CUR_TIME;
				notice(lang('ADMIN_EMPIRES_MODIFY_DELETE_CLEAR', $emod));
				logevent(varlist(array('num', 'moddelete'), get_defined_vars()));
			}
			if ($moddelete == 1)
			{
				if ($emod->e_flags & EFLAG_DELETE)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_DELETE_NO_SET', $emod));
					break;
				}
				// if the empire is disabled, set them as killed by the admin doing the delete
				// otherwise, treat it as a requested self-delete
				if ($emod->e_killedby == 0)
				{
					if ($emod->e_flags & EFLAG_DISABLE)
						$emod->e_killedby = $emp1->e_id;
					else	$emod->e_killedby = $emod->e_id;
				}
				// grant a kill credit (if necessary)
				if (($emod->e_killedby != 0) && ($emod->e_killedby != $emod->e_id))
				{
					if ($emod->e_killedby == $emp1->e_id)
						$emp1->e_kills++;
					else
					{
						$emod2 = new prom_empire($emod->e_killedby);
						if (!$emod2->locked())
						{
							notice(lang('ADMIN_EMPIRES_MODIFY_LOCK_FAILED', $emod->e_killedby));
							$checked[$num] = TRUE;
							break;
						}
						$emod2->load();
						$emod2->e_kills++;
						$emod2->save();
						$emod2 = NULL;
					}
				}
				$emod->setFlag(EFLAG_DELETE);
				notice(lang('ADMIN_EMPIRES_MODIFY_DELETE_SET', $emod));
				logevent(varlist(array('num', 'moddelete'), get_defined_vars()));
			}
		} while (0);

		if ($modsetreason == 1) do
		{
			// leave the remaining 55 characters for stuff added by the turns script
			if (strlen($modreason) > 200)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_REASON_TOO_LONG'));
				break;
			}
			$emod->e_reason = $modreason;
			notice(lang('ADMIN_EMPIRES_MODIFY_REASON_SET', $emod));
			logevent(varlist(array('num', 'modreason'), get_defined_vars()));
		} while (0);

		if ($modadmin != -1) do
		{
			if (!($user1->u_flags & UFLAG_ADMIN))
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_ADMIN_NEED_PERMISSION'));
				break;
			}

			if ($emod->e_flags & EFLAG_DELETE)
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_MUST_UNDELETE', $emod));
				break;
			}

			if ($modadmin == 0)
			{
				if (!($emod->e_flags & EFLAG_ADMIN))
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_ADMIN_NO_CLEAR', $emod));
					break;
				}
				$emod->clrFlag(EFLAG_ADMIN);
				notice(lang('ADMIN_EMPIRES_MODIFY_ADMIN_CLEAR', $emod));
				logevent(varlist(array('num', 'modadmin'), get_defined_vars()));
			}
			if ($modadmin == 1)
			{
				if ($emod->e_flags & EFLAG_ADMIN)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_ADMIN_NO_SET', $emod));
					break;
				}
				$emod->setFlag(EFLAG_ADMIN);
				notice(lang('ADMIN_EMPIRES_MODIFY_ADMIN_SET', $emod));
				logevent(varlist(array('num', 'modadmin'), get_defined_vars()));
			}
		} while (0);

		if ($modlink != -1) do
		{
			if (!($user1->u_flags & UFLAG_ADMIN))
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_LINK_NEED_PERMISSION'));
				break;
			}

			if ($modlink == 0)
			{
				if ($emod->u_id == 0)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_LINK_NO_CLEAR', $emod));
					break;
				}
				$emod->u_oldid = $emod->u_id;
				$emod->u_id = 0;
				notice(lang('ADMIN_EMPIRES_MODIFY_LINK_CLEAR', $emod));
				logevent(varlist(array('num', 'modlink'), get_defined_vars()));
			}
			if ($modlink == 1)
			{
				if ($emod->u_id != 0)
				{
					notice(lang('ADMIN_EMPIRES_MODIFY_LINK_NO_SET', $emod));
					break;
				}
				$emod->u_id = $emod->u_oldid;
				$emod->u_oldid = 0;
				// update idle time to prevent from being unlinked again
				$emod->e_idle = CUR_TIME;
				notice(lang('ADMIN_EMPIRES_MODIFY_LINK_SET', $emod));
				logevent(varlist(array('num', 'modlink'), get_defined_vars()));
			}
		} while (0);

		if ($moduser != -1) do
		{
			if (!($user1->u_flags & UFLAG_ADMIN))
			{
				notice(lang('ADMIN_EMPIRES_MODIFY_USER_NEED_PERMISSION'));
				break;
			}
			if ($emod->u_id != 0)
			{
				$id = $emod->u_id;
				$emod->u_id = $moduser;
				notice(lang('ADMIN_EMPIRES_MODIFY_USER_SET_REAL', $emod, prenum($moduser)));
				logevent(varlist(array('num', 'moduser', 'id'), get_defined_vars()));
			}
			if ($emod->u_oldid != 0)
			{
				$oldid = $emod->u_oldid;
				$emod->u_oldid = $moduser;
				notice(lang('ADMIN_EMPIRES_MODIFY_USER_SET_OLD', $emod, prenum($moduser)));
				logevent(varlist(array('num', 'moduser', 'oldid'), get_defined_vars()));
			}
		} while (0);
	}
	if (isset($emod))
		$emod->save();
	$emod = NULL;

	// if all checked empires were processed successfully, clear out commands
	if (count($checked) == 0)
	{
		$modmulti = -1;
		$moddisable = -1;
		$modvalidate = -1;
		$modsendmail = 0;
		$modsilence = -1;
		$modlogged = -1;
		$moddelete = -1;
		$modsetreason = 0;
		$modreason = '';
		$modadmin = -1;
		$modlink = -1;
		$moduser = -1;
	}
} while (0);
notices();

prom_session::initvar('admin_empires_sortcol', 'uid');
prom_session::initvar('admin_empires_sortdir', 'asc');
prom_session::initvar('admin_empires_page', 1);

$sortcol = getFormVar('sortcol', $_SESSION['admin_empires_sortcol']);
$sortdir = getFormVar('sortdir', $_SESSION['admin_empires_sortdir']);
$curpage = fixInputNum(getFormVar('page', $_SESSION['admin_empires_page']));
$per = 50;	// 50 rows per page

if ($linkfilter)
	$total = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id != 0');
else	$total = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = 0');
$pages = ceil($total / $per);

$sorttypes = array(
	'_default'	=> 'uid',
	'uid'		=> array('u.u_id {DIR}', 'u_id'),
	'user'		=> array('u_username {DIR}', 'u_username'),
	'ip'		=> array('u_lastip {DIR}', 'u_lastip'),
	'eid'		=> array('e_id {DIR}', 'e_id'),
	'name'		=> array('e_name {DIR}', 'e_name'),
	'idle'		=> array('e_idle {DIR}', 'e_idle'),
	'uflags'	=> array('u_flags {DIR}', 'u_flags'),
	'eflags'	=> array('e_flags {DIR}', 'e_flags'),
	'comment'	=> array('e_reason {DIR}', 'e_reason'),
);
if (CLAN_ENABLE)
	$sorttypes['clan'] = array('c_id {DIR}', 'c_id');
$sortby = parsesort($sortcol, $sortdir, $sorttypes);
$offset = parsepage($curpage, $total, $per);
$sortcomp = $sorttypes[$sortcol][1];

$_SESSION['admin_empires_sortcol'] = $sortcol;
$_SESSION['admin_empires_sortdir'] = $sortdir;
$_SESSION['admin_empires_page'] = $curpage;

$sortlink = '?location=admin/empires&amp;linked='. $linkfilter .'&amp;';
?>
<h3><a href="?location=admin/empires&amp;linked=1"><?php echo lang('ADMIN_EMPIRES_FILTER_LINKED'); ?></a> - <a href="?location=admin/empires&amp;linked=0"><?php echo lang('ADMIN_EMPIRES_FILTER_UNLINKED'); ?></a></h3>
<form method="post" action="?location=admin/empires&amp;linked=<?php echo $linkfilter; ?>">
<table>
<tr><th><?php echo sortlink(lang('COLUMN_ADMIN_USERID'), $sortlink, $sortcol, $sortdir, 'uid', 'asc', $curpage); ?></th>
    <th class="al"><?php echo sortlink(lang('COLUMN_ADMIN_USERNAME'), $sortlink, $sortcol, $sortdir, 'user', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_IPADDR'), $sortlink, $sortcol, $sortdir, 'ip', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_EMPIREID'), $sortlink, $sortcol, $sortdir, 'eid', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_EMPNAME'), $sortlink, $sortcol, $sortdir, 'name', 'asc', $curpage); ?></th>
<?php if (CLAN_ENABLE) { ?>
    <th><?php echo sortlink(lang('COLUMN_CLAN'), $sortlink, $sortcol, $sortdir, 'clan', 'asc', $curpage); ?></th>
<?php } ?>
    <th class="ar"><?php echo sortlink(lang('COLUMN_ADMIN_IDLE'), $sortlink, $sortcol, $sortdir, 'idle', 'desc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_EFLAGS'), $sortlink, $sortcol, $sortdir, 'eflags', 'asc', $curpage); ?></th>
    <th><?php echo sortlink(lang('COLUMN_ADMIN_UFLAGS'), $sortlink, $sortcol, $sortdir, 'uflags', 'asc', $curpage); ?></th>
    <th><?php echo lang('ADMIN_EMPIRES_COLUMN_MODIFY'); ?></th></tr>
<tr><th colspan="3"></th>
    <th colspan="<?php if (CLAN_ENABLE) echo '4'; else echo '3'; ?>" class="al"><?php echo sortlink(lang('COLUMN_ADMIN_COMMENT'), $sortlink, $sortcol, $sortdir, 'comment', 'asc', $curpage); ?></th>
    <th colspan="2"><?php echo lang('ADMIN_EMPIRES_COLUMN_STATUS'); ?></th>
    <th></th></tr>
<?php
$lastsort = '';

if ($linkfilter)
{
	$sql = 'SELECT u.u_id,u_username,u_lastip,u_flags,e_id,e_name,c_id,e_idle,e_vacation,e_flags,e_turnsused,e_land,e_killedby,e_reason FROM '. EMPIRE_TABLE .' e LEFT OUTER JOIN '. USER_TABLE .' u ON (e.u_id    = u.u_id) WHERE e.u_id != 0 ORDER BY '. $sortby;
	$sql = $db->setLimit($sql, $per, $offset);
	$q = $db->query($sql) or warning('Failed to fetch linked empire list', 0);
}
else
{
	$sql = 'SELECT u.u_id,u_username,u_lastip,u_flags,e_id,e_name,c_id,e_idle,e_vacation,e_flags,e_turnsused,e_land,e_killedby,e_reason FROM '. EMPIRE_TABLE .' e LEFT OUTER JOIN '. USER_TABLE .' u ON (e.u_oldid = u.u_id) WHERE e.u_id  = 0 ORDER BY '. $sortby;
	$sql = $db->setLimit($sql, $per, $offset);
	$q = $db->query($sql) or warning('Failed to fetch unlinked empire list', 0);
}
$emps = $q->fetchAll();
foreach ($emps as $emp)
{
	$idle = CUR_TIME - $emp['e_idle'];
	if ($emp['e_flags'] & EFLAG_DISABLE)
		echo '<tr class="cbad">'."\n";
	elseif ($emp['e_flags'] & EFLAG_MULTI)
		echo '<tr class="cgood">'."\n";
	elseif (($emp[$sortcomp] == $lastsort) || ($emp['u_flags'] & UFLAG_WATCH))
		echo '<tr class="cwarn">'."\n";
	else	echo "<tr>\n";
?>
    <th class="ar"><a href="?location=admin/users&amp;action=edit&amp;user_id=<?php echo $emp['u_id']; ?>"><?php echo $emp['u_id']; ?></a></th>
    <td><?php echo htmlspecialchars($emp['u_username']); ?></td>
    <td class="ac"><?php echo $emp['u_lastip']; ?></td>
    <td class="ac"><?php echo $emp['e_id']; ?></td>
    <td class="ac"><?php echo $emp['e_name']; ?></td>
<?php	if (CLAN_ENABLE) { ?>
    <td class="ac"><?php echo $cnames[$emp['c_id']]; ?></td>
<?php	} ?>
    <td class="ar"><?php echo floor($idle / 86400).gmdate(':H:i:s', $idle); ?></td>
    <td class="ac"><?php
	echo ($emp['e_flags'] & EFLAG_ADMIN) ? 'A' : '-';
	echo ($emp['e_flags'] & EFLAG_DELETE) ? 'D' : '-';
	echo ($emp['e_flags'] & EFLAG_DISABLE) ? 'I' : '-';
	echo ($emp['e_flags'] & EFLAG_MULTI) ? 'U' : '-';
	echo ($emp['e_flags'] & EFLAG_VALID) ? 'V' : '-';
	echo ($emp['e_flags'] & EFLAG_NOTIFY) ? 'N' : '-';
	echo ($emp['e_flags'] & EFLAG_ONLINE) ? 'O' : '-';
	echo ($emp['e_flags'] & EFLAG_SILENT) ? 'S' : '-';
	echo ($emp['e_flags'] & EFLAG_LOGGED) ? 'G' : '-';
?></td>
    <td class="ac"><?php
        echo ($emp['u_flags'] & UFLAG_ADMIN) ? 'A' : '-';
        echo ($emp['u_flags'] & UFLAG_MOD) ? 'M' : '-';
        echo ($emp['u_flags'] & UFLAG_DISABLE) ? 'D' : '-';
        echo ($emp['u_flags'] & UFLAG_VALID) ? 'V' : '-';
        echo ($emp['u_flags'] & UFLAG_CLOSED) ? 'C' : '-';
        echo ($emp['u_flags'] & UFLAG_WATCH) ? 'W' : '-';
?></td>
    <td class="ac"><?php
	if (($emp['e_id'] != $emp1->e_id) && ($emp['e_land'] != 0))
		echo checkbox('modify[]', '', $emp['e_id'], isset($checked[$emp['e_id']]));
?></td></tr>
<tr><td colspan="3"></td>
    <td colspan="<?php if (CLAN_ENABLE) echo '4'; else echo '3'; ?>"><?php echo $emp['e_reason']; ?></td>
    <td class="ac"><?php
	if ($emp['e_flags'] & EFLAG_DELETE)
		echo lang('ADMIN_STATUS_DELETED');
	elseif ($emp['e_flags'] & EFLAG_ADMIN)
		echo lang('ADMIN_STATUS_ADMIN');
	elseif ($emp['e_flags'] & EFLAG_DISABLE)
	{
		if ($emp['e_flags'] & EFLAG_MULTI)
			echo lang('ADMIN_STATUS_DISABLED_MULTI', $emp['e_killedby']);
		else	echo lang('ADMIN_STATUS_DISABLED_OTHER', $emp['e_killedby']);
	}
	elseif ($emp['e_flags'] & EFLAG_NOTIFY)
	{
		if (!($emp['e_flags'] & EFLAG_VALID))
			echo lang('ADMIN_STATUS_UNVALIDATED_NOTIFY');
		elseif ($emp['e_land'] == 0)
			echo lang('ADMIN_STATUS_DEAD_NOTIFY');
		elseif ($emp['e_vacation'] > 0)
			echo lang('ADMIN_STATUS_VACATION');
		else	echo lang('ADMIN_STATUS_UNKNOWN_NOTIFY');
	}
	else
	{
		if ($emp['e_land'] == 0)
			echo lang('ADMIN_STATUS_DEAD');
		elseif ($emp['e_flags'] & EFLAG_MULTI)
			echo lang('ADMIN_STATUS_MULTI');
		elseif ($emp['e_flags'] & EFLAG_VALID)
			echo lang('ADMIN_STATUS_NORMAL');
		elseif ($emp['e_turnsused'] > TURNS_VALIDATE)
			echo lang('ADMIN_STATUS_UNVALIDATED');
		else	echo lang('ADMIN_STATUS_NEW');
	}
?></td><td></td></tr>
<?php
	$lastsort = $emp[$sortcomp];
}
if ($pages > 0)
	echo '<tr><td colspan="'. (CLAN_ENABLE ? '9' : '8') .'" class="ar">'. pagelist($curpage, $pages, $sortlink, $sortcol, $sortdir) .'</td></tr>';

$q = $db->query('SELECT u_id,u_name FROM '. USER_TABLE);
$users = $q->fetchall();
?>
<tr><td colspan="<?php if (CLAN_ENABLE) echo '9'; else echo '8'; ?>" class="ar">
        <input type="hidden" name="action" value="modify" />
        <?php echo lang('ADMIN_EMPIRES_LABEL_MULTI'); ?> <?php echo radiolist('modify_multi', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modmulti); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_DISABLED'); ?> <?php echo radiolist('modify_disable', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $moddisable); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_VALIDATED'); ?> <?php echo radiolist('modify_validate', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modvalidate); ?><br />
        <?php if (VALIDATE_ALLOW) echo checkbox('modify_sendmail', lang('ADMIN_EMPIRES_LABEL_SENDMAIL'), 1, $modsendmail).'<br />'; ?>
        <?php echo lang('ADMIN_EMPIRES_LABEL_SILENCED'); ?> <?php echo radiolist('modify_silence', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modsilence); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_LOGGED'); ?> <?php echo radiolist('modify_logged', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modlogged); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_DELETED'); ?> <?php echo radiolist('modify_delete', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $moddelete, $user1->u_flags & UFLAG_ADMIN); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_REASON'); ?> <?php echo checkbox('modify_setreason', lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 1, $modsetreason); ?> <input type="text" name="modify_reason" value="<?php echo $modreason; ?>" /><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_ADMIN'); ?> <?php echo radiolist('modify_admin', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modadmin, $user1->u_flags & UFLAG_ADMIN); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_LINKAGE'); ?> <?php echo radiolist('modify_link', array(1 => lang('ADMIN_EMPIRES_LABEL_OPTION_SET'), 0 => lang('ADMIN_EMPIRES_LABEL_OPTION_CLEAR'), -1 => lang('ADMIN_EMPIRES_LABEL_OPTION_IGNORE')), $modlink, $user1->u_flags & UFLAG_ADMIN); ?><br />
        <?php echo lang('ADMIN_EMPIRES_LABEL_SETOWNER'); ?> <?php
$ownerlist = array();
$ownerlist[-1] = lang('ADMIN_EMPIRES_LABEL_SELECTACCOUNT');
foreach ($users as $user)
	$ownerlist[$user['u_id']] = lang('COMMON_USER_NAMEID', htmlspecialchars($user['u_name']), prenum($user['u_id']));
echo optionlist('modify_user', $ownerlist, $moduser, $user1->u_flags & UFLAG_ADMIN);
?><br />
        <input type="submit" value="<?php echo lang('ADMIN_EMPIRES_MODIFY_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
if ($user1->u_flags & UFLAG_ADMIN)
{
?>
<hr />
<form method="post" action="?location=admin/empires">
<table>
<tr><th><?php echo lang('ADMIN_EMPIRES_LABEL_OWNER'); ?></th><td><?php
$userlist = array();
$userlist[-1] = lang('ADMIN_EMPIRES_LABEL_SELECTACCOUNT');
foreach ($users as $user)
	$userlist[$user['u_id']] = lang('COMMON_USER_NAMEID', htmlspecialchars($user['u_name']), prenum($user['u_id']));
echo optionlist('add_user', $userlist);
?></td></tr>
<tr><th><?php echo lang('ADMIN_EMPIRES_LABEL_NAME'); ?></th><td><input type="text" name="add_name" value="" /></td></tr>
<tr><th colspan="2"><input type="hidden" name="action" value="add" /><input type="submit" value="<?php echo lang('ADMIN_EMPIRES_MODIFY_CREATE'); ?>" /></th></tr>
</table>
</form>
<?php
}
page_footer();
?>
