<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: graveyard.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'GRAVEYARD_TITLE';

page_header();

function graveHeader ($color)
{
	global $emp1;
?>
<tr class="era<?php echo $color; ?>">
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
<?php	if (GRAVEYARD_DISCLOSE || ($emp1->e_flags & EFLAG_ADMIN)) { ?>
    <th class="ac"><?php echo lang('COLUMN_USER'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_LAND'); ?></th>
    <th class="ar"><?php echo lang('COLUMN_NETWORTH'); ?></th>
<?php	if (SCORE_ENABLE) { ?>
    <th class="ac"><?php echo lang('COLUMN_SCORE'); ?></th>
<?php	} ?>
<?php	if (CLAN_ENABLE) { ?>
    <th class="ac"><?php echo lang('COLUMN_CLAN'); ?></th>
<?php	} ?>
    <th class="ac"><?php echo lang('COLUMN_RACE'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_ERA'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_DEATH'); ?></th></tr>
<?php
}
function graveLine ($emp, $reason)
{
	global $emp1;
	global $cnames, $races, $eras;
?>
<tr class="mdead">
    <td class="ac"><?php echo $emp; ?></td>
<?php	if (GRAVEYARD_DISCLOSE || ($emp1->e_flags & EFLAG_ADMIN)) { ?>
    <td class="ac"><a href="?location=playerstats&amp;id=<?php echo $emp->u_oldid; ?>" rel="external"><?php echo lang('COMMON_USER_NAMEID', $emp->u_name, prenum($emp->u_oldid)); ?></a></td>
<?php	} ?>
    <td class="ac"><?php echo number($emp->e_land); ?></td>
    <td class="ar"><?php echo money($emp->e_networth); ?></td>
<?php	if (SCORE_ENABLE) { ?>
    <td class="ac"><?php echo $emp->e_score; ?></td>
<?php	} ?>
<?php	if (CLAN_ENABLE) { ?>
    <td class="ac"><?php echo $cnames[$emp->c_oldid]; ?></td>
<?php	} ?>
    <td class="ac"><?php echo $races[$emp->e_race]; ?></td>
    <td class="ac"><?php echo $eras[$emp->e_era]; ?></td>
    <td class="ac"><?php echo $reason; ?></td></tr>
<?php
}
?>
<?php echo lang('GRAVEYARD_HEADER'); ?><br />
<table class="scorestable">
<?php
$q = $db->query('SELECT e_name,e_id,e_land,e_networth,e_score,c_id,c_oldid,e_race,e_era,e_killedby,e_killclan,e_flags,u_oldid,u_name '.
		'FROM '. EMPIRE_TABLE .' e '.
		'LEFT OUTER JOIN '. USER_TABLE .' u ON (e.u_oldid = u.u_id) '.
		'WHERE e.u_id = 0 '.
		'ORDER BY e_networth DESC') or warning('Failed to fetch dead empires', 0);
$graves = $q->fetchAll();
if (count($graves) > 0)
{
	graveHeader($emp1->e_era);
	foreach ($graves as $data)
	{
		$emp_a = new prom_empire($data['e_id']);
		$emp_a->initdata($data);

		if ($emp_a->e_killedby == 0)
			$reason = lang('GRAVEYARD_LABEL_ABANDONED');
		elseif ($emp_a->e_killedby == $emp_a->e_id)
			$reason = lang('GRAVEYARD_LABEL_SUICIDED');
		else
		{
			$emp_b = new prom_empire($emp_a->e_killedby);
			$emp_b->loadPartial();
			if ($emp_a->e_flags & EFLAG_DELETE)
				$reason = lang('GRAVEYARD_LABEL_NUKED', $emp_b);
			elseif (CLAN_ENABLE && $emp_a->e_killclan)
				$reason = lang('GRAVEYARD_LABEL_KILLED_CLAN', $emp_b, $cnames[$emp_a->e_killclan]);
			else	$reason = lang('GRAVEYARD_LABEL_KILLED_NOCLAN', $emp_b);
			$emp_b = NULL;
		}
		graveLine($emp_a, $reason);
		$emp_a = NULL;
	}
	graveHeader($emp1->e_era);
}
?>
</table>
<?php
page_footer();
?>
