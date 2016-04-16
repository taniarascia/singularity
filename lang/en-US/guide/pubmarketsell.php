<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pubmarketsell.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'pubmarketsell', 'g_pubmarketsell', 'The Public Market - Selling');
function g_pubmarketsell ()
{
	global $era;
?>
<h2>The Public Market - Selling</h2>
<p>While the private market is a convenient place to rid yourself of excess food and equipment, you likely will not be well compensated. Using the public market, you can sell your goods to other empires willing to purchase them, often at much better prices.</p>
<p>Once you place items on the public market, they will take <?php echo PUBMKT_START; ?> hour(s) to reach the marketplace and become available for sale.
<?php	if (PUBMKT_MINTIME >= 0) { ?>
After they have been on sale for at least <?php echo PUBMKT_MINTIME; ?> hours, you will be allowed to remove them at your own discretion.
<?php	} ?>
<?php	if (PUBMKT_MAXTIME >= 0) { ?>
Items are automatically removed from the market and returned to your empire after <?php echo PUBMKT_MAXTIME; ?> hours.
<?php	} ?>
<?php	if ((PUBMKT_MINTIME < 0) && (PUBMKT_MAXTIME < 0)) { ?>
Once placed on the market, items cannot be recalled - they will remain on the market until they are purchased by another empire.
<?php	} ?>
</p>
<p>The market coalition collects a 5% commission on all market sales, subtracted from the amount paid by the buyer. If goods fail to sell on the market and are returned, a percentage of those remaining will be kept by the market coalition as payment for their services.</p>
<p>Be wary about selling during times of war, because the market coalition does not concern itself with inter-empire relations and will gladly sell your goods to your enemies.</p>
<?php
}
?>
