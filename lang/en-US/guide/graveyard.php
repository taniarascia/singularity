<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: graveyard.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'graveyard', 'g_graveyard', 'The Graveyard');
function g_graveyard ()
{
?>
<h2>The Graveyard</h2>
<p>Whenever an empire is destroyed or abandoned, it is removed from the Scores list and moved to the Graveyard. Each empire is listed with the following statistics:</p>
<dl>
    <dt><?php echo lang('COLUMN_EMPIRE'); ?></dt>
        <dd>The empire's name and identification number.</dd>
<?php	if (GRAVEYARD_DISCLOSE) { ?>
    <dt><?php echo lang('COLUMN_USER'); ?></dt>
        <dd>The name of the player who controlled the empire before it was deleted.</dd>
<?php	} ?>
    <dt><?php echo lang('COLUMN_LAND'); ?></dt>
        <dd>The total amount of land the empire occupied prior to its deletion. If the empire was destroyed in combat, this number will be 0.</dd>
    <dt><?php echo lang('COLUMN_NETWORTH'); ?></dt>
        <dd>A calculated number indicating the empire's overall value.</dd>
<?php	if (SCORE_ENABLE) { ?>
    <dt><?php echo lang('COLUMN_SCORE'); ?></dt>
        <dd>The empire's final score before its destruction.</dd>
<?php	} ?>
<?php	if (CLAN_ENABLE) { ?>
    <dt><?php echo lang('COLUMN_CLAN'); ?></dt>
        <dd>Any clan the empire was a member of (shown as "<?php echo lang('CLAN_NONE'); ?>" otherwise).</dd>
<?php	} ?>
    <dt><?php echo lang('COLUMN_RACE'); ?></dt>
        <dd>The race of the empire's inhabitants.</dd>
    <dt><?php echo lang('COLUMN_ERA'); ?></dt>
        <dd>The time period in which the empire existed.</dd>
    <dt><?php echo lang('COLUMN_DEATH'); ?></dt>
        <dd>The event which resulted in the empire's deletion - Killed (by another empire), Executed (deleted by an Administrator), <?php echo lang('GRAVEYARD_LABEL_SUICIDED'); ?> (used "Delete Account"), or <?php echo lang('GRAVEYARD_LABEL_ABANDONED'); ?> (failed to login and play over an extended period of time). For empires killed in combat, this will indicate the empire<?php if (CLAN_ENABLE) echo ' (and clan)'; ?> which dealt the final blow.</dd>
</dl>
<?php
}
?>
