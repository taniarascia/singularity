<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: main.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'main', 'g_main', 'Empire Summary');
function g_main ()
{
	global $era, $adminflag;
?>
<h2>Empire Summary</h2>
<p>This is the first page you will see each time you login. On it is displayed brief statistics for your empire, any recent events, and when you should expect to receive your next set of turns.</p>
<?php	if (EMPIRES_PER_USER > 1) { ?>
<p>If you have more than one empire registered to your account, a drop-down list will appear at the very top of the page and allow you to select which empire you wish to play with.</p>
<?php	} ?>
<?php	if ($adminflag) { ?>
<p>As a Moderator or Administrator, you will be given additional links at the top of the page to additional pages which will allow you to manage the game more effectively.</p>
<?php	} ?>
<p>In the center of the page, a table will display the following vital statistics of your empire:</p>
<dl>
    <dt><?php echo lang('ROW_TURNS'); ?></dt>
        <dd>The number of turns you currently have available to use (and the maximum you are allowed to accumulate).</dd>
    <dt><?php echo lang('ROW_STOREDTURNS'); ?></dt>
        <dd>The number of turns you have stored in reserve (and the maximum allowed). See the <?php echo guidelink('intro', 'introduction'); ?> for more information.</dd>
    <dt><?php echo lang('ROW_RANK'); ?></dt>
        <dd>Your empire's current rank among all other empires, based on its networth.</dd>
    <dt><?php echo lang($era->peasants); ?></dt>
        <dd>The current population of your empire.</dd>
    <dt><?php echo lang('ROW_LANDACRES'); ?></dt>
        <dd>The current size of your empire.</dd>
    <dt><?php echo lang('ROW_CASH'); ?></dt>
        <dd>The amount of money your empire has on hand, not counting any funds you may have stored in the <?php echo guidelink('bank','World Bank'); ?>.</dd>
    <dt><?php echo lang($era->food); ?></dt>
        <dd>The amount of food your empire has stockpiled. Without food, your citizens and army will starve!</dd>
    <dt><?php echo lang($era->runes); ?></dt>
        <dd>The amount of energy your empire's <?php echo lang($era->trpwiz); ?> have available for casting spells.</dd>
    <dt><?php echo lang('ROW_NETWORTH'); ?></dt>
        <dd>The estimated value of your empire, taking all significant assets into account.</dd>
    <dt><?php echo lang('COLUMN_ERA'); ?></dt>
        <dd>The <?php echo guidelink('eras', 'time period'); ?> during which your empire exists.</dd>
    <dt><?php echo lang('COLUMN_RACE'); ?></dt>
        <dd>The <?php echo guidelink('races', 'race'); ?> of your empire's inhabitants.</dd>
    <dt><?php echo lang('ROW_HEALTH'); ?></dt>
        <dd>The health and happiness of your empire's citizens and army.</dd>
    <dt><?php echo lang('ROW_TAX'); ?></dt>
        <dd>Your empire's tax rate, which influences both the income of your empire's government and the happiness of its citizens.</dd>
    <dt><?php echo lang($era->trparm); ?>, <?php echo lang($era->trplnd); ?>, <?php echo lang($era->trpfly); ?>, <?php echo lang($era->trpsea); ?></dt>
        <dd>The number of units of each type your empire currently employs or maintains in its army.</dd>
    <dt><?php echo lang($era->trpwiz); ?></dt>
        <dd>The number of spellcasters your empire has at its disposal.</dd>
</dl>
<p>Below this table is displayed the game server's current time (in your configured time zone) and the state of the current round (e.g. how long before it starts or ends), as well as the rate at which turns are given out and how long you should expect to wait before receiving additional turns.</p>
<?php	if (CLAN_ENABLE) { ?>
<p>If you are currently in a clan, the latest post in the clan's News thread will be displayed as a reminder.</p>
<?php	} ?>
<p>A Personal Notes section can be found on this page, allowing you to store pieces of information relevant to your gameplay strategy or any tasks you need to perform.</p>
<p>A summary of your <?php echo guidelink('messages', 'mailbox'); ?> is displayed below, indicating how many new messages you have received lately and how many old messages are remaining in your mailbox.</p>
<p>If any other empires have interacted with you recently, whether it be via the public market, your clan, or a rival empire attacking you, a list of events will be displayed. Clicking the "<?php echo lang('MAIN_MARK_NEWS_READ'); ?>" link will dismiss all recent events, and clicking the "<?php echo lang('MAIN_VIEW_NEWS_ARCHIVE'); ?>" link will allow you to re-examine any events which have occurred during the past few days.</p>
<?php
}
?>
