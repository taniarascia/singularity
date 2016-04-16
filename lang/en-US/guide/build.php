<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: build.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'build', 'g_build', 'Construction');
function g_build ()
{
?>
<h2>Construction</h2>
<p>Without buildings, organization is impossible - from here, you may spend time and money building structures to strengthen your empire.</p>
<p>As the size of your empire increases you will be able to build structures more quickly, but the cost of building will also increase due to the difficulty of transporting materials.</p>
<p>For a description of what each building does, see <?php echo guidelink('buildings', 'Structures'); ?>.</p>
<?php
}
?>
