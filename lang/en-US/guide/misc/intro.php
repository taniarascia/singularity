<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: intro.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'intro', 'g_intro', 'Introduction');
function g_intro ()
{
	global $era;
?>
<h2>Introduction</h2>
<p>As leader of a newly founded empire, your goal is to become supreme to all others. Using everything from diplomacy to war, you must strive to build an empire wealthier than all others. Through this all, you will compete against anywhere from hundreds to thousands of other players, all aiming to achieve the same goals.</p>
<h2>About Turn-Based Games</h2>
<p>QM Promisance is a turn-based game - that is, there is a limit to how often your empire can advance itself or interact with others.</p>
<p>In <?php echo GAME_TITLE; ?>, you receive <?php echo TURNS_COUNT; ?> of these turns every <?php echo TURNS_FREQ; ?> minutes. You can only accumulate <?php echo TURNS_MAXIMUM; ?> turns at once - if you receive more, they will go into your Stored Turns, of which you may have up to <?php echo TURNS_STORED; ?>; every time you receive additional turns, 1 of your stored turns will be released for you to use.</p>
<p>All of the actions listed in the "Use Turns" section of the side menu will consume a variable number of turns when you perform them - the <?php echo guidelink('cash', 'Cash'); ?>, <?php echo guidelink('farm', 'Farm'); ?>, and <?php echo guidelink('land', 'Explore'); ?> options allow you to specify how much time to spend, while <?php echo guidelink('build', 'Build'); ?> and <?php echo guidelink('demolish', 'Demolish'); ?> consume turns based on how quickly your empire can perform these actions.</p>
<p><?php echo guidelink('military', 'Attacking'); ?> an empire, sending <?php echo guidelink('aid', 'aid'); ?>, or casting a <?php echo guidelink('magic', 'spell'); ?> (whether on yourself or on another empire) consumes <b>2</b> turns.</p>
<p>Other actions, such as participating in the <?php echo guidelink('pvtmarketbuy', 'private'); ?> or <?php echo guidelink('pubmarketbuy', 'public'); ?> market, joining or managing a <?php echo guidelink('clan', 'clan'); ?>, <?php echo guidelink('manage/empire', 'managing your empire'); ?>, or sending <?php echo guidelink('messages', 'messages'); ?> to other empires, do not consume any turns.</p>
<p>Each time you take a turn, your empire will collect taxes from its citizens and harvest food. Tax revenue will then be spent maintaining your empire and its military, and food will be used to feed your population. Be careful to not run out of money or food - while the <?php echo guidelink('bank', 'World Bank'); ?> can help you with the former, the latter can have disastrous results.</p>
<p>When your empire is first created, it is placed under protection so that other empires cannot harm it during its initial stages of development. During this period of protection, your empire also may not attack others, send foreign aid, or participate on the Public Market. Once you have used <?php echo TURNS_PROTECTION; ?> turns, protection will be lifted and your empire will be exposed to the rest of the world. Once the end of the round draws near, however, this protection will be removed.</p>
<h2>The Status Bar</h2>
<p>At the top and bottom of every page is your Status Bar. This allows you to quickly check the crucial statistics of your empire:</p>
<dl>
    <dt><?php echo lang('STATBAR_MAILBOX'); ?></dt>
        <dd>A shortcut link to <?php echo guidelink('messages', 'Your Mailbox'); ?> - the text of this link will change to "<b><?php echo lang('STATBAR_NEWMAIL'); ?></b>" if you have unread messages waiting.</dd>
    <dt><?php echo lang('ROW_TURNS'); ?></dt>
        <dd>How many turns you have available to use.</dd>
    <dt><?php echo lang('ROW_CASH'); ?></dt>
        <dd>The amount of money your empire has on hand, not counting any funds you may have stored in the <?php echo guidelink('bank','World Bank'); ?>.</dd>
    <dt><?php echo lang('ROW_LAND'); ?></dt>
        <dd>The current size of your empire.</dd>
    <dt><?php echo lang($era->runes); ?></dt>
        <dd>The amount of energy your empire's <?php echo lang($era->trpwiz); ?> have available for casting spells.</dd>
    <dt><?php echo lang($era->food); ?></dt>
        <dd>The amount of food your empire has stockpiled.</dd>
    <dt><?php echo lang('ROW_HEALTH'); ?></dt>
        <dd>The health and happiness of your empire's citizens and army.</dd>
    <dt><?php echo lang('ROW_NETWORTH'); ?></dt>
        <dd>The estimated value of your empire, taking all significant assets into account.</dd>
</dl>
<?php
}
?>
