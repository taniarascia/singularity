<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: military.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'military', 'g_military', 'Your Army');
function g_military ()
{
	global $era;
?>
<h2>Your Army</h2>
<p>From here, you may deploy your empire's military to attack other empires and steal their land and resources. Once your empire reaches a certain size, this becomes the easiest method of expansion.</p>
<p>In order to attack another empire, it must either be in the same era as yours or a Time Gate must be open between your empires, either opened by you or by your target.</p>
<p>Six different attack methods are available for you to use, each having their own advantages and disadvantages:</p>
<dl>
    <dt><?php echo lang('MILITARY_TYPE_STANDARD'); ?></dt>
        <dd>The standard attack type, this allows sending all types of military units to attack your target. If successful, you will steal a percentage of your target's land, potentially with some of its original structures intact.</dd>
    <dt><?php echo lang('MILITARY_TYPE_SURPRISE'); ?></dt>
        <dd>A surprise attack grants a 25% attack power bonus and allows the attacker to bypass any shared forces the defender's clan may have, though this comes at the cost of increased troop losses for the attacker as well as a significantly higher health loss. If successful, all structures on captured land are destroyed.</dd>
    <dt><?php echo lang('MILITARY_TYPE_ARM'); ?></dt>
        <dd>By sending in only your <?php echo lang($era->trparm); ?>, you can avoid your target's other forces. If successful, all structures on captured land are destroyed.</dd>
    <dt><?php echo lang('MILITARY_TYPE_LND'); ?></dt>
        <dd>By sending in only your <?php echo lang($era->trplnd); ?>, you can not only steal your target's land but also destroy additional structures on the land you do not capture. <?php echo lang($era->blddef); ?> and <?php echo lang($era->bldwiz); ?> are especially vulnerable.</dd>
    <dt><?php echo lang('MILITARY_TYPE_FLY'); ?></dt>
        <dd>By sending in only your <?php echo lang($era->trpfly); ?>, you can not only steal your target's land but also destroy additional structures on the land you do not capture. While attacking from above, significantly more structures can be destroyed, but much fewer will be captured.</dd>
    <dt><?php echo lang('MILITARY_TYPE_SEA'); ?></dt>
        <dd>By sending in only your <?php echo lang($era->trpsea); ?>, you can not only steal your target's land but also destroy additional structures on the land you do not capture. <?php echo lang($era->blddef); ?> and <?php echo lang($era->bldwiz); ?> are especially vulnerable.</dd>
</dl>
<?php
}
?>
