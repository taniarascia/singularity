<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: search.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'search', 'g_search', 'Searching for Empires');
function g_search ()
{
?>
<h2>Searching for Empires</h2>
<p>In a large game, it may be difficult to locate empires to interact with. This page allows you to search for empires based on various criteria. Any of the following filters can be specified:</p>
<dl>
    <dt>Empire ID</dt>
        <dd>Only the empire having this ID number will be displayed in the search results. This filter can be disabled by specifying the number 0.</dd>
    <dt>Empire Name</dt>
        <dd>Only empires whose names contain the specified string will be listed. This filter can be disabled by leaving the search string blank.</dd>
<?php	if (CLAN_ENABLE) { ?>
    <dt>Clan Membership</dt>
        <dd>Only empires belonging to the specified clan will be listed. This filter can be disabled by selecting "Any Clan".</dd>
<?php	} ?>
    <dt>Race</dt>
        <dd>Only empires of the specified race will be listed. This filter can be disabled by selecting "Any Race".</dd>
    <dt>Era</dt>
        <dd>Only empires in the specified era will be listed. This filter can be disabled by selecting "Any Era".</dd>
    <dt>Minimum Networth</dt>
        <dd>If checked, only empires having a networth greater than or equal to this value will be listed.</dd>
    <dt>Maximum Networth</dt>
        <dd>If checked, only empires having a networth less than or equal to this value will be listed.</dd>
    <dt>Exclude Offline Empires</dt>
        <dd>If checked, only empires whose owners are currently logged in will be listed.</dd>
    <dt>Exclude Dead Empires</dt>
        <dd>If checked, only empires having at least one acre of land will be listed.</dd>
</dl>
<p>The resulting empires can be sorted in the following ways:</p>
<dl>
    <dt>Networth</dt>
        <dd>Orders empires by networth, descending.</dd>
    <dt>Land</dt>
        <dd>Orders empires by their size in acres, descending.</dd>
    <dt>Name</dt>
        <dd>Orders empires by their names, alphabetically.</dd>
    <dt>Empire ID</dt>
        <dd>Orders empires by their ID numbers, ascending.</dd>
<?php	if (CLAN_ENABLE) { ?>
    <dt>Clan Membership</dt>
        <dd>Orders empires by their clan affiliation, based on the order in which the clans were founded.</dd>
<?php	} ?>
<?php	if (SCORE_ENABLE) { ?>
    <dt>Score</dt>
        <dd>Orders empires by their current score rating, descending.</dd>
<?php	} ?>
    <dt>Race</dt>
        <dd>Groups empires by their race.</dd>
    <dt>Era</dt>
        <dd>Orders empires by their current era, chronologically.</dd>
</dl>
<p>Search results are displayed in the same basic format as the <?php echo guidelink('scores', 'Scores List'); ?>, but with additional columns to indicate each empire's statistics for offensive and defensive actions.</p>
<p>A maximum of 100 empires can be displayed in the search results.</p>
<?php
}
?>
