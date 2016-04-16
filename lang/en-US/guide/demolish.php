<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: demolish.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'demolish', 'g_demolish', 'Demolition');
function g_demolish ()
{
?>
<h2>Demolition</h2>
<p>From time to time, an empire may need to shift its focus to better cope with its situation - from here, you may spend time demolishing structures to make room for new structures to build.</p>
<p>Demolishing structures is slower than building (due to the difficulty of disposing of the remains), but some of the original costs can be recovered.</p>
<p>Unused acres of land can also be dropped from your empire, though the need to relocate citizens and troops, not to mention the paperwork, makes this a slow process.</p>
<?php
}
?>
