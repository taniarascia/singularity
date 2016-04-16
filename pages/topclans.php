<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: topclans.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

$cnames = prom_clan::getNames();

$html = new prom_html_compact();
$html->begin('TOPCLANS_TITLE');

$members = array();
$totalnet = array();
$avgnet = array();

$q = $db->query('SELECT c_id,e_networth FROM '. EMPIRE_TABLE .' WHERE u_id > 0');
$unallied = $utotal = 0;
while ($edata = $q->fetch())
{
	$cnum = $edata['c_id'];
	$utotal++;
	if ($cnum == 0)
	{
		$unallied++;
		continue;
	}

	if (!isset($members[$cnum]))
	{	// if one is unset, then all of them are
		$members[$cnum] = 0;
		$totalnet[$cnum] = 0;
		$avgnet[$cnum] = 0;
	}

	$members[$cnum]++;
	$totalnet[$cnum] += $edata['e_networth'];
	$avgnet[$cnum] = round($totalnet[$cnum] / $members[$cnum]);
}
$sortcol = getFormVar('sortcol', 'totalnet');
$sortdir = getFormVar('sortdir', 'desc');
switch ($sortcol)
{
default:
	$sortcol = 'totalnet';
case 'totalnet':
	$sortby = $totalnet;
	break;
case 'members':
	$sortby = $members;
	break;
case 'avgnet':
	$sortby = $avgnet;
	break;
}
if ($sortdir == 'desc')
	arsort($sortby);
else	asort($sortby);
$clans = array();
foreach ($sortby as $key => $val)
	$clans[] = $key;
?>
<table class="scorestable">
<tr class="era0"><th colspan="5"><?php echo lang('CLANSTATS_HEADER', CLANSTATS_MINSIZE); ?></th></tr>
<tr class="era0">
    <th><?php echo lang('COLUMN_CLAN_NAME'); ?></th>
    <th><?php echo lang('COLUMN_CLAN_TITLE'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_MEMBERS'), '?location=topclans&amp;', $sortcol, $sortdir, 'members', 'desc'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_AVGNET'), '?location=topclans&amp;', $sortcol, $sortdir, 'avgnet', 'desc'); ?></th>
    <th><?php echo sortlink(lang('COLUMN_CLAN_TOTALNET'), '?location=topclans&amp;', $sortcol, $sortdir, 'totalnet', 'desc'); ?></th></tr>
<?php
$cunlisted = $ctotal = 0;
foreach ($clans as $num)
{
	$clan_a = new prom_clan($num);
	$clan_a->load();
	$ctotal++;
	if ($clan_a->c_members < CLANSTATS_MINSIZE)
	{
		$cunlisted++;
		continue;
	}
?>
<tr class="ac">
    <td><?php echo $clan_a->c_name; ?></td>
    <td><?php echo $clan_a->c_title; ?></td>
    <td><?php echo $clan_a->c_members; ?></td>
    <td><?php echo money($avgnet[$num]); ?></td>
    <td><?php echo money($totalnet[$num]); ?></td></tr>
<?php
}
if ($ctotal == 0)
	echo '<tr class="ac"><td colspan="5">'. lang('CLANSTATS_NO_CLANS') .'</td></tr>';
?>
</table>
<?php echo lang('CLANSTATS_TOO_SMALL', $cunlisted, $ctotal, percent(100 * ($cunlisted / max(1, $ctotal)))); ?><br />
<?php echo lang('CLANSTATS_INDEPENDENT', $unallied, $utotal, percent(100 * ($unallied / max(1, $utotal)))); ?><br />
<?php
$html->end();
?>
