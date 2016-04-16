<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: farm.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'farm', 'g_farm', 'Agriculture Focus');
function g_farm ()
{
	global $era;
?>
<h2>Agriculture Focus</h2>
<p>Here you may choose to spend time with extra focus placed on producing <?php echo lang($era->food); ?> to sustain your citizens and military.</p>
<p>While spending turns here, your empire's gross production of <?php echo lang($era->food); ?> will increase by 25%.</p>
<?php
}
?>
