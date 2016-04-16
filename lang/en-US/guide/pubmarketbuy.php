<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: pubmarketbuy.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

guidepage($topic, 'pubmarketbuy', 'g_pubmarketbuy', 'The Public Market - Buying');
function g_pubmarketbuy ()
{
	global $era;
?>
<h2>The Public Market - Buying</h2>
<p>While the private market is convenient and reliable, its prices may be prohibitive if your empire does not focus strongly on its economy. Goods can be purchased from other empires via the public market, often at much better prices.</p>
<p>Of all goods available on the public market, the least expensive will be shown to you. The identities of the empires you are purchasing from are not revealed to you, nor is your identity revealed to them.</p>
<p>If less expensive goods become available while you are browsing, you will automatically purchase as many as possible at the reduced price before buying the rest at the price you originally were quoted. If somebody else buys goods while you are browsing, you will only buy what remains at the price you requested - if you require more units, you can purchase them separately at a higher price.</p>
<p>When browsing, your own goods will never be listed for sale to you.</p>
<?php
}
?>
