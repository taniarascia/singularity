<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: topplayers.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die('Access denied');

$sortcol = getFormVar('sortcol', 'avgrank');
$sortdir = getFormVar('sortdir', 'desc');

$sorttypes = array(
	'_default'	=> 'avgrank',
	'avgrank'	=> array('u_avgrank {DIR}, u_id ASC'),
	'bestrank'	=> array('u_bestrank {DIR}, u_id ASC'),
	'offtotal'	=> array('u_offtotal {DIR}, u_id ASC'),
	'deftotal'	=> array('u_deftotal {DIR}, u_id ASC'),
	'kills'		=> array('u_kills {DIR}, u_id ASC'),
	'deaths'	=> array('u_deaths {DIR}, u_id ASC'),
	'plays'		=> array('u_numplays {DIR}, u_id ASC'),
);
$sortby = parsesort($sortcol, $sortdir, $sorttypes);

$numplayers = $db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE);
$disabled = $db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE .' WHERE u_flags & ? != 0', array(UFLAG_DISABLE));
$closed = $db->queryCell('SELECT COUNT(*) FROM '. USER_TABLE .' WHERE u_flags & ? != 0', array(UFLAG_CLOSED));

$playing = array();
$q = $db->query('SELECT u_id,u_oldid FROM '. EMPIRE_TABLE);
foreach ($q as $data)
{
	if ($data['u_id'] > 0)
		$playing[$data['u_id']] = TRUE;
	if ($data['u_oldid'] > 0)
		$playing[$data['u_oldid']] = TRUE;
}

$html = new prom_html_compact();
$html->begin('TOPPLAYERS_TITLE');
?>
<h2><?php echo lang('TOPPLAYERS_HEADER'); ?></h2>
<?php echo gmdate(lang('COMMON_TIME_FORMAT'), CUR_TIME); ?><br /><br />
<?php echo lang('TOPPLAYERS_CREATED'); ?> <span class="cgood"><?php echo $numplayers; ?></span><br />
<?php echo lang('TOPPLAYERS_DISABLED'); ?> <span class="mdisabled"><?php echo $disabled; ?></span><br />
<?php echo lang('TOPPLAYERS_CLOSED'); ?> <span class="mdead"><?php echo $closed; ?></span><br /><br />
<?php echo lang('COMMON_COLORKEY'); ?> <span class="mprotected"><?php echo lang('COMMON_COLOR_NEW'); ?></span> - <span class="mdisabled"><?php echo lang('COMMON_COLOR_DISABLED'); ?></span> - <span class="mdead"><?php echo lang('COMMON_COLOR_CLOSED'); ?></span> - <span class="madmin"><?php echo lang('COMMON_COLOR_ADMIN'); ?></span><br />
<?php echo lang('TOPPLAYERS_NOTE_PLAYING'); ?><br />
<table class="scorestable">
<tr class="era0">
    <th class="ar"><?php echo sortlink(lang('COLUMN_AVGRANK'), '?location=topplayers&amp;', $sortcol, $sortdir, 'avgrank', 'desc'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_USER'); ?></th>
    <th class="ar"><?php echo sortlink(lang('COLUMN_ROUNDSPLAYED'), '?location=topplayers&amp;', $sortcol, $sortdir, 'plays', 'desc'); ?></th>
    <th class="ar"><?php echo sortlink(lang('COLUMN_BESTRANK'), '?location=topplayers&amp;', $sortcol, $sortdir, 'bestrank', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_ATTACKS'), '?location=topplayers&amp;', $sortcol, $sortdir, 'offtotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_DEFENDS'), '?location=topplayers&amp;', $sortcol, $sortdir, 'deftotal', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_KILLS'), '?location=topplayers&amp;', $sortcol, $sortdir, 'kills', 'desc'); ?></th>
    <th class="ac"><?php echo sortlink(lang('COLUMN_DEATHS'), '?location=topplayers&amp;', $sortcol, $sortdir, 'deaths', 'desc'); ?></th></tr>
<?php

$sql = 'SELECT u_avgrank,u_name,u_id,u_flags,u_numplays,u_sucplays,u_bestrank,u_offsucc,u_offtotal,u_defsucc,u_deftotal,u_kills,u_deaths FROM '. USER_TABLE .' ORDER BY '. $sortby;
$sql = $db->setLimit($sql, TOPPLAYERS_COUNT);
$topplayers = $db->query($sql) or warning('Failed to fetch top players listing', 0);
while ($data = $topplayers->fetch())
{
	$user_a = new prom_user($data['u_id']);
	$user_a->initdata($data);

	$color = 'normal';
	if ($user_a->u_flags & UFLAG_MOD)
		$color = 'admin';
	elseif ($user_a->u_flags & UFLAG_CLOSED)
		$color = 'dead';
	elseif ($user_a->u_flags & UFLAG_DISABLE)
		$color = 'disabled';
	elseif ($user_a->u_numplays == 0)
		$color = 'protected';
?>
<tr class="m<?php echo $color; ?>">
    <td class="ar"><?php if (isset($playing[$user_a->u_id])) echo "*"; ?><?php echo percent($user_a->u_avgrank * 100, 2); ?></td>
    <td class="ac"><a href="?location=playerstats&amp;id=<?php echo $user_a->u_id; ?>"><?php echo $user_a; ?></a></td>
    <td class="ar"><?php echo lang('COMMON_NUMBER_PERCENT', $user_a->u_numplays, percent($user_a->u_sucplays / max($user_a->u_numplays, 1) * 100)); ?></td>
    <td class="ar"><?php echo percent($user_a->u_bestrank * 100, 2); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $user_a->u_offtotal, percent($user_a->u_offsucc / max($user_a->u_offtotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo lang('COMMON_NUMBER_PERCENT', $user_a->u_deftotal, percent($user_a->u_defsucc / max($user_a->u_deftotal, 1) * 100)); ?></td>
    <td class="ac"><?php echo $user_a->u_kills; ?></td>
    <td class="ac"><?php echo $user_a->u_deaths; ?></td></tr>
<?php
	$user_a = NULL;
}
// footer row shouldn't have sort links on it
?>
<tr class="era0">
    <th class="ar"><?php echo lang('COLUMN_AVGRANK'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_USER'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_ROUNDSPLAYED'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_BESTRANK'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ATTACKS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_DEFENDS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_KILLS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_DEATHS'); ?></th></tr>
</table>
<?php
$html->end();
?>
