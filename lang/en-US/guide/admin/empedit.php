<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: empedit.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_ADMIN)
	guidepage($topic, 'admin/empedit', 'g_admin_empedit', 'Modify Empire');
function g_admin_empedit ()
{
?>
<h2>Modify Empire</h2>
<p>This page allows Administrators to make arbitrary changes to empires for testing purposes.</p>
<p>All numeric input fields are relative - a positive number increases the field's value, a negative number decreases it, and zero leaves it unchanged.</p>
<?php
}
?>
