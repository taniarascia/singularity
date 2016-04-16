<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: eras.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'eras', 'g_eras', 'Time Periods');
function g_eras ()
{
	global $era;
	global $eras;
?>
<h2>Time Periods</h2>
<p>Empires in this world exist in 3 different time periods. Interacting directly with an empire in another era requires an open Time Gate.</p>
<p>The offensive and defensive power of <?php echo guidelink('units', 'Military Units'); ?> varies with one's era. Additionally, empires in different eras receive the following bonuses and penalties:</p>
<dl>
    <dt>Industry</dt>
        <dd>Your ability to produce military units.</dd>
    <dt>Energy</dt>
        <dd>The rate at which your <?php echo lang($era->trpwiz); ?> produce <?php echo lang($era->runes); ?>.</dd>
    <dt>Exploration</dt>
        <dd>How much land you gain per turn spent <?php echo guidelink('land', 'exploring'); ?>.</dd>
</dl>
<table border="1">
<tr><th>Era</th>
    <th>Industry</th>
    <th>Energy</th>
    <th>Exploration</th></tr>
<?php
	$attribs = array('industry', 'runepro', 'explore');
	foreach ($eras as $eid => $ename)
	{
		$_era = new prom_era($eid);
?>
<tr><th><?php echo $_era; ?></th>
<?php
		foreach ($attribs as $attrib)
		{
			$attrib = 'mod_'. $attrib;
			$val = $_era->$attrib;
?>
    <td><?php echo colornum($val, percent(abs($val)), 'cgood', 'cbad', 'cneutral', '+', '-', '+'); ?></td>
<?php
		}
?></tr>
<?php
	}
?>
</table>
<?php
}
?>
