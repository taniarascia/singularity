<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: log.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_MOD)
	guidepage($topic, 'admin/log', 'g_admin_log', 'Event Log');
function g_admin_log ()
{
?>
<h2>Event Log</h2>
<p>QM Promisance automatically logs all errors encountered during execution, as well as empire actions (if you have enabled the appropriate configuration setting).</p>
<p>From this section, you may browse through all logged events - if you have been making code changes, this can be an excellent debugging tool.</p>
<p>The inputs at the top of the page allow you to specify filters to restrict what is displayed in the log view as well as choose which columns you wish to display. These filters can be combined in any order and can be removed by clearing them, and they are remembered as long as you remain logged in.</p>
<p>The log should be cleared periodically by pressing the "Clear" button as it can consume a significant amount of disk space, especially if you have extended logging enabled. Only Administrators are permitted to clear the log.</p>
<p>The following event types may be found in the listing:</p>
<dl>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_EVENT'); ?></dt>
        <dd>Normal events performed by users. These are only recorded if the LOG_ENABLE setting has been enabled.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_LOGEVENT'); ?></dt>
        <dd>Events performed by empires marked with the "Logged" flag.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_WARNING'); ?></dt>
        <dd>Run-time warnings issued by PHP.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_NOTICE'); ?></dt>
        <dd>Run-time notices issued by PHP.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_CERROR'); ?></dt>
        <dd>Errors logged by the game itself. Currently, these show up every time a user is given the "Security Violation" page, most often from trying to access an in-game page from a bookmark.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_CWARNING'); ?></dt>
        <dd>Warnings logged by the game itself.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_CNOTICE'); ?></dt>
        <dd>Notices logged by the game itself. Currently, these consist of failed login attempts.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_STRICT'); ?></dt>
        <dd>Run-time notices issued by PHP regarding potential problems with interoperability and compatibility with future versions of PHP.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_RECOVER'); ?></dt>
        <dd>Run-time errors issued by PHP such that the error may have been recoverable. Unless you are actively modifying the game's code, you should never see these.</dd>
    <dt><?php echo lang('ADMIN_LOG_LEVEL_DEPRECATED'); ?></dt>
        <dd>Run-time notices issued by PHP regarding usage of deprecated code.</dd>
</dl>
<p>Errors issued by PHP should never appear unless you have made incorrect modifications to the code or if you are running the game using an unsupported version of PHP.</p>
<?php
}
?>
