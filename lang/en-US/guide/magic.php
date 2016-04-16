<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: magic.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'magic', 'g_magic', 'Casting Spells');
function g_magic ()
{
	global $era;
?>
<h2>Casting Spells</h2>
<p>From here, your <?php echo lang($era->trpwiz); ?> can spend <?php echo lang($era->runes); ?> and turns to cast spells, either on your own empire or on your enemies.</p>
<p>Spells cannot be cast if your empire's health is below 20%. Casting an offensive spell reduces health by 4%.</p>
<p>If your empire's magic power is not great enough, high level spells will result in a magical explosion and kill some of your <?php echo lang($era->trpwiz); ?>.</p>
<h3>Offensive Spells</h3>
<dl>
    <dt><?php echo lang($era->spell_spy); ?></dt>
        <dd>If successful, this will allow you to see the vital statistics of your target's empire, just as is shown on your Main Page.</dd>
    <dt><?php echo lang($era->spell_blast); ?></dt>
        <dd>If successful, this will eliminate 3% of your enemy's military and magical forces. If your target has an active <?php echo lang($era->spell_shield); ?>, only 1% will be destroyed.</dd>
    <dt><?php echo lang($era->spell_storm); ?></dt>
        <dd>If successful, this will destroy a percentage of your target's <?php echo lang('ROW_CASH'); ?> and <?php echo lang($era->food); ?>. If shielded, it will only be 33% as effective.</dd>
    <dt><?php echo lang($era->spell_runes); ?></dt>
        <dd>If successful, this will destroy a percentage of your target's <?php echo lang($era->runes); ?>, limiting the ability of their <?php echo lang($era->trpwiz); ?> to cast spells. If shielded, it will only be 33% as effective.</dd>
    <dt><?php echo lang($era->spell_struct); ?></dt>
        <dd>If successful, this will destroy 3% of your enemy's structures. If your target has an active <?php echo lang($era->spell_shield); ?>, only 1% will be destroyed.</dd>
    <dt><?php echo lang($era->spell_fight); ?></dt>
        <dd>This spell will allow your <?php echo lang($era->trpwiz); ?> to battle with your target's magic users. Successful attacks will steal approximately 33% as much land as a standard military attack.</dd>
    <dt><?php echo lang($era->spell_steal); ?></dt>
        <dd>If successful, this will allow you to steal <?php echo lang('ROW_CASH'); ?> from your target empire's treasury. If shielded, it will only be 33% as effective.</dd>
</dl>
<h3>Defensive Spells</h3>
<dl>
    <dt><?php echo lang($era->spell_shield); ?></dt>
        <dd>This will allow you to partially protect your empire from magical attacks from other empires. Casting once will protect your empire for 12 hours, and each additional cast will extend it by 3 hours. If your empire has less than 9 hours of protection remaining, it will automatically be renewed to 12 hours.</dd>
    <dt><?php echo lang($era->spell_food); ?></dt>
        <dd>This spell will create an amount of <?php echo lang($era->food); ?> to feed your citizens and army, the amount being proportional to the size of your empire and the number of <?php echo lang($era->trpwiz); ?> at your disposal.</dd>
    <dt><?php echo lang($era->spell_cash); ?></dt>
        <dd>This spell will create <?php echo lang('ROW_CASH'); ?> to fund your empire, the amount being proportional to the size of your empire and the number of <?php echo lang($era->trpwiz); ?> at your disposal.</dd>
    <dt><?php echo lang($era->spell_gate); ?></dt>
        <dd>This spell will open a Time Gate, allowing your empire to attack other empires in any time period. Casting once will open the gate for 12 hours, and each additional cast will extend it by 3 hours. If your empire's gate has less than 9 hours remaining, it will automatically be renewed to 12 hours.</dd>
    <dt><?php echo lang($era->spell_ungate); ?></dt>
        <dd>If your empire currently has an open Time Gate, this spell will close it, protecting your empire from attacks by other empires in different time periods (unless they themselves have open time gates).</dd>
    <dt><?php echo lang($era->spell_advance); ?></dt>
        <dd>This spell advances your empire from its current era to the next one (if such an era exists). At least <?php echo TURNS_ERA; ?> turns must be spent in your current era before you may cast this spell.</dd>
<?php	if ('MAGIC_ALLOW_REGRESS') { ?>
    <dt><?php echo lang($era->spell_regress); ?></dt>
        <dd>This spell regresses your empire from its current era to the previous one (if such an era exists). At least <?php echo TURNS_ERA; ?> turns must be spent in your current era before you may cast this spell.</dd>
<?php	} ?>
</dl>
<?php
}
?>
