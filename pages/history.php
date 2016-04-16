<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: history.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

function scoreHeader ($round, $baseurl, $sortcol, $sortdir)
{
?>
<tr class="era0">
    <th class="ar"><?php echo sortlink(lang('COLUMN_RANK'), $baseurl, $sortcol, $sortdir, 'rank', 'asc'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_USER'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_LAND'); ?></th>
    <th class="ar"><?php echo sortlink(lang('COLUMN_NETWORTH'), $baseurl, $sortcol, $sortdir, 'networth', 'desc'); ?></th>
<?php	if ($round['hr_flags'] & HRFLAG_CLANS) { ?>
    <th class="ac"><?php echo lang('COLUMN_CLAN'); ?></th>
<?php	} ?>
<?php	if ($round['hr_flags'] & HRFLAG_SCORE) { ?>
    <th class="ac"><?php echo sortlink(lang('COLUMN_SCORE'), $baseurl, $sortcol, $sortdir, 'score', 'desc'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_ATTACKS'), $baseurl, $sortcol, $sortdir, 'offtotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_DEFENDS'), $baseurl, $sortcol, $sortdir, 'deftotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_KILLS'), $baseurl, $sortcol, $sortdir, 'kills', 'desc'); ?></th></tr>
<?php
}

function scoreLine ($round, $empire, $cnames)
{
	if ($empire['he_flags'] & HEFLAG_ADMIN)
		$color = 'admin';
	elseif ($empire['he_flags'] & HEFLAG_PROTECT)
		$color = 'protected';
	else	$color = 'normal';
?>
<tr class="m<?php echo $color; ?>">
    <td class="ar"><?php echo $empire['he_rank']; ?></td>
    <td class="ac"><?php echo lang('COMMON_EMPIRE_NAMEID', $empire['he_name'], prenum($empire['he_id'])); ?></td>
    <td class="ac"><a href="?location=playerstats&amp;id=<?php echo $empire['u_id']; ?>"><?php echo lang('COMMON_USER_NAMEID', $empire['u_name'], prenum($empire['u_id'])); ?></a></td>
    <td class="ar"><?php echo number($empire['he_land']); ?></td>
    <td class="ar"><?php echo money($empire['he_networth']); ?></td>
<?php	if ($round['hr_flags'] & HRFLAG_CLANS) { ?>
    <td class="ac"><?php echo $cnames[$empire['hc_id']]; ?></td>
<?php	} ?>
<?php	if ($round['hr_flags'] & HRFLAG_SCORE) { ?>
    <td class="ac"><?php echo $empire['he_score']; ?></td>
<?php	} ?>
    <td class="ac"><?php echo lang($empire['he_race']); ?></td>
    <td class="ac"><?php echo lang($empire['he_era']); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $empire['he_offtotal'], percent($empire['he_offsucc'] / max($empire['he_offtotal'], 1) * 100)); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $empire['he_deftotal'], percent($empire['he_defsucc'] / max($empire['he_deftotal'], 1) * 100)); ?></td>
    <td class="ac"><?php echo $empire['he_kills']; ?></td></tr>
<?php
}

