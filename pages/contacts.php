<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: contacts.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

$title = 'CONTACTS_TITLE';

page_header();
?>
<?php echo lang('CONTACTS_HEADER'); ?><br />
<table class="inputtable">
<tr><th><?php echo lang('COLUMN_CLAN_NAME'); ?></th>
    <th><?php echo lang('COLUMN_CLAN_TITLE'); ?></th>
    <th><?php echo lang('COLUMN_CLAN_LEADER'); ?></th>
    <th><?php echo lang('COLUMN_CLAN_ASST'); ?></th>
    <th colspan="2"><?php echo lang('COLUMN_CLAN_FAS'); ?></th></tr>
<?php
$q = $db->query('SELECT c.c_id, c_name, c_title, '.
		'e_id_leader, p1.e_name AS e_name_leader, '.
		'e_id_asst, p2.e_name AS e_name_asst, '.
		'e_id_fa1, p3.e_name AS e_name_fa1, '.
		'e_id_fa2, p4.e_name AS e_name_fa2 FROM '. CLAN_TABLE .' c '.
		'LEFT OUTER JOIN '. EMPIRE_TABLE .' p1 ON (e_id_leader = p1.e_id) '.
		'LEFT OUTER JOIN '. EMPIRE_TABLE .' p2 ON (e_id_asst = p2.e_id) '.
		'LEFT OUTER JOIN '. EMPIRE_TABLE .' p3 ON (e_id_fa1 = p3.e_id) '.
		'LEFT OUTER JOIN '. EMPIRE_TABLE .' p4 ON (e_id_fa2 = p4.e_id) '.
		'WHERE c_members > 0 ORDER BY c_name ASC') or warning('Failed to fetch clan contacts', 0);
foreach ($q as $cdata)
{
	$clan_a = new prom_clan($cdata['c_id']);
	$clan_a->initdata($cdata);
	$emp_a = new prom_empire($cdata['e_id_leader']);
	$emp_a->initdata(array('e_id' => $cdata['e_id_leader'], 'e_name' => $cdata['e_name_leader']));
	$link1 = '<a href="?location=messages&amp;action=contact&amp;msg_to='. $emp_a->e_id .'">'. $emp_a .'</a>';
	if ($cdata['e_id_asst'])
	{
		$emp_b = new prom_empire($cdata['e_id_asst']);
		$emp_b->initdata(array('e_id' => $cdata['e_id_asst'], 'e_name' => $cdata['e_name_asst']));
		$link2 = '<a href="?location=messages&amp;action=contact&amp;msg_to='. $emp_b->e_id .'">'. $emp_b .'</a>';
	}
	else	$link2 = lang('CONTACTS_EMP_NONE');
	if ($cdata['e_id_fa1'])
	{
		$emp_c = new prom_empire($cdata['e_id_fa1']);
		$emp_c->initdata(array('e_id' => $cdata['e_id_fa1'], 'e_name' => $cdata['e_name_fa1']));
		$link3 = '<a href="?location=messages&amp;action=contact&amp;msg_to='. $emp_c->e_id .'">'. $emp_c .'</a>';
	}
	else	$link3 = lang('CONTACTS_EMP_NONE');
	if ($cdata['e_id_fa2'])
	{
		$emp_d = new prom_empire($cdata['e_id_fa2']);
		$emp_d->initdata(array('e_id' => $cdata['e_id_fa2'], 'e_name' => $cdata['e_name_fa2']));
		$link4 = '<a href="?location=messages&amp;action=contact&amp;msg_to='. $emp_d->e_id .'">'. $emp_d .'</a>';
	}
	else	$link4 = lang('CONTACTS_EMP_NONE');
?>
<tr><td><?php echo $clan_a->c_name; ?></td>
    <td><?php echo $clan_a->c_title; ?></td>
    <td><?php echo $link1; ?></td>
    <td><?php echo $link2; ?></td>
    <td><?php echo $link3; ?></td>
    <td><?php echo $link4; ?></td></tr>
<?php
	$clan_a = NULL;
	$emp_a = NULL;
	$emp_b = NULL;
	$emp_c = NULL;
	$emp_d = NULL;
}
?>
</table>
<?php
page_footer();
?>
