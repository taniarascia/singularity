<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: permissions.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if ($adminflag & UFLAG_ADMIN)
	guidepage($topic, 'admin/permissions', 'g_admin_permissions', 'Permissions');
function g_admin_permissions ()
{
?>
<h2>Game Permissions</h2>
<p>This page allows Administrators to explicitly deny access to the game from specific IP addresses, or to prevent certain email addresses from being used to create new accounts. Active permission entries are listed in a table at the top of the page:</p>
<dl>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_TYPE'); ?></dt>
        <dd>This indicates whether the permission entry is for an IP address or email address, as well as whether it is a ban or an exception.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_CRITERIA'); ?></dt>
        <dd>This shows what exactly is being banned. For IPv4 addresses this is an address/mask pair, for IPv6 this is a CIDR block, and for email addresses it is the email address wildcard mask.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_CREATED'); ?></dt>
        <dd>Indicates when the permission entry was first added.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_UPDATED'); ?></dt>
        <dd>Indicates when the permission entry was last updated.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_LASTHIT'); ?></dt>
        <dd>Indicates the last time the permission entry was actively enforced. This can be used to indicate when it is safe to prune unneeded bans or exceptions from the database.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_HITCOUNT'); ?></dt>
        <dd>For bans only, indicates how many times the ban has been actively enforced.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_EXPIRE'); ?></dt>
        <dd>Indicates when the permission entry expires.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_COMMENT'); ?></dt>
        <dd>Displays the private comment entered when the permission entry was added.</dd>
    <dt><?php echo lang('ADMIN_PERMISSIONS_COLUMN_REASON'); ?></dt>
        <dd>For bans only, displays the public reason for why the ban was placed.</dd>
</dl>
<p>New permission entries can be added using the form at the bottom of the page:</p>
<dl>
    <dt>Type</dt>
        <dd>Selects whether the entry will cover IP addresses or email addresses, as well as whether it is intended to be a ban or an exception. By default, newly added permissions will be IP address bans.</dd>
    <dt>Criteria</dt>
        <dd>For IPv4 addresses, the following formats are accepted: Single Address (e.g. "192.0.2.1"), Wildcard (e.g. "192.0.2.*", "192.0.*.*", or "192.*.*.*"), CIDR block (e.g. "192.0.2.0/24"), and Address/Mask (e.g. "192.0.2.0/255.255.255.0").<br />
            For IPv6 addresses, specify an address or a CIDR block. Both expanded (e.g. "2001:0db8:85a3:08d3:1319:8a2e:0370:7348" or "2001:0db8:85a3:08d3:1319:8a2e:0000:0000/96") and compressed (e.g. "2001:db8::1428:57ab" or "2001:db8::/32") addresses can be specified, and IPv4 mapped address notation (e.g. "::ffff:192.0.2.1") is also supported.<br />
            For email address bans, simply enter an email address, using the wildcard "*" to match any number of characters.<br />
            When adding new entries, multiple criteria can be specified in this field by separating them with spaces. This cannot be done when editing existing entries.</dd>
    <dt>Comment</dt>
        <dd>Attaches a brief comment to the permission entry, visible to Administrators for reference purposes.</dd>
    <dt>Reason</dt>
        <dd>For bans only, this text is displayed to matching users to explain why they have been banned.</dd>
    <dt>Duration</dt>
        <dd>In order to make a ban/exception temporary, either specify its desired duration in days or enter a timestamp to specify exactly when the entry should expire. To make it permanent, specify the number 0.</dd>
</dl>
<p>Clicking on a permission entry's ID will fill its values into the table and allow it to be modified or deleted.</p>
<?php
}
?>