function scoreFooter ($round)
{
?>
<tr class="era0">
    <th class="ar"><?php echo lang('COLUMN_RANK'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_USER'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_LAND'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_NETWORTH'); ?></th>
<?php	if ($round['hr_flags'] & HRFLAG_CLANS) { ?>
    <th class="ac"><?php echo lang('COLUMN_CLAN'); ?></th>
<?php	} ?>
<?php	if ($round['hr_flags'] & HRFLAG_SCORE) { ?>
    <th class="ac"><?php echo lang('COLUMN_SCORE'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ATTACKS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_DEFENDS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_KILLS'); ?></th></tr>
<?php
}

$html = new prom_html_compact();
$round_id = getFormVar('round');
if (!$round_id)
{
	$html->begin('HISTORY_INDEX_TITLE');
	$q = $db->query('SELECT * FROM '. HISTORY_ROUND_TABLE .' ORDER BY hr_id ASC');
	$lastname = 'undefined';
?>
<table>
<?php
	foreach ($q as $round)
	{
		if ($lastname != $round['hr_name'])
		{
			$lastname = $round['hr_name'];
?>
<tr><th><?php echo $round['hr_name']; ?></th></tr>
<?php
		}
?>
<tr><td class="ac"><a href="?location=history&amp;round=<?php echo $round['hr_id']; ?>"><?php echo lang('HISTORY_SUMMARY_DATES', gmdate('F j, Y', $round['hr_startdate']), gmdate('F j, Y', $round['hr_stopdate'])); ?></a></td></tr>
<?php
	}
	if ($lastname == 'undefined')
	{
?>
<tr><td><?php echo lang('HISTORY_INDEX_EMPTY'); ?></td></tr>
<?php
	}
?>
</table>
<?php
	$html->end();
	exit;
}

$q = $db->prepare('SELECT * FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id = ?');
$q->bindIntValue(1, $round_id);
$q->execute() or warning('Error fetching round history data', 0);
$round = $q->fetch();

if (!$round)
	error_404('ERROR_TITLE', lang('HISTORY_ERROR_NO_DATA'));

$baseurl = '?location=history&amp;round='. $round_id .'&amp;';

if (!$action)
{
	$html->begin('HISTORY_SUMMARY_TITLE');
?>
<h2><?php echo $round['hr_name']; ?></h2>
<h3><?php echo lang('HISTORY_SUMMARY_DATES', gmdate('F j, Y', $round['hr_startdate']), gmdate('F j, Y', $round['hr_stopdate'])); ?></h3>
<p><?php echo $round['hr_description']; ?></p>
<a href="<?php echo $baseurl; ?>action=empire"><?php echo lang('LOGIN_TOPEMPIRES'); ?></a><br />
<?php
	if ($round['hr_flags'] & HRFLAG_CLANS)
	{
?>
<a href="<?php echo $baseurl; ?>action=clan"><?php echo lang('LOGIN_TOPCLANS'); ?></a><br />
<?php
	}
	$html->end();
	exit;
}

$cnames = array();
if ($round['hr_flags'] & HRFLAG_CLANS)
{
	$q = $db->prepare('SELECT hc_id, hc_name FROM '. HISTORY_CLAN_TABLE .' WHERE hr_id = ? ORDER BY hc_id ASC');
	$q->bindIntValue(1, $round['hr_id']);
	$q->execute() or warning('Failed to retrieve list of historic clan names', 0);

	$cnames[0] = lang('CLAN_NONE');
	while ($c = $q->fetch())
		$cnames[$c['hc_id']] = $c['hc_name'];
}

if ($action == 'empire')
{
	$html->begin('HISTORY_EMPIRE_TITLE');
	$baseurl .= 'action=empire&amp;';

	$sortcol = getFormVar('sortcol', 'rank');
	$sortdir = getFormVar('sortdir', 'asc');

	$sorttypes = array(
		'_default'	=> 'rank',
		'rank'		=> array('he_rank {DIR}'),
		'networth'	=> array('he_networth {DIR}'),
		'offtotal'	=> array('he_offtotal {DIR}, he_rank ASC'),
		'deftotal'	=> array('he_deftotal {DIR}, he_rank ASC'),
		'kills'		=> array('he_kills {DIR}, he_rank ASC'),
	);
	if ($round['hr_flags'] & HRFLAG_SCORE)
		$sorttypes['score'] = array('he_score {DIR}');
	$sortby = parsesort($sortcol, $sortdir, $sorttypes);

	$sql = 'SELECT * FROM '. HISTORY_EMPIRE_TABLE .' LEFT OUTER JOIN '. USER_TABLE .' USING (u_id) WHERE hr_id = ? ORDER BY '. $sortby;
	$sql = $db->setLimit($sql, 50);
	$q = $db->prepare($sql);
	$q->bindIntValue(1, $round_id);
	$q->execute();
?>
<h2><?php echo lang('HISTORY_EMPIRE_HEADER'); ?></h2>
<?php echo lang('HISTORY_EMPIRE_CREATED'); ?> <span class="cneutral"><?php echo $round['hr_allempires']; ?></span><br />
<?php echo lang('HISTORY_EMPIRE_ALIVE'); ?> <span class="cgood"><?php echo $round['hr_liveempires']; ?></span><br />
<?php echo lang('HISTORY_EMPIRE_DEAD'); ?> <span class="mdead"><?php echo $round['hr_deadempires']; ?></span> + <span class="mdead"><?php echo $round['hr_delempires']; ?></span><br />
<?php echo lang('COMMON_COLORKEY'); ?> <span class="mprotected"><?php echo lang('COMMON_COLOR_PROTECT'); ?></span> - <span class="madmin"><?php echo lang('COMMON_COLOR_ADMIN'); ?></span><br />
<table class="scorestable">
<?php
	scoreHeader($round, $baseurl, $sortcol, $sortdir);
	foreach ($q as $empire)
		scoreLine($round, $empire, $cnames);
	scoreFooter($round);
?>
</table>
<?php
	$html->end();
	exit;
}
if ($action == 'clan')
{
	if (!($round['hr_flags'] & HRFLAG_CLANS))
		error_404('ERROR_TITLE', lang('HISTORY_ERROR_NO_CLAN'));
	$baseurl1 = $baseurl .'action=clan&amp;';
	$baseurl2 = $baseurl .'action=member&amp;';
	$html->begin('HISTORY_CLAN_TITLE');

	$sortcol = getFormVar('sortcol', 'totalnet');
	$sortdir = getFormVar('sortdir', 'desc');

	$sorttypes = array(
		'_default'	=> 'totalnet',
		'totalnet'	=> array('hc_totalnet {DIR}'),
		'members'	=> array('hc_members {DIR}'),
		'avgnet'	=> array('hc_totalnet/hc_members {DIR}'),
	);
	$sortby = parsesort($sortcol, $sortdir, $sorttypes);

	$q = $db->prepare('SELECT * FROM '. HISTORY_CLAN_TABLE .' WHERE hr_id = ? AND hc_members >= ? ORDER BY '. $sortby);
	$q->bindIntValue(1, $round_id);
	$q->bindIntValue(2, $round['hr_smallclansize']);
	$q->execute();
?>
<table class="scorestable">
<tr class="era0"><th colspan="5"><?php echo lang('HISTORY_CLAN_HEADER', $round['hr_smallclansize']); ?></th></tr>
<tr class="era0">
    <th><?php echo lang('COLUMN_CLAN_NAME'); ?></th>
    <th><?php echo lang('COLUMN_CLAN_TITLE'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_MEMBERS'), $baseurl1, $sortcol, $sortdir, 'members', 'desc'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_AVGNET'), $baseurl1, $sortcol, $sortdir, 'avgnet', 'desc'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_TOTALNET'), $baseurl1, $sortcol, $sortdir, 'totalnet', 'desc'); ?></th></tr>
<?php
	foreach ($q as $clan)
	{
?>
<tr class="ac">
    <td><a href="<?php echo $baseurl2; ?>clan=<?php echo $clan['hc_id']; ?>"><?php echo $clan['hc_name']; ?></a></td>
    <td><?php echo $clan['hc_title']; ?></td>
    <td><?php echo $clan['hc_members']; ?></td>
    <td><?php echo money($clan['hc_totalnet'] / $clan['hc_members']); ?></td>
    <td><?php echo money($clan['hc_totalnet']); ?></td></tr>
<?php
	}
	if ($round['hr_smallclans'] == $round['hr_allclans'])
		echo '<tr class="ac"><th colspan="5">'. lang('HISTORY_CLAN_NO_CLANS') .'</th></tr>';
?>
</table>
<?php echo lang('HISTORY_CLAN_TOO_SMALL', $round['hr_smallclans'], $round['hr_allclans'], percent(100 * ($round['hr_smallclans'] / max(1, $round['hr_allclans'])))); ?><br />
<?php echo lang('HISTORY_CLAN_INDEPENDENT', $round['hr_nonclanempires'], $round['hr_liveempires'], percent(100 * ($round['hr_nonclanempires'] / max(1, $round['hr_liveempires']))), $baseurl2 .'clan=0'); ?><br />
<?php
	$html->end();
	exit;
}

if ($action == 'member')
{
	if (!($round['hr_flags'] & HRFLAG_CLANS))
		error_404('ERROR_TITLE', lang('HISTORY_ERROR_NO_CLAN'));
	$clan_id = fixInputNum(getFormVar('clan'));
	$html->begin('HISTORY_MEMBER_TITLE');
	$baseurl .= 'action=member&amp;clan='. $clan_id .'&amp;';

	$sortcol = getFormVar('sortcol', 'rank');
	$sortdir = getFormVar('sortdir', 'asc');

	$sorttypes = array(
		'_default'	=> 'rank',
		'rank'		=> array('he_rank {DIR}'),
		'networth'	=> array('he_networth {DIR}'),
		'offtotal'	=> array('he_offtotal {DIR}, he_rank ASC'),
		'deftotal'	=> array('he_deftotal {DIR}, he_rank ASC'),
		'kills'		=> array('he_kills {DIR}, he_rank ASC'),
	);
	if ($round['hr_flags'] & HRFLAG_SCORE)
		$sorttypes['score'] = array('he_score {DIR}');
	$sortby = parsesort($sortcol, $sortdir, $sorttypes);

	$sql = 'SELECT * FROM '. HISTORY_EMPIRE_TABLE .' LEFT OUTER JOIN '. USER_TABLE .' USING (u_id) WHERE hr_id = ? AND hc_id = ? ORDER BY '. $sortby;
	$sql = $db->setLimit($sql, 50);
	$q = $db->prepare($sql);
	$q->bindIntValue(1, $round_id);
	$q->bindIntValue(2, $clan_id);
	$q->execute();
?>
<h2><?php if ($clan_id == 0) echo lang('HISTORY_MEMBER_HEADER_NOCLAN'); else echo lang('HISTORY_MEMBER_HEADER_CLAN'); ?></h2>
<?php echo lang('COMMON_COLORKEY'); ?> <span class="mprotected"><?php echo lang('COMMON_COLOR_PROTECT'); ?></span> - <span class="madmin"><?php echo lang('COMMON_COLOR_ADMIN'); ?></span><br />
<table class="scorestable">
<?php
	scoreHeader($round, $baseurl, $sortcol, $sortdir);
	foreach ($q as $empire)
		scoreLine($round, $empire, $cnames);
	scoreFooter($round);
?>
</table>
<?php
	$html->end();
	exit;
}

error_404('ERROR_TITLE', lang('HISTORY_ERROR_BAD_MODE'));
?>
