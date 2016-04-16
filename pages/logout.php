<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: logout.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

// set locks for logging purposes
$lock['emp1'] = $emp1->e_id;
$lock['user1'] = $user1->u_id;
db_lockentities(array($user1, $emp1), $user1->u_id);
logevent();

$emp1->clrFlag(EFLAG_ONLINE);

$emp1->save();
$user1->save();
$db->releaseLocks();
prom_session::kill();

redirect(URL_HOMEPAGE);
?>
