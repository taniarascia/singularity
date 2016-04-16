<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clans.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'ADMIN_CLANS_TITLE';

$needpriv = UFLAG_ADMIN;

if (!CLAN_ENABLE)
	unavailable(lang('CLAN_UNAVAILABLE_CONFIG'));

if (($action == 'leader') || ($action == 'rename'))
	$lock['clan1'] = fixInputNum(getFormVar('admin_clan1'));

page_header();

if ($action == 'leader') do
{
	if (!isFormPost())
		break;
	if ($lock['clan1'] == 0)
	{
		notice(lang('ADMIN_CLANS_CLAN_INVALID'));
		break;
	}
	if ($clan1->c_members == 0)
	{
		notice(lang('ADMIN_CLANS_CLAN_EMPTY'));
		break;
	}
	$leader = fixInputNum(getFormVar('admin_leader'));
	$emp_a = new prom_empire($leader);
	if (!$emp_a->loadPartial())
	{
		$emp_a = NULL;
		notice(lang('INPUT_EMPIRE_ID'));
		break;
	}
	if ($emp_a->c_id != $clan1->c_id)
	{
		$emp_a = NULL;
		notice(lang('ADMIN_CLANS_LEADER_NOT_MEMBER'));
		break;
	}
	if ($emp_a->e_id == $clan1->e_id_leader)
	{
		$emp_a = NULL;
		notice(lang('ADMIN_CLANS_LEADER_ALREADY'));
		break;
	}
	if ($emp_a->e_id == $clan1->e_id_asst)
	{
		$clan1->e_id_asst = 0;
		addEmpireNews(EMPNEWS_CLAN_REVOKE_ASSISTANT, $emp1, $emp_a, 0);
	}
	if ($emp_a->e_id == $clan1->e_id_fa1)
	{
		$clan1->e_id_fa1 = 0;
		addEmpireNews(EMPNEWS_CLAN_REVOKE_MINISTER, $emp1, $emp_a, 0);
	}
	if ($emp_a->e_id == $clan1->e_id_fa2)
	{
		$clan1->e_id_fa2 = 0;
		addEmpireNews(EMPNEWS_CLAN_REVOKE_MINISTER, $emp1, $emp_a, 0);
	}

	$emp_b = new prom_empire($clan1->e_id_leader);
	if ($emp_b->loadPartial())
		addEmpireNews(EMPNEWS_CLAN_REVOKE_LEADER, $emp1, $emp_b, 0);
	$emp_b = NULL;

	$clan1->e_id_leader = $leader;
	addEmpireNews(EMPNEWS_CLAN_GRANT_LEADER, $emp1, $emp_a, 0);
	// save to database so the lists below will update properly
	$clan1->save();
	notice(lang('ADMIN_CLANS_LEADER_COMPLETE', $clan1->c_name, $emp_a));
	logevent(varlist(array('leader'), get_defined_vars()));
	$emp_a = NULL;
} while (0);
if ($action == 'rename') do
{
	if (!isFormPost())
		break;
	if ($lock['clan1'] == 0)
	{
		notice(lang('ADMIN_CLANS_CLAN_INVALID'));
		break;
	}
	if ($clan1->c_members == 0)
	{
		notice(lang('ADMIN_CLANS_CLAN_EMPTY'));
		break;
	}
	$cname = getFormVar('clan_name');
	if (empty($cname))
	{
		notice(lang('ADMIN_CLANS_RENAME_NEED_NAME'));
		break;
	}
	if ($cname == $clan1->c_name)
	{
		notice(lang('ADMIN_CLANS_RENAME_NAME_UNCHANGED'));
		break;
	}
	if (lang_equals_any($cname, 'CLAN_NONE'))
	{
		notice(lang('ADMIN_CLANS_RENAME_NAME_INVALID'));
		break;
	}
	if (lang_isset($cname))
	{
		notice(lang('ADMIN_CLANS_RENAME_NAME_INVALID'));
		break;
	}
	if (strlen($cname) > 8)
	{
		notice(lang('ADMIN_CLANS_RENAME_NAME_TOO_LONG'));
		break;
	}
	if ($db->queryCell('SELECT COUNT(*) FROM '. CLAN_TABLE .' WHERE c_name = ? AND c_members >= 0', array($cname)) > 0)
	{
		notice(lang('ADMIN_CLANS_RENAME_NAME_IN_USE'));
		break;
	}
	$oldname = $clan1->c_name;
	$clan1->c_name = $cname;
	// save to database so the lists below will update properly
	$clan1->save();
	notice(lang('ADMIN_CLANS_RENAME_COMPLETE', $oldname, $clan1->c_name));
	logevent(varlist(array('oldname', 'cname'), get_defined_vars()));
} while (0);
notices();

