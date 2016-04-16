<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: news.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'news', 'g_news', 'News Search');
function g_news ()
{
?>
<h2>News Search</h2>
<p>Events which happen to your empire are reported on your Empire Summary page; events which happen to others can be found at this location. Here you can search for events perpetrated by or targetted at a specific empire or clan.</p>
<dl>
    <dt>Search by</dt>
        <dd>This allows you to specify whether the criteria selected below should be filtered as the attacker, defender, or either.</dd>
    <dt>Empire ID</dt>
        <dd>Specify an empire number here to list events involving that empire. If left blank, all recent events will be listed.</dd>
    <dt>Clan</dt>
        <dd>Select a clan tag here to list events involving empires when they were in a particular clan. Even if an empire leaves one clan (and optionally joins another one), its clan at the time of the event will be listed.</dd>
</dl>
<p>Events are sorted descending by the date on which they happened. A maximum of 100 events will be listed in any search.</p>
<?php
}
?>
