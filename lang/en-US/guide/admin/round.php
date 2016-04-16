<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: round.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_ADMIN)
	guidepage($topic, 'admin/round', 'g_admin_round', 'Round Settings');
function g_admin_round ()
{
?>
<h2>Round Settings</h2>
<p>This page allows Administrators to modify the current round's start date, cooldown date (at which point various things such as signup and vacation are disallowed), and ending date.</p>
<p>From here, Administrators can also reset the game to begin a new round, automatically creating new empires for selected Administrators and Moderators. Simply mark the checkboxes of the accounts for which you wish to create empires, then specify empire names in the matching text boxes.</p>
<p>When resetting the game, all users other than yourself will be logged out, and your login session will be automatically rebound to your newly created empire. If you do not create an empire for yourself, your session will be temporarily bound to the highest numbered account created during the reset.</p>
<?php
}
?>
