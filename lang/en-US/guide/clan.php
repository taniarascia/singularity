<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clan.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if (CLAN_ENABLE)
	guidepage($topic, 'clan', 'g_clan', 'Clans');
function g_clan()
{
	global $era;
?>
<h2>Clans</h2>
<p>If you wish to ally together with a group of friends, you may form a Clan. Being in a clan offers several advantages; however, there are also downsides.</p>
<p>Clan members can selectively deploy 10% of their troops for the sole purpose of defending other clan members who also elect to share theirs - in doing so, clan members can significantly boost their defensive power against attacking empires. Beware - an enemy engaging in a <?php echo lang('MILITARY_TYPE_SURPRISE'); ?> will be able to bypass these extra defenses, including the 10% you shared yourself!</p>
<p>Your clan's leaders can choose to form strategic alliances with other clans, allowing clan members to send an unlimited number of aid shipments to their allies as well as send goods to empires significantly larger than themselves.</p>
<p>However, being in a clan also opens the possibility of war, allowing members of enemy clans to attack you mercilessly (and also allowing your clan to do the same).</p>
<h3>Creating and Joining</h3>
<p>When you wish to create a clan, simply enter a short name (no longer than 8 characters) and specify a password which other players must enter in order to join.</p>
<p>If you wish to join a clan which has already been created and you have been given its password, simply select the clan from the dropdown list and enter the password in the space provided.</p>
<p>If you have received an invitation to join a clan, simply locate it in the Invitations table and click the corresponding join button.</p>
<?php
}
?>
