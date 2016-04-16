<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: playerstats.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

function scoreHeader ($round)
{
?>
<tr class="era0">
    <th class="ar"><?php echo lang('COLUMN_RANK'); ?></th>
    <th class="ac"><?php echo lang('COLUMN_EMPIRE'); ?></th>
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

$playernum = fixInputNum(getFormVar('id', 0));
if (!$playernum)
{
	error_404('ERROR_TITLE', lang('PLAYERSTATS_NO_SUCH_USER'));

	$html->end();
	exit;
}

$user_a = new prom_user($playernum);
if (!$user_a->load())
{
	error_404('ERROR_TITLE', lang('PLAYERSTATS_NO_SUCH_USER'));

	$html->end();
	exit;
}
	
$html = new prom_html_compact();
$html->begin('PLAYERSTATS_TITLE');

if ($user_a->u_flags & UFLAG_ADMIN)
	echo '<p class="madmin">'. lang('PLAYERSTATS_IS_ADMIN', $user_a) .'</p>';
elseif ($user_a->u_flags & UFLAG_MOD)
	echo '<p class="madmin">'. lang('PLAYERSTATS_IS_MOD', $user_a) .'</p>';
elseif ($user_a->u_flags & UFLAG_DISABLE)
	echo '<p class="mdisabled">'. lang('PLAYERSTATS_IS_DISABLED', $user_a) .'</p>';
elseif ($user_a->u_flags & UFLAG_CLOSED)
	echo '<p class="mdead">'. lang('PLAYERSTATS_IS_CLOSED', $user_a) .'</p>';

if ($user_a->u_numplays == 0)
	echo '<p>'. lang('PLAYERSTATS_NO_PLAYS', $user_a, gmdate(lang('COMMON_TIME_FORMAT'), $user_a->u_createdate)) .'</p>';
else
{
	echo '<p>'. lang('PLAYERSTATS_PLAY_SUMMARY', $user_a, gmdate(lang('COMMON_TIME_FORMAT'), $user_a->u_createdate), $user_a->u_numplays, percent($user_a->u_sucplays / max($user_a->u_numplays, 1) * 100)) .'</p>';
	echo '<p>'. lang('PLAYERSTATS_PLAY_ACTIONS', number($user_a->u_kills), number($user_a->u_deaths), number($user_a->u_offtotal), percent($user_a->u_offsucc / max($user_a->u_offtotal, 1) * 100), number($user_a->u_deftotal), percent($user_a->u_defsucc / max($user_a->u_deftotal, 1) * 100)) .'</p>';
	echo '<p>'. lang('PLAYERSTATS_PLAY_RANKS', percent($user_a->u_bestrank * 100, 2), percent($user_a->u_avgrank * 100, 2)) .'</p>';
}

if ($db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id = ?', array($user_a->u_id)) > 0)
	echo '<p class="cneutral">'. lang('PLAYERSTATS_CURRENTLY_PLAYING') .'</p>';
elseif ($db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_oldid = ?', array($user_a->u_id)) > 0)
	echo '<p class="cwarn">'. lang('PLAYERSTATS_CURRENTLY_DEAD') .'</p>';

$q = $db->prepare('SELECT * FROM '. HISTORY_ROUND_TABLE .' WHERE hr_id IN (SELECT hr_id FROM '. HISTORY_EMPIRE_TABLE .' WHERE u_id = ?) ORDER BY hr_id ASC');
$q->bindIntValue(1, $user_a->u_id);
$q->execute();
$rounds = $q->fetchAll();

if ($user_a->u_numplays == 0)
	; // skip section if it's their first round
elseif (count($rounds) == 0)
	echo '<p>'. lang('PLAYERSTATS_NO_HISTORY') .'</p>';
else
{
	echo '<p>'. lang('PLAYERSTATS_HISTORY_HEADER') .'</p>';
	foreach ($rounds as $round)
	{
?>
<h3><a href="?location=history&amp;round=<?php echo $round['hr_id']; ?>"><?php echo lang('PLAYERSTATS_HISTORY_LABEL', $round['hr_name'], gmdate('F j, Y', $round['hr_startdate']), gmdate('F j, Y', $round['hr_stopdate'])); ?></a></h3>
<table class="scorestable">
<?php
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
		$q = $db->prepare('SELECT * FROM '. HISTORY_EMPIRE_TABLE .' WHERE hr_id = ? AND u_id = ? ORDER BY he_rank ASC');
		$q->bindIntValue(1, $round['hr_id']);
		$q->bindIntValue(2, $user_a->u_id);
		$q->execute() or warning('Failed to retrieve historic empire data', 0);
		scoreHeader($round);
		foreach ($q as $empire)
			scoreLine($round, $empire, $cnames);		
		scoreHeader($round);
?>
</table>
<?php
	}
}
$html->end();
?>
