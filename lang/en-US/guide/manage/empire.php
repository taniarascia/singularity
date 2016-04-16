<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: empire.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'manage/empire', 'g_manage_empire', 'Managing your Empire');
function g_manage_empire ()
{
	global $era;
?>
<h2>Managing your Empire</h2>
<p>From this page, you may change several properties which govern your empire.</p>
<h3>Polymorph</h3>
<p>If you find yourself in a situation where the race of your empire's inhabitants prevents you from reaching a particular goal, you may, as a last resort, change your race. Beware, though, that changing your race will have serious side effects.</p>
<p>In order to polymorph, you must have a sufficient number of <?php echo lang($era->trpwiz); ?>, at least <?php echo TURNS_INITIAL; ?> turns, and your health must be at least 75%.</p>
<p>The amount of energy required to polymorph will kill 10-15% of your population and military, destroy roughly 35% of your structures, and lose at least 15-45% of your stockpiles of <?php echo lang('ROW_CASH'); ?>, <?php echo lang($era->food); ?>, and <?php echo lang($era->runes); ?> amidst the chaos. Finally, <?php echo TURNS_INITIAL; ?> turns will be consumed, and your health will be reduced by 50%.</p>
<p>Note that if you made a mistake while selecting your race during signup, you may perform this procedure <b>for free</b> if you have not yet used any turns.</p>
<h3>Taxes</h3>
<p>Your tax rate determines not only the amount of money your empire's population contributes to you, but also their happiness - low tax rates will increase immigration, while high tax rates will drive your citizens away. Furthermore, raising your tax rate will reduce how far your empire's Health will recover itself.</p>
<h3>Industrial Allocation</h3>
<p>If you have built <?php echo lang($era->bldtrp); ?> on your land, you may use these settings to control what types of units they will produce. When resources are allocated equally between all unit types, <?php echo lang($era->trparm); ?>, <?php echo lang($era->trplnd); ?>, <?php echo lang($era->trpfly); ?>, and <?php echo lang($era->trpsea); ?> will be produced in a 12:6:3:2 ratio.</p>
<p>Specifying percentages whose sum is less than 100% will cause your <?php echo lang($era->bldtrp); ?> to run at partial capacity, which can be useful if your finances are temporarily unable to support additional troops.</p>
<?php	if (VACATION_LIMIT != 0) { ?>
<h3>Vacation</h3>
<p>If, for some reason, you are unable to login for an extended period of time, you may set your empire on Vacation to protect it until you are able to return.</p>
<p>Once you choose to go on vacation, you will be locked out of your account; after <?php echo VACATION_START; ?> hours, your empire will be frozen and no other empires will be allowed to interact with you directly (though you will still share your troops with clanmates, and items already on the market will still be sold).</p>
<p>After your empire has been locked for a minimum of <?php echo VACATION_LIMIT; ?> hours, you will be allowed to login and unlock your empire at your own convenience, at which point you may resume normal gameplay. Do note that once the final week of gameplay for the current round has begun, your empire will be <i>automatically</i> unlocked at the earliest opportunity (so as to prevent players from using vacation to protect their high rank).</p>
<?php	}
}
?>
