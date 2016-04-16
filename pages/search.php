<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: search.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'SEARCH_TITLE';

page_header();

function searchHeader ($color)
{
?>
<tr class="era<?php echo $color; ?>">
    <th class="ar"><?php echo lang('COLUMN_RANK'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_LAND'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_NETWORTH'); ?></th>
<?php	if (CLAN_ENABLE) { ?>
    <th class="ac"><?php echo lang('COLUMN_CLAN'); ?></th>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <th class="ac"><?php echo lang('COLUMN_SCORE'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ATTACKS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_DEFENDS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_KILLS'); ?></th></tr>
<?php
}

function searchLine ($color, $emp)
{
	global $cnames, $races, $eras;
?>
<tr class="m<?php echo $color; ?>">
    <td class="ar"><?php if ($emp->e_flags & EFLAG_ONLINE) echo "*"; echo $emp->e_rank; ?></td>
    <td class="ac"><?php echo $emp; ?></td>
    <td class="ar"><?php echo number($emp->e_land); ?></td>
    <td class="ar"><?php echo money($emp->e_networth); ?></td>
<?php	if (CLAN_ENABLE) { ?>
    <td class="ac"><?php echo $cnames[$emp->c_id]; ?></td>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <td class="ac"><?php echo $emp->e_score; ?></td>
<?php	} ?>
    <td class="ac"><?php echo $races[$emp->e_race]; ?></td>
    <td class="ac"><?php echo $eras[$emp->e_era]; ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $emp->e_offtotal, percent($emp->e_offsucc / max($emp->e_offtotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $emp->e_deftotal, percent($emp->e_defsucc / max($emp->e_deftotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo $emp->e_kills; ?></td></tr>
<?php
}
if ($action == 'search') do
{
	// unlike other forms, this one should permit both GET and POST

	$checks = array();
	$values = array();
	$vars = array();

	// allow user to specify search results limit
	$limit = fixInputNum(getFormVar('search_limit'));
	if (!$limit)
		$limit = 25;
	if ($limit > 100)
		$limit = 100;
	$vars[] = 'limit';

	$name = getFormVar('search_name');
	$num = fixInputNum(getFormVar('search_num'));
	if (CLAN_ENABLE)
		$clan = fixInputNumSigned(getFormVar('search_clan', -1));

	$race = fixInputNumSigned(getFormVar('search_race', -1));
	$era = fixInputNumSigned(getFormVar('search_era', -1));

	$filter_networth_min = fixInputBool(getFormVar('search_filter_networth_min'));
	$filter_networth_max = fixInputBool(getFormVar('search_filter_networth_max'));
	$networth_min = fixInputNum(getFormVar('search_networth_min'));
	$networth_max = fixInputNum(getFormVar('search_networth_max'));

	$filter_offline = fixInputBool(getFormVar('search_filter_offline'));
	$filter_dead = fixInputBool(getFormVar('search_filter_dead'));

	$sort = getFormVar('search_sort');
	$vars[] = 'sort';
	$order_by = '';
	switch ($sort)
	{
	case 'networth':
		$order_by = 'e_networth DESC';
		break;
	case 'land':
		$order_by = 'e_land DESC';
		break;
	case 'name':
		$order_by = 'e_name ASC';
		break;
	case 'num':
		$order_by = 'e_id ASC';
		break;
	case 'clan':
		if (CLAN_ENABLE)
		{
			$order_by = 'c_id ASC';
			break;
		}
		break;
	case 'score':
		if (SCORE_ENABLE)
		{
			$order_by = 'e_score DESC';
			break;
		}
		break;
	case 'race':
		$order_by = 'e_race ASC';
		break;
	case 'era':
		$order_by = 'e_era ASC';
		break;
	}
	if (!$order_by)
	{
		if (SCORE_ENABLE)
			$order_by = 'e_score DESC';
		else	$order_by = 'e_networth DESC';
	}

	if ($name)
	{
		$checks[] = 'e_name LIKE ?';
		$values[] = '%'. $name .'%';
		$vars[] = 'name';
	}
	if ($num)
	{
		$checks[] = 'e_id = ?';
		$values[] = $num;
		$vars[] = 'num';
	}
	if (CLAN_ENABLE && ($clan != -1))
	{
		$checks[] = 'c_id = ?';
		$values[] = $clan;
		$vars[] = 'clan';
	}
	if ($race != -1)
	{
		if (!prom_race::exists($race))
		{
			notice(lang('SEARCH_INVALID_RACE'));
			break;
		}
		$checks[] = 'e_race = ?';
		$values[] = $race;
		$vars[] = 'race';
	}
	if ($era != -1)
	{
		if (!prom_era::exists($era))
		{
			notice(lang('SEARCH_INVALID_ERA'));
			break;
		}
		$checks[] = 'e_era = ?';
		$values[] = $era;
		$vars[] = 'era';
	}
	if ($filter_networth_min)
	{
		$checks[] = 'e_networth >= ?';
		$values[] = $networth_min;
		$vars[] = 'networth_min';
	}
	if ($filter_networth_max)
	{
		$checks[] = 'e_networth <= ?';
		$values[] = $networth_max;
		$vars[] = 'networth_max';
	}
	if ($filter_offline)
	{
		$checks[] = 'e_flags & ? != 0';
		$values[] = EFLAG_ONLINE;
	}
	if ($filter_dead)
		$checks[] = 'e_land > 0';

	// always filter out deleted empires
	$checks[] = 'u_id > 0';
	// assemble checks into a string of query conditions
	$query = implode(' AND ', $checks);
	$sql = 'SELECT e_rank, e_name, e_id, e_land, e_networth, c_id, e_race, e_era, e_flags, e_turnsused,'.
			'e_vacation, e_kills, e_score, e_offsucc, e_offtotal, e_defsucc, e_deftotal '.
			'FROM '. EMPIRE_TABLE .' '.
			'WHERE '. $query .' '.
			'ORDER BY '. $order_by;
	$sql = $db->setLimit($sql, $limit);
	$q = $db->prepare($sql);
	$q->bindAllValues($values);
	$q->execute() or warning('Failed to fetch search results', 0);
	$search = $q->fetchAll();
	$numrows = count($search);
	$vars[] = 'numrows';
	logevent(varlist($vars, get_defined_vars()));
	if ($numrows == 0)
	{
		notice(lang('SEARCH_NO_RESULTS'));
		break;
	}
?>
<table class="scorestable">
<?php
	$rel = array();
	if (CLAN_ENABLE && ($emp1->c_id != 0))
	{
		$clan_a = new prom_clan($emp1->c_id);

		$ally = $clan_a->getAllies();
		$war = $clan_a->getWars();
		foreach ($ally as $id)
			$rel[$id] = CRFLAG_ALLY;
		foreach ($war as $id)
			$rel[$id] = CRFLAG_WAR;
		$clan_a = NULL;
	}

	searchHeader($emp1->e_era);
	foreach ($search as $data)
	{
		$emp_a = new prom_empire($data['e_id']);
		$emp_a->initdata($data);

		$color = 'normal';
		if ($emp_a->e_id == $emp1->e_id)
			$color = 'self';
		elseif (($emp_a->e_land == 0) || ($emp_a->e_flags & EFLAG_DELETE))
			$color = 'dead';
		elseif ($emp_a->e_flags & EFLAG_ADMIN)
			$color = 'admin';
		elseif ($emp_a->e_flags & EFLAG_DISABLE)
			$color = 'disabled';
		elseif ($emp_a->is_protected() || $emp_a->is_vacation())
			$color = 'protected';
		elseif (($emp_a->c_id != 0) && ($emp_a->c_id == $emp1->c_id))
			$color = 'clan';
		elseif (isset($rel[$emp_a->c_id]) && ($rel[$emp_a->c_id] == CRFLAG_ALLY))
			$color = 'ally';
		elseif (isset($rel[$emp_a->c_id]) && ($rel[$emp_a->c_id] == CRFLAG_WAR))
			$color = 'war';

		searchLine($color, $emp_a);
		$emp_a = NULL;
	}
	searchHeader($emp1->e_era);
?>
</table>
<?php
	notice(lang('SEARCH_COMPLETE', $numrows));
} while (0);
else
{
	$name = '';
	$num = '';
	if (CLAN_ENABLE)
		$clan = -1;
	$race = -1;
	$era = -1;
	$limit = 25;
	$filter_networth_min = FALSE;
	$filter_networth_max = FALSE;
	$networth_min = round($emp1->e_networth / 10);
	$networth_max = $emp1->e_networth * 10;
	$filter_offline = FALSE;
	$filter_dead = TRUE;
	if (SCORE_ENABLE)
		$sort = 'score';
	else	$sort = 'networth';
}
notices();
?>
<form method="post" action="?location=search">
<table class="inputtable">
<tr><th class="al"><?php echo lang('SEARCH_LABEL_NUM'); ?></th>
    <td><input type="text" name="search_num" value="<?php echo $num; ?>" size="4" /></td></tr>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_NAME'); ?></th>
    <td><input type="text" name="search_name" value="<?php echo htmlspecialchars($name); ?>" size="32" /></td></tr>
<?php if (CLAN_ENABLE) { ?>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_CLAN'); ?></th>
    <td><?php
$clanlist = array();
$clanlist[-1] = lang('SEARCH_LABEL_CLAN_ANY');
$clanlist[0] = lang('SEARCH_LABEL_CLAN_NONE');
$q = $db->query('SELECT c_id,c_name,c_title FROM '. CLAN_TABLE .' WHERE c_members > 0 ORDER BY c_id ASC');
while ($cdata = $q->fetch())
	$clanlist[$cdata['c_id']] = lang('SEARCH_LABEL_CLAN_FORMAT', $cdata['c_name'], $cdata['c_title']);
echo optionlist('search_clan', $clanlist, $clan);
?></td></tr>
<?php } ?>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_RACE'); ?></th>
    <td><?php
$racelist = array();
$racelist[-1] = lang('SEARCH_LABEL_RACE_ANY');
foreach ($races as $rid => $rname)
	$racelist[$rid] = $rname;
echo optionlist('search_race', $racelist, $race);
?></td></tr>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_ERA'); ?></th>
    <td><?php
$eralist = array();
$eralist[-1] = lang('SEARCH_LABEL_ERA_ANY');
foreach ($eras as $eid => $ename)
	$eralist[$eid] = $ename;
echo optionlist('search_era', $eralist, $era);
?></td></tr>
<tr><th class="al"><?php echo checkbox('search_filter_networth_min', lang('SEARCH_LABEL_NETWORTH_MIN'), 1, $filter_networth_min); ?></th>
    <td><input type="text" name="search_networth_min" size="9" value="<?php echo money($networth_min); ?>" /></td></tr>
<tr><th class="al"><?php echo checkbox('search_filter_networth_max', lang('SEARCH_LABEL_NETWORTH_MAX'), 1, $filter_networth_max); ?></th>
    <td><input type="text" name="search_networth_max" size="9" value="<?php echo money($networth_max); ?>" /></td></tr>
<tr><th class="al" colspan="2"><?php echo checkbox('search_filter_offline', lang('SEARCH_LABEL_OFFLINE'), 1, $filter_offline); ?></th></tr>
<tr><th class="al" colspan="2"><?php echo checkbox('search_filter_dead', lang('SEARCH_LABEL_DEAD'), 1, $filter_dead); ?></th></tr>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_SORT'); ?></th>
    <td><?php
$sortlist = array();
$sortlist['networth'] = lang('SEARCH_LABEL_SORT_NETWORTH');
$sortlist['land'] = lang('SEARCH_LABEL_SORT_LAND');
$sortlist['name'] = lang('SEARCH_LABEL_SORT_NAME');
$sortlist['num'] = lang('SEARCH_LABEL_SORT_NUM');
if (CLAN_ENABLE)
	$sortlist['clan'] = lang('SEARCH_LABEL_SORT_CLAN');
if (SCORE_ENABLE)
	$sortlist['score'] = lang('SEARCH_LABEL_SORT_SCORE');
$sortlist['race'] = lang('SEARCH_LABEL_SORT_RACE');
$sortlist['era'] = lang('SEARCH_LABEL_SORT_ERA');
echo optionlist('search_sort', $sortlist, $sort);
?></td></tr>
<tr><th class="al"><?php echo lang('SEARCH_LABEL_LIMIT'); ?></th>
    <td><input type="text" name="search_limit" value="<?php echo $limit; ?>" size="4" /></td></tr>
<tr><td colspan="2" class="ac"><input type="hidden" name="action" value="search" /><input type="submit" value="<?php echo lang('SEARCH_SUBMIT'); ?>" /></td></tr>
</table>
</form>
<?php
page_footer();
?>
