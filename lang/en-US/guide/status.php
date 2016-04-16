<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: status.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'status', 'g_status', 'Detailed Status');
function g_status ()
{
	global $era;
?>
<h2>Detailed Status</h2>
<p>This page provides detailed statistics about your empire and its economy, divided into 6 overall sections:</p>
<h3><?php echo lang('STATUS_EMPIRE_HEADER'); ?></h3>
<dl>
    <dt><?php echo lang('ROW_TURNSUSED'); ?></dt>
        <dd>This is the number of turns you have used since your empire was first created.</dd>
    <dt><?php echo lang('ROW_CASH'); ?></dt>
        <dd>Funds your empire has available on-hand.</dd>
    <dt><?php echo lang('ROW_RANK'); ?></dt>
        <dd>Your empire's rank, determined by its networth, compared to other empires in the game.</dd>
    <dt><?php echo lang('ROW_NETWORTH'); ?></dt>
        <dd>Your empire's networth is calculated based on all of its available assets - acres of land, cash, <?php echo lang($era->food); ?>, your citizens, and your military - and provides a rough indication of how much your empire is worth.</dd>
    <dt><?php echo lang('ROW_POPULATION'); ?></dt>
        <dd>This is the number of <?php echo lang($era->peasants); ?> that live in your empire.  <?php echo lang($era->peasants); ?> are necessary for making money to finance your empire.</dd>
    <dt><?php echo lang('ROW_RACE'); ?></dt>
        <dd>The <?php echo guidelink('races', 'race'); ?> of your empire's inhabitants.</dd>
    <dt><?php echo lang('ROW_ERA'); ?></dt>
        <dd>The <?php echo guidelink('eras', 'time period'); ?> during which your empire exists.</dd>
</dl>
<h3><?php echo lang('STATUS_FOOD_HEADER'); ?></h3>
<dl>
    <dt><?php echo lang('STATUS_FOOD_PRODUCE'); ?></dt>
        <dd><?php echo lang($era->bldfood); ?> and unused land both help to produce <?php echo lang($era->food); ?> with which to feed your citizens and army. This number indicates approximately how much they will produce each turn.</dd>
    <dt><?php echo lang('STATUS_FOOD_CONSUME'); ?></dt>
        <dd>Your military, <?php echo lang($era->peasants); ?>, and <?php echo lang($era->trpwiz); ?> all require <?php echo lang($era->food); ?> to survive. This number shows your estimated consumption per turn.</dd>
    <dt><?php echo lang('STATUS_FOOD_NET'); ?></dt>
        <dd>This number indicates whether you are gaining or losing <?php echo lang($era->food); ?> overall per turn. It is usually a good idea to keep an eye on this number, lest you run out and your people starve.</dd>
</dl>
<h3><?php echo lang('STATUS_FOREIGN_HEADER'); ?></h3>
<dl>
<?php	if (CLAN_ENABLE) { ?>
    <dt><?php echo lang('STATUS_FOREIGN_CLAN'); ?></dt>
        <dd>If you are in a clan, its name is indicated here.  If you are independent, this will simply say '<?php echo lang('CLAN_NONE'); ?>'.</dd>
    <dt><?php echo lang('STATUS_FOREIGN_ALLIES'); ?></dt>
        <dd>If you are in a clan, other clans which you are allied with will be listed here.</dd>
    <dt><?php echo lang('STATUS_FOREIGN_WARS'); ?></dt>
        <dd>If you are in a clan, clans you are at war with are listed here.</dd>
<?php	} ?>
    <dt><?php echo lang('STATUS_FOREIGN_OFFENSE'); ?></dt>
        <dd>Indicates how many times you have attacked other empires, as well as the percentage of successful attacks (in parentheses).</dd>
    <dt><?php echo lang('STATUS_FOREIGN_DEFENSE'); ?></dt>
        <dd>Indicates how many times your empire has been attacked by others, as well as the percentage of attacks that have been successfully resisted (in parentheses).</dd>
    <dt><?php echo lang('STATUS_FOREIGN_KILLS'); ?></dt>
        <dd>This indicates the number of empires you have destroyed.</dd>
</dl>
<h3><?php echo lang('STATUS_LAND_HEADER'); ?></h3>
<p>Each row in this table indicates the number of each type of structure your empire has built on its land, as well as how many acres are currently unused.</p>
<h3><?php echo lang('STATUS_CASH_HEADER'); ?></h3>
<dl>
    <dt><?php echo lang('STATUS_CASH_PERCAP'); ?></dt>
        <dd>This is your empire's per capita income, indicating how much money each of its <?php echo lang($era->peasants); ?> make each turn.  A percentage of this income is gained based on tax rate.</dd>
    <dt><?php echo lang('STATUS_CASH_INCOME'); ?></dt>
        <dd>Your empire's income is determined by the number of <?php echo lang($era->peasants); ?> it has, its per capita income, its tax rate, and its overall health.</dd>
    <dt><?php echo lang('STATUS_CASH_EXPENSE'); ?></dt>
        <dd>Your empire's expenses consist mainly of military upkeep and land taxes. <?php echo lang($era->bldcost); ?> can help to lower these expenses.</dd>
    <dt><?php echo lang('STATUS_CASH_LOANPAY'); ?></dt>
        <dd>If you have borrowed any money from the World Bank, 0.5% of your loan is paid off each turn.  Your loan payment for the next turn you take is indicated here.</dd>
    <dt><?php echo lang('STATUS_CASH_NET'); ?></dt>
        <dd>This indicates your empire's net income, whether it is gaining or losing money overall each turn.  It is highly recommended to keep an eye on this value, lest you run out of money and are forced to take out a potentially expensive loan.</dd>
    <dt><?php echo lang('STATUS_CASH_SAVINGS'); ?></dt>
        <dd>This indicates how much money your empire currently has saved in the World Bank. Your account's interest rate is indicated in parentheses.</dd>
    <dt><?php echo lang('STATUS_CASH_LOAN'); ?></dt>
        <dd>Here is indicated the amount of money your empire currently owes to the World Bank. The loan's interest rate is shown in parentheses.</dd>
</dl>
<h3><?php echo lang('STATUS_MILITARY_HEADER'); ?></h3>
<p>The top rows indicate how many of each unit your empire currently has in its army.</p>
<dl>
    <dt><?php echo lang('STATUS_MILITARY_OFFPOWER'); ?></dt>
        <dd>This number indicates your empire's total calculated offensive power (see <?php echo guidelink('military', 'Military Units'); ?> for more information).</dd>
    <dt><?php echo lang('STATUS_MILITARY_DEFPOWER'); ?></dt>
        <dd>Your empire's total calculated defensive power is shown here.</dd>
</dl>
<?php
}
?>
