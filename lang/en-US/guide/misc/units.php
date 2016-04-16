<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: units.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'units', 'g_units', 'Military Units');
function g_units ()
{
	global $eras, $_era;
	$req = getFormVar('era');
	if (!prom_era::exists($req))
		$req = $_era;
	$era = new prom_era($req);
?>
<h2>Military Units</h2>
<p>Your military serves not only to protect your empire from harm, but to strike out against others who threaten you.</p>
<h3> - <?php
	foreach ($eras as $eid => $ename)
	{
		if ($eid == $req)
			echo guidelink('units', '<b>'. $ename .'</b>', 'era', $eid);
		else	echo guidelink('units', $ename, 'era', $eid);
		echo " - ";
	}
?></h3>
<dl>
    <dt><?php echo lang($era->trparm); ?></dt>
        <dd>The basic military unit. Not the strongest unit, but with a cheaper price tag these can be mobilized in large groups to cause plenty of damage to your enemy.<br />Base cost $<?php echo PVTM_TRPARM; ?>, offensive power <?php echo $era->o_trparm; ?>, defensive power <?php echo $era->d_trparm; ?>.</dd>
    <dt><?php echo lang($era->trplnd); ?></dt>
        <dd>A strong <?php if ($era->o_trplnd > $era->d_trplnd) echo 'offensive'; else echo 'defensive'; ?> unit.  Can be used in attacks to gain land from your enemies.<br />Base cost $<?php echo PVTM_TRPLND; ?>, offensive power <?php echo $era->o_trplnd; ?>, defensive power <?php echo $era->d_trplnd; ?>.</dd>
    <dt><?php echo lang($era->trpfly); ?></dt>
        <dd>An aerial attack is sometimes the best way to go; these can also capture land in special attacks and have an edge in <?php if ($era->o_trpfly > $era->d_trpfly) echo 'offense'; else echo 'defense'; ?>.<br />Base cost $<?php echo PVTM_TRPFLY; ?>, offensive power <?php echo $era->o_trpfly; ?>, defensive power <?php echo $era->d_trpfly; ?>.</dd>
    <dt><?php echo lang($era->trpsea); ?></dt>
        <dd>These are used not only for military purposes, but also to ship foreign aid to other empires.  With both strong offensive and defensive capabilities, it is the most expensive unit, but also the most powerful.<br />Base cost $<?php echo PVTM_TRPSEA; ?>, offensive power <?php echo $era->o_trpsea; ?>, defensive power <?php echo $era->d_trpsea; ?>.</dd>
</dl>
<?php
}
?>
