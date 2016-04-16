<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: market.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_ADMIN)
	guidepage($topic, 'admin/market', 'g_admin_market', 'Market Cleanup');
function g_admin_market ()
{
?>
<h2>Market Cleanup</h2>
<p>Though this version of QM Promisance is less susceptible to players flooding the market with small sets of items to inconvenience others trying to make purchases, such actions can still result in degraded performance. From this page, it is possible to view all items currently for sale on the Public Market and remove any selected items.</p>
<p>Simply select the items which you wish to remove from the market (or click the "<?php echo lang('ADMIN_MARKET_COLUMN_TOGGLE'); ?>" link to quickly toggle every item in the current page) and press the "<?php echo lang('ADMIN_MARKET_SUBMIT'); ?>" button to remove the items from the market.</p>
<p>The "Return items" option allows choosing what happens to the items being removed:</p>
<dl>
    <dt><?php echo lang('ADMIN_MARKET_RETURN_NONE'); ?></dt>
        <dd>Destroys all of the items selected without any notification to the empire who originally put them on sale.</dd>
    <dt><?php echo lang('ADMIN_MARKET_RETURN_SOME'); ?></dt>
        <dd>Returns a percentage of unsold items to the empire who sold them, the amount determined the same way as when items are removed from the market by other means (and updating the lottery accordingly).</dd>
    <dt><?php echo lang('ADMIN_MARKET_RETURN_ALL'); ?></dt>
        <dd>Returns all items to the seller at no penalty.</dd>
</dl>
<?php
}
?>