?>
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('ADMIN_CLANS_LEADER_HEADER'); ?></th></tr>
<?php
$q = $db->query('SELECT * FROM '. CLAN_TABLE .' WHERE c_members > 0 ORDER BY c_id ASC') or warning('Failed to fetch clan list', 0);
$clans = $q->fetchAll();

$q = $db->query('SELECT e_id,e_name,c_id FROM '. EMPIRE_TABLE .' WHERE c_id != 0 AND u_id != 0 ORDER BY e_id ASC') or warning('Failed to fetch clan member list', 0);
$empires = $q->fetchAll();

// clanlist used below for Rename option
$clanlist = array();
$clanlist[0] = lang('ADMIN_CLANS_LABEL_SELECTCLAN');
foreach ($clans as $cdata)
{
	$clan_a = new prom_clan($cdata['c_id']);
	$clan_a->initdata($cdata);
	$clanlist[$clan_a->c_id] = (string)$clan_a;
?>
<tr><th><?php echo $clan_a->c_name; ?></th>
    <td class="ar"><form method="post" action="?location=admin/clans"><div><input type="hidden" name="admin_clan1" value="<?php echo $clan_a->c_id; ?>" />
<?php
	$clanmembers = array();
	foreach ($empires as $edata)
	{
		$emp_a = new prom_empire($edata['e_id']);
		$emp_a->initdata($edata);
		if ($emp_a->c_id != $clan_a->c_id)
			continue;
		if ($emp_a->e_id == $clan_a->e_id_leader)
			$clanmembers[$emp_a->e_id] = lang('ADMIN_CLANS_LABEL_LEADER', $emp_a);
		elseif ($emp_a->e_id == $clan_a->e_id_asst)
			$clanmembers[$emp_a->e_id] = lang('ADMIN_CLANS_LABEL_ASST', $emp_a);
		elseif ($emp_a->e_id == $clan_a->e_id_fa1)
			$clanmembers[$emp_a->e_id] = lang('ADMIN_CLANS_LABEL_FA1', $emp_a);
		elseif ($emp_a->e_id == $clan_a->e_id_fa2)
			$clanmembers[$emp_a->e_id] = lang('ADMIN_CLANS_LABEL_FA2', $emp_a);
		else	$clanmembers[$emp_a->e_id] = lang('ADMIN_CLANS_LABEL_NORMAL', $emp_a);
	}
	echo optionlist('admin_leader', $clanmembers, $clan_a->e_id_leader);
?>
<input type="hidden" name="action" value="leader" /><input type="submit" value="<?php echo lang('ADMIN_CLANS_LEADER_SUBMIT'); ?>" /></div></form></td></tr>
<?php
}
?>
</table>
<hr />
<form method="post" action="?location=admin/clans">
<table class="inputtable">
<tr><th colspan="2"><?php echo lang('ADMIN_CLANS_RENAME_HEADER'); ?></th></tr>
<tr><td><?php echo optionlist('admin_clan1', $clanlist); ?></td><td><input type="text" name="clan_name" size="8" maxlength="8" /></td></tr>
<tr><th colspan="2"><input type="hidden" name="action" value="rename" /><input type="submit" value="<?php echo lang('ADMIN_CLANS_RENAME_SUBMIT'); ?>" /></th></tr>
</table>
</form>
<?php
page_footer();
?>
