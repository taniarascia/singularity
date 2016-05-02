<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: scores.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'SCORES_TITLE';

page_header(); ?>

<br/><img src="/images/score.jpg" style="max-width: 550px;"/>
<br/>

<?php

function scoreHeader ($color)
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
    <th class="ac"><?php echo lang('COLUMN_KILLS'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th></tr>
<?php
}

function scoreLine ($color, $emp)
{
	global $cnames, $races, $eras;
?>
<tr class="m<?php echo $color; ?>">
    <td class="ar"><?php if ($emp->e_flags & EFLAG_ONLINE) echo '*'; echo $emp->e_rank; ?></td>
    <td class="ac"><?php echo $emp; ?></td>
    <td class="ar"><?php echo number($emp->e_land); ?></td>
    <td class="ar"><?php echo money($emp->e_networth); ?></td>
<?php	if (CLAN_ENABLE) { ?>
    <td class="ac"><?php echo $cnames[$emp->c_id]; ?></td>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <td class="ac"><?php echo $emp->e_score; ?></td>
<?php	} ?>
    <td class="ac"><?php echo $emp->e_kills; ?></td>
    <td class="ac"><?php echo $races[$emp->e_race]; ?></td>
    <td class="ac"><?php echo $eras[$emp->e_era]; ?></td></tr>
<?php
}

$numempires = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0');
$online = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0 AND e_flags & ? != 0', array(EFLAG_ONLINE));
$killed = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = 0 AND e_land = 0');
$abandoned = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = 0 AND e_land > 0');
$disabled = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id > 0 AND e_flags & ? != 0', array(EFLAG_DISABLE));

?>
<h2><?php echo lang('SCORES_HEADER'); ?></h2>
<?php echo $user1->customdate(CUR_TIME); ?><br /><br />
<?php echo lang('TOPEMPIRES_ALIVE'); ?> <span class="cneutral"><?php echo $numempires; ?></span><br />
<?php echo lang('TOPEMPIRES_ONLINE'); ?> <span class="cgood"><?php echo $online; ?></span><br />
<?php echo lang('TOPEMPIRES_DEAD'); ?> <span class="mdead"><?php echo $killed; ?></span> + <span class="mdead"><?php echo $abandoned; ?></span><br />
<?php echo lang('TOPEMPIRES_DISABLED'); ?> <span class="mdisabled"><?php echo $disabled; ?></span><br />
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

for ($topten = 0; $topten < 2; $topten++)	// first pass for 1-10, second pass for 30 surrounding current rank (or just 11-40)
{
	$sql = 'SELECT e_rank,e_name,e_id,e_land,e_networth,c_id,e_race,e_era,e_flags,e_turnsused,e_vacation,e_kills,e_score FROM '. EMPIRE_TABLE .' WHERE u_id > 0 ORDER BY e_rank ASC';
	if ($topten == 0)
		$sql = $db->setLimit($sql, 10);
	else	$sql = $db->setLimit($sql, 100, max(10, $emp1->e_rank - 15));
	$q = $db->query($sql) or warning('Failed to fetch empire list', 0);
	$scores = $q->fetchAll();
	if (count($scores) == 0)
		break;
	scoreHeader($emp1->e_era);
	foreach ($scores as $data)
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
		scoreLine($color, $emp_a);
		$emp_a = NULL;
	}
}
scoreHeader($emp1->e_era);
?>
</table>
<?php
page_footer();
?>
