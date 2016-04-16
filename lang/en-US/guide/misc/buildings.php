<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: buildings.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'buildings', 'g_buildings', 'Structures');
function g_buildings ()
{
	global $era;
?>
<h2>Structures</h2>
<p>Your empire's land can be allocated for the following purposes:</p>
<dl>
    <dt><?php echo lang($era->bldpop); ?></dt>
        <dd>While <?php echo lang($era->peasants); ?> will live on unused land, these are specifically designed for housing. As a result, they allow you to house a great deal more <?php echo lang($era->peasants); ?> than otherwise.</dd>
    <dt><?php echo lang($era->bldcash); ?></dt>
        <dd>These allow your empire's economy to grow, helping to increase your Per Capita Income, as well as directly producing money themselves.</dd>
    <dt><?php echo lang($era->bldtrp); ?></dt>
        <dd>These produce your military units; the percentage of resources allocated to each unit type produced is controlled through <?php echo guidelink('manage/empire', 'Empire Management'); ?>.</dd>
    <dt><?php echo lang($era->bldcost); ?></dt>
        <dd>These allow you to reduce your military expenses by more efficiently housing your units. They will also lower the price of all military units purchased from the Private Market. These also increase the rate at which your Private Market refills.</dd>
    <dt><?php echo lang($era->bldwiz); ?></dt>
        <dd>These serve to train and house <?php echo lang($era->trpwiz); ?>, as well as produce <?php echo lang($era->runes); ?> with which they may cast their spells.</dd>
    <dt><?php echo lang($era->bldfood); ?></dt>
        <dd>These are vital for feeding your <?php echo lang($era->peasants); ?> and military; without food, your population and army will starve and desert your empire.</dd>
    <dt><?php echo lang($era->blddef); ?></dt>
        <dd>These are a strictly defensive building, worth up to 450 defense points each. In order to provide maximum defense, each must be occupied by at least 150 <?php echo lang($era->trparm); ?>.</dd>
</dl>
<?php
}
?>
