<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pvtmarketsell.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'pvtmarketsell', 'g_pvtmarketsell', 'The Private Market - Selling');
function g_pvtmarketsell ()
{
	global $era;
?>
<h2>The Private Market - Selling</h2>
<p>Just as you can purchase from the private market, you can also quickly sell your excess goods to it, though for significantly lower returns.</p>
<p>As is the case with buying, having a large percentage of your empire occupied by <?php echo lang($era->bldcash); ?> and <?php echo lang($era->bldcost); ?> will increase the selling prices of military units to up to 50% of their usual purchase price.</p>
<p>A maximum of <?php echo PVTM_MAXSELL/100; ?>% of each type of military unit can be sold on the private market in a given time span - in order to sell more, you must wait a while.</p>
<?php
}
?>
