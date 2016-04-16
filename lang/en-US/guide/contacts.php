<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: contacts.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'contacts', 'g_contacts', 'Clan Contacts');
function g_contacts ()
{
?>
<h2>Clan Contacts</h2>
<p>If you're interested in joining a clan or negotiating relations, this page can be used to locate an appropriate official to contact.</p>
<p>Each active clan in the game is listed with its name, title, and the names and ID numbers of the empires currently occupying the positions of Leader, Assistant Leader, and Minister of Foreign Affairs within the clan. Clicking on an empire's name will automatically open your <?php echo guidelink('messages', 'Mailbox'); ?> and allow you to send a message.</p>
<?php
}
?>
