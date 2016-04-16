<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: aid.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if (AID_ENABLE)
	guidepage($topic, 'aid', 'g_aid', 'Sending Foreign Aid');
function g_aid ()
{
	global $era;
?>
<h2>Sending Foreign Aid</h2>
<p>If one of your friends or clanmates is in trouble and needs help, you can send some of your <?php echo lang($era->trpsea); ?> with a shipment of troops and supplies. Up to <?php echo plural(AID_MAXCREDITS, 'SHIPMENTS_SINGLE', 'SHIPMENTS_PLURAL'); ?> can be sent at any given time, and one additional shipment can be sent every <?php echo duration(AID_DELAY); ?>.</p>
<p>In a single aid shipment, you can send up to 20% of your empire's currently available <?php echo lang($era->trparm); ?>, <?php echo lang($era->trplnd); ?>, <?php echo lang($era->trpfly); ?>, <?php echo lang($era->trpsea); ?>, <?php echo lang('ROW_CASH'); ?>, <?php echo lang($era->runes); ?>, and <?php echo lang($era->food); ?> to another empire.</p>
<p>No matter what you wish to send, you must send a minimum number of <?php echo lang($era->trpsea); ?> to deliver your shipment, where the amount is based on the size of your empire. If you specify a number smaller than this amount, any remaining <?php echo lang($era->trpsea); ?> will be automatically returned to you once your shipment is delivered.</p>
<p>Aid can only be sent to those who actually need it - an empire whose networth is significantly greater than yours likely has no need for your goods. However, if you are in a clan, you will be allowed to send goods to clanmates many times your size.</p>
<p>It is not possible to send aid to those you are at war with - the very idea is laughable.</p>
<?php
}
?>
