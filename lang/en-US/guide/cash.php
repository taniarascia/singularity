<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: cash.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'cash', 'g_cash', 'Economic Focus');
function g_cash ()
{
?>
<h2>Economic Focus</h2>
<p>Here you may choose to spend time with extra focus placed on your empire's economy.</p>
<p>While spending turns here, your empire's gross income will increase by 25%.</p>
<?php
}
?>
