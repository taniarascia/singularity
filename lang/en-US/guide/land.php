<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: land.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'land', 'g_land', 'Exploration');
function g_land ()
{
?>
<h2>Exploration</h2>
<p>For small empires, attacking others to gain land is infeasible - their time is better spent exploring.</p>
<p>While spending turns here, your empire will expand its borders and gain additional land.</p>
<p>Be warned - as your empire grows larger, it will become more and more difficult to find usable land, at which point you will have to resort to <?php echo guidelink('military', 'attacking'); ?> other empires to steal their land.</p>
<?php
}
?>
