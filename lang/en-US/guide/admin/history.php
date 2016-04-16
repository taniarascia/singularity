<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: history.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_MOD)
	guidepage($topic, 'admin/history', 'g_admin_history', 'Manage History');
function g_admin_history ()
{
?>
<h2>Round History Management</h2>
<p>This page allows Administrators and Moderators to view a list of all game rounds recorded in the game's History and to edit various details.</p>
<p>A table lists several properties for all recorded rounds:</p>
<dl>
    <dt><?php echo lang('ADMIN_HISTORY_COLUMN_ID'); ?></dt>
        <dd>The round history record's ID number.</dd>
    <dt><?php echo lang('ADMIN_HISTORY_COLUMN_NAME'); ?></dt>
        <dd>The name this game bore when the round took place.</dd>
    <dt><?php echo lang('ADMIN_HISTORY_COLUMN_DESC'); ?></dt>
        <dd>A brief description of the round. Blank by default, this page exists so you can specify it.</dd>
    <dt><?php echo lang('ADMIN_HISTORY_COLUMN_START'); ?></dt>
        <dd>The date the round started.</dd>
    <dt><?php echo lang('ADMIN_HISTORY_COLUMN_STOP'); ?></dt>
        <dd>The date the round ended.</dd>
</dl>
<p>Clicking on a round's ID number will allow editing the details of the history record and viewing statistics. Most fields listed here are self-explanatory.</p>
<p>If the game was configured to use Clans during the round selected, you may change the threshold below which clans are excluded from Top Clans for being too small.</p>
<p>If a round was reset by mistake and an unwanted history record was saved, Administrators may delete the history record from the database. Note that this option is irreversible - once you press the History Eraser Button, there is no turning back.</p>
<p>After the current round has ended, Administrators may open this page to save a snapshot of the end of the round to the game's History. In the process, all killed or deleted empires will be moved to the Graveyard and the rankings of all surviving empires will be updated.<br />
Note that this will also update the ranking statistics for each user account which participated in the current round, so history should <b>never</b> be recorded twice during the same round.</p>
<?php
}
?>
