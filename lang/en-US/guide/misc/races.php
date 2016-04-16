<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: races.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'races', 'g_races', 'Races');
function g_races ()
{
	global $era;
	global $races;
?>
<h2>Races</h2>
<p>There are many different races in the world, each with their own distinct advantages and disadvantages in the following areas:</p>
<dl>
    <dt>Offense</dt>
        <dd>Your offensive power while attacking other empires.</dd>
    <dt>Defense</dt>
        <dd>Your defensive power when being attacked by other empires.</dd>
    <dt>Building</dt>
        <dd>How quickly you can construct (and demolish) structures.</dd>
    <dt>Upkeep*</dt>
        <dd>The amount of money you must pay for upkeep on your military units.</dd>
    <dt>Magic</dt>
        <dd>Your magical power, used when casting spells and when other empires cast spells on you.</dd>
    <dt>Industry</dt>
        <dd>Your ability to produce military units.</dd>
    <dt>Economy</dt>
        <dd>Your Per Capita Income, how much money your citizens make each turn.</dd>
    <dt>Exploration</dt>
        <dd>How much land you gain per turn spent exploring.</dd>
    <dt>Market*</dt>
        <dd>The prices of military units on the private market.</dd>
    <dt>Consumption*</dt>
        <dd>The amount of <?php echo lang($era->food); ?> your population and military consumes each turn.</dd>
    <dt>Energy</dt>
        <dd>The rate at which your <?php echo lang($era->trpwiz); ?> produce <?php echo lang($era->runes); ?>.</dd>
    <dt>Agriculture</dt>
        <dd>The rate at which your <?php echo lang($era->bldfood); ?> produce <?php echo lang($era->food); ?>.</dd>
</dl>
<table border="1">
<tr><th>Race</th>
    <th>Offense</th>
    <th>Defense</th>
    <th>Building</th>
    <th>Upkeep*</th>
    <th>Magic</th>
    <th>Industry</th>
    <th>Economy</th>
    <th>Exploration</th>
    <th>Market*</th>
    <th>Consumption*</th>
    <th>Energy</th>
    <th>Agriculture</th></tr>
<?php
	$attribs = array('offense', 'defense', 'buildrate', 'expenses', 'magic', 'industry', 'income', 'explore', 'market', 'foodcon', 'runepro', 'foodpro');
	foreach ($races as $rid => $rname)
	{
		$race = new prom_race($rid);
?>
<tr><th><?php echo $race; ?></th>
<?php
		foreach ($attribs as $attrib)
		{
			$attrib = 'mod_'. $attrib;
			$val = $race->$attrib;
?>
    <td><?php echo colornum($val, percent(abs($val)), 'cgood', 'cbad', 'cneutral', '+', '-', '+'); ?></td>
<?php
		}
?></tr>
<?php
	}
?>
</table>
<p>For all of the above values, a positive percentage works to your empire's advantage while a negative percentage acts as a penalty. For attributes noted with a "*", this may seem backwards - for example, a food consumption penalty (negative) will <i>increase</i> how much food your units require, while an upkeep bonus (positive) will <i>decrease</i> your expenses.</p>
<?php
}
?>
