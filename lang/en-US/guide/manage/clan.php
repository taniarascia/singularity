<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: clan.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GUIDE'))
	die('Access denied');

if (CLAN_ENABLE)
	guidepage($topic, 'manage/clan', 'g_manage_clan', 'Managing your Clan');
function g_manage_clan()
{
?>
<h2>Clan Management</h2>
<p>If you create a clan or are granted appropriate permissions, you may browse here from the Clan page to manage your clan.</p>
<h3>Relations</h3>
<p>From this section, you may request alliances or declare wars with other clans at your own discretion. Any existing relations will be listed with appropriate options to change them, if available.</p>
<p>Be aware that any relation changes will result in a notification being sent to the leaders of the clans involved, and that once you take an action, you must wait before attempting to change your mind.</p>
<h4>Alliances</h4>
<p>By forming an alliance with another clan, your clan members will be allowed to send an unlimited amount of foreign aid shipments to members of your ally clan. You will also be prevented from attacking members of ally clans, as such an incident would be quite embarrassing.</p>
<p>Once you request an alliance with another clan, an appropriate leader of the other clan must accept it before it will take effect. Once in effect, either clan can terminate the alliance at will.</p>
<h4>Wars</h4>
<p>Declaring war with another clan provides several tactical advantages when attacking or casting spells on its members: relative size differences are ignored, <?php if (MAX_ATTACKS > 0) echo 'attack limits are bypassed, '; ?> and a 20% offensive power bonus is granted.</p>
<p>Be warned that, by declaring war with another clan, the other clan will automatically be placed at war with you as well, immediately giving them the same advantages against you.</p>
<p>Only the clan which began the war can propose a treaty to end it, at which point originating clan members are no longer considered to be at war while enemy clan members are still considered hostile; if the other clan accepts, the war ends, but if the other clan refuses, then it will be as if they had declared war on your clan - now, only they will be able to propose peace.</p>
<h3>Member Permissions</h3>
<p>From this section, a clan's Leader or Assistant Leader can assign special roles to other clan members.</p>
<dl>
    <dt>Leader</dt>
        <dd>Required, can perform any action in the clan - relations can be changed, roles assigned, and clan properties edited.</dd>
    <dt>Assistant Leader</dt>
        <dd>Optional, has the same privileges as Leader except is not allowed to change the Leader or Assistant Leader roles.</dd>
    <dt>Minister of Foreign Affairs</dt>
        <dd>Optional, up to 2 members, permitted to change relations and post to the <b><?php echo lang('CLANFORUM_SUBJECT_NEWS'); ?></b> thread in the <?php echo guidelink('clanforum', 'Clan Forum'); ?>.</dd>
</dl>
<?php	if (CLAN_VIEW_STAT) { ?>
<p>Clicking on an empire's name within this list will allow viewing its detailed <?php echo guidelink('status', 'status'); ?>, allowing the clan's Leader or Assistant Leader to assess the readiness of its members and to advise them on how to improve themselves.</p>
<?php	} ?>
<h3>Invitations</h3>
<p>This section allows privileged clan members to invite other (clanless) empires to join their clan without having to tell them the clan's password. The clan's Leader and Assistant Leader can make invitations permanent, allowing the empire to leave and rejoin the clan as often as they wish.</p>
<p>Non-permanent invitations automatically expire after <?php echo CLAN_INVITE_TIME; ?> hours or when the empire joins a clan. Only the Leader and Assistant Leader can delete permanent invitations.</p>
<h3>Clan Properties</h3>
<p>From this section, a clan's password, title, logo image, and homepage location can be changed by the clan's Leader or Assistant Leader.</p>
<?php
}
?>
