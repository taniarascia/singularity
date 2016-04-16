<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: topempires.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

if (CLAN_ENABLE)
	$cnames = prom_clan::getNames();
$races = prom_race::getNames();
$eras = prom_era::getNames();

$sortcol = getFormVar('sortcol', 'rank');
$sortdir = getFormVar('sortdir', 'asc');

$sorttypes = array(
	'_default'	=> 'rank',
	'rank'		=> array('e_rank {DIR}'),
	'networth'	=> array('e_networth {DIR}, e_rank ASC'),
	'offtotal'	=> array('e_offtotal {DIR}, e_rank ASC'),
	'deftotal'	=> array('e_deftotal {DIR}, e_rank ASC'),
	'kills'		=> array('e_kills {DIR}, e_rank ASC'),
);
if (SCORE_ENABLE)
	$sorttypes['score'] = array('e_score {DIR}, e_rank ASC', 'e_score');
$sortby = parsesort($sortcol, $sortdir, $sorttypes);

$numempires = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0');
$online = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0 AND e_flags & ? != 0', array(EFLAG_ONLINE));
$killed = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = 0 AND e_land = 0');
$abandoned = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = 0 AND e_land > 0');
$disabled = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0 AND e_flags & ? != 0', array(EFLAG_DISABLE));

$html = new prom_html_compact();
$html->begin('TOPEMPIRES_TITLE');
?>
<h2><?php echo lang('TOPEMPIRES_HEADER'); ?></h2>
<?php echo gmdate(lang('COMMON_TIME_FORMAT'), CUR_TIME); ?><br /><br />
<?php echo lang('TOPEMPIRES_ALIVE'); ?> <span class="cneutral"><?php echo $numempires; ?></span><br />
<?php echo lang('TOPEMPIRES_ONLINE'); ?> <span class="cgood"><?php echo $online; ?></span><br />
<?php echo lang('TOPEMPIRES_DEAD'); ?> <span class="mdead"><?php echo $killed; ?></span> + <span class="mdead"><?php echo $abandoned; ?></span><br />
<?php echo lang('TOPEMPIRES_DISABLED'); ?> <span class="mdisabled"><?php echo $disabled; ?></span><br />
<b><?php echo lang('TOPEMPIRES_NOTE_UPDATES', duration(TURNS_FREQ * 60)); ?></b><br /><br />
<?php echo lang('COMMON_COLORKEY'); ?> <span class="mprotected"><?php echo lang('COMMON_COLOR_PROTECT'); ?></span> - <span class="mdead"><?php echo lang('COMMON_COLOR_DEAD'); ?></span> - <span class="mdisabled"><?php echo lang('COMMON_COLOR_DISABLED'); ?></span> - <span class="madmin"><?php echo lang('COMMON_COLOR_ADMIN'); ?></span><br />
<?php echo lang('TOPEMPIRES_NOTE_ONLINE'); ?><br />
<table class="scorestable">
<tr class="era0">
    <th class="ar"><?php echo sortlink(lang('COLUMN_RANK'), '?location=topempires&amp;', $sortcol, $sortdir, 'rank', 'asc'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_LAND'); ?></th>
    <th class="ar"><?php echo sortlink(lang('COLUMN_NETWORTH'), '?location=topempires&amp;', $sortcol, $sortdir, 'networth', 'desc'); ?></th>
<?php	if (CLAN_ENABLE) { ?>
    <th class="ac"><?php echo lang('COLUMN_CLAN'); ?></th>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <th class="ac"><?php echo sortlink(lang('COLUMN_SCORE'), '?location=topempires&amp;', $sortcol, $sortdir, 'score', 'desc'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_ATTACKS'), '?location=topempires&amp;', $sortcol, $sortdir, 'offtotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_DEFENDS'), '?location=topempires&amp;', $sortcol, $sortdir, 'deftotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_KILLS'), '?location=topempires&amp;', $sortcol, $sortdir, 'kills', 'desc'); ?></th></tr>
<?php
$sql = 'SELECT e_rank,e_name,e_id,e_land,e_networth,c_id,e_race,e_era,e_flags,e_turnsused,e_vacation,e_offsucc,e_offtotal,e_defsucc,e_deftotal,e_kills,e_score FROM '. EMPIRE_TABLE .' WHERE u_id > 0 ORDER BY '. $sortby;
$sql = $db->setLimit($sql, TOPEMPIRES_COUNT);
$topempires = $db->query($sql) or warning('Failed to fetch top empires listing', 0);
while ($data = $topempires->fetch())
{
	$emp_a = new prom_empire($data['e_id']);
	$emp_a->initdata($data);

	$color = 'normal';
	if (($emp_a->e_land == 0) || ($emp_a->e_flags & EFLAG_DELETE))
		$color = 'dead';
	elseif ($emp_a->e_flags & EFLAG_ADMIN)
		$color = 'admin';
	elseif ($emp_a->e_flags & EFLAG_DISABLE)
		$color = 'disabled';
	elseif ($emp_a->is_protected() || $emp_a->is_vacation())
		$color = 'protected';
?>
<tr class="m<?php echo $color; ?>">
    <td class="ar"><?php if ($emp_a->e_flags & EFLAG_ONLINE) echo "*"; echo $emp_a->e_rank; ?></td>
    <td class="ac"><?php echo $emp_a; ?></td>
    <td class="ar"><?php echo number($emp_a->e_land); ?></td>
    <td class="ar"><?php echo money($emp_a->e_networth); ?></td>
<?php	if (CLAN_ENABLE) { ?>
    <td class="ac"><?php echo $cnames[$emp_a->c_id]; ?></td>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <td class="ac"><?php echo $emp_a->e_score; ?></td>
<?php	} ?>
    <td class="ac"><?php echo $races[$emp_a->e_race]; ?></td>
    <td class="ac"><?php echo $eras[$emp_a->e_era]; ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $emp_a->e_offtotal, percent($emp_a->e_offsucc / max($emp_a->e_offtotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $emp_a->e_deftotal, percent($emp_a->e_defsucc / max($emp_a->e_deftotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo $emp_a->e_kills; ?></td></tr>
<?php
	$emp_a = NULL;
}
// footer row shouldn't have sort links on it
?>
<tr class="era0">
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
</table>
<?php
$html->end();
?>
