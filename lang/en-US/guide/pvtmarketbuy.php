<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pvtmarketbuy.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'pvtmarketbuy', 'g_pvtmarketbuy', 'The Private Market - Buying');
function g_pvtmarketbuy ()
{
	global $era;
?>
<h2>The Private Market - Buying</h2>
<p>Within every empire, there exist those who train mercenaries, build instruments of war, and stockpile food. If your empire's <?php echo lang($era->bldtrp); ?> and <?php echo lang($era->bldfood); ?> do not produce enough to sustain its citizens and army, you may spend your money here to purchase these goods.</p>
<p>Only a finite number of goods can be purchased from the private market in a given time span - once depleted, you will need to wait for more to be produced. The rate at which units and food are replenished is based on both the overall size of your empire and how many <?php echo lang($era->bldcost); ?> and <?php echo lang($era->bldfood); ?> you have, respectively.</p>
<p>The cost of goods on the private market is affected by your economy and your ability to maintain such units - having a large percentage of your empire occupied by <?php echo lang($era->bldcash); ?> and <?php echo lang($era->bldcost); ?> will reduce the purchase prices of military units by up to 40%; however, food prices are not affected.</p>
<?php
}
?>
