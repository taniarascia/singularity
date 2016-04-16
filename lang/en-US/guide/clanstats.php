<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clanstats.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'clanstats', 'g_clanstats', 'Clan Statistics');
function g_clanstats ()
{
?>
<h2>Clan Statistics</h2>
<p>This page lists all clans of sufficient size and provides various statistics - the clan's name, its title, how many members it has, the average networth of all members, and the summed networth of all members.</p>
<p>Clicking on a clan's name will list all of its members (using the <?php echo guidelink('search', 'Search'); ?> feature).</p>
<p>The column headers for Members, Average Networth, and Total Networth can be clicked to sort the table descending by the respective attribute.</p>
<p>Additional statistics are listed below this table - the number of clans (and overall percentage) which had too few members to be listed, and the number of empires (and overall percentage) which are not members of a clan.</p>
<?php
}
?>
