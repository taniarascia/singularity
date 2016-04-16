<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: database.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

require_once(PROM_BASEDIR .'includes/misc.php');

// Supported database types:
// 'mysql' - MySQL 4.1 or greater
// 'pgsql' - PostgreSQL 8.1 or greater
// 'sqlite' - SQLite 3 or greater (requires PHP 5.3 or greater)

function db_open ($type, $sock, $host, $port, $user, $pass, $name)
{
	if (!strlen($sock) && !strlen($host))
		die('Invalid database configuration - must specify either hostname or UNIX socket');
	if (strlen($sock) && strlen($host))
		die('Invalid database configuration - cannot specify both hostname and UNIX socket');

	try
	{
		switch ($type)
		{
		case 'mysql':
			$db = QM_PDO_MYSQL::open($sock, $host, $port, $user, $pass, $name);
			break;

		case 'pgsql':
			$db = QM_PDO_PGSQL::open($sock, $host, $port, $user, $pass, $name);
			break;

		case 'sqlite':
			$db = QM_PDO_SQLITE::open($sock, $host, $port, $user, $pass, $name);
			break;

		default:
			die('An unsupported database driver has been specified!');
			break;
		}
	}
	catch (PDOException $e)
	{
		return NULL;
	}

	return $db;
}

// Attempt to lock all of the specified entities
function db_lockentities ($ents, $owner, $specials = array())
{
	global $db;
	// to start off, make sure everything is actually a lockable entity
	foreach ($ents as $ent)
	{
		if (!($ent instanceof prom_entity))
			error_500('ERROR_TITLE', 'Attempted to request lock on a non-entity!');
		if ($ent->getType() == 0)
			error_500('ERROR_TITLE', 'Attempted to request lock on a non-lockable entity!');
	}
	foreach ($ents as $ent)
		$ent->lock();
	// Request any special locks
	foreach ($specials as $special)
	{
		if (!$special['type'])
			$db->lockAll();
		elseif	(!$special['id'])
			$db->lockGroup($special['type']);
		else	$db->lockSingle($special['type'], $special['id']);
	}
	$db->acquireLocks($owner);
	// Refresh all normally locked entities from the database
	foreach ($ents as $ent)
	{
		if (!$ent->locked())
			error_500('ERROR_TITLE', 'Entity '. $ent .' failed to lock when requested!');
		$ent->load();
	}
}

// Wrapper for PDO, adds query counting and some additional methods for common database-bound actions
abstract class QM_PDO extends PDO
{
	protected $queryCount;
	protected $lock_single, $lock_group, $lock_all;
	protected $acquired_locks;
	protected $post_commit;

	public function __construct($dsn, $username = '', $password = '', $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);
		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('QM_PDOStatement', array($this, &$this->queryCount)));
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
		$this->queryCount = 0;
		$this->lock_single = array();
		$this->lock_group = array();
		$this->lock_all = FALSE;
		$this->acquired_locks = array();
		$this->post_commit = array();
	}

	// Retrieve current query count
	public function getQueryCount ()
	{
		return $this->queryCount;
	}

	// Placeholder for query logging
	public function query ($statement)
	{
		$this->queryCount++;
		return parent::query($statement);
	}

	protected function postcommit ()
	{
		if (!count($this->post_commit))
			return;
		foreach ($this->post_commit as $query)
			$this->query($query);
		$this->post_commit = array();
	}

	// Placeholder to allow deferring queries until after transaction end
	public function beginTransaction ()
	{
		$this->postcommit();
		return parent::beginTransaction();
	}

	// Placeholder to allow deferring queries until after transaction end
	public function commit ()
	{
		if (!parent::commit())
			return FALSE;
		$this->postcommit();
		return TRUE;
	}

	// 1-liner for executing quick SQL queries, intended only for turns.php
	public function queryParam ($query, $parms = NULL)
	{
		$q = $this->prepare($query) or warning('Failed to prepare SQL query', 1);
		if ($parms)
			$q->bindAllValues($parms);
		$q->execute() or warning('Failed to execute SQL query', 1);
		return $q;
	}

	// evaluate an SQL query, return first cell of first row
	// useful for "SELECT COUNT(*) ..." queries
	public function queryCell ($query, $parms = NULL)
	{
		$q = $this->queryParam($query, $parms);
		return $q->fetchColumn();
	}

	// Clear the contents of a table
	abstract public function clearTable ($table);

	// Retrieves a database's sequence, if it exists
	abstract public function getSequence ($table);

	// Changes a database's sequence, if it exists
	abstract public function setSequence ($table, $value = 1);

	// Modifies the query to limit rows returned
	abstract public function setLimit ($query, $rows, $offset = 0);

	// Request that a single entity be locked
	public function lockSingle ($type, $id)
	{
		if ($this->locked())
		{
			warning('Lock on entity '. $type .':'. $id .' was requested while locks already acquired', 1);
			return FALSE;
		}
		if ($this->lock_all)
		{
			// warning('Ignoring lock request on entity '. $type .':'. $id .' - global lock already requested', 1);
			return TRUE;
		}
		if (in_array($type, $this->lock_group))
		{
			// warning('Ignoring lock request on entity '. $type .':'. $id .' - group lock already requested', 1);
			return TRUE;
		}
		$this->lock_single[] = array('type' => $type, 'id' => $id);
		return TRUE;
	}

	// Request that an entire category of entities be locked
	public function lockGroup ($type)
	{
		if ($this->locked())
		{
			warning('Lock on entity group '. $type .' was requested while locks already acquired', 1);
			return FALSE;
		}
		if ($this->lock_all)
		{
			// warning('Ignoring lock request on entity group '. $type .' - global lock already requested', 1);
			return TRUE;
		}
		$this->lock_group[] = $type;
		$singles = 0;
		foreach ($this->lock_single as $idx => $lock)
		{
			if ($lock['type'] == $type)
			{
				unset($this->lock_single[$idx]);
				$singles++;
			}
		}
		if ($singles > 0)
		{
			// warning('Discarding '. $singles .' entity locks', 1);
		}
		return TRUE;
	}

	// Request that all entities be locked
	public function lockAll ()
	{
		if ($this->locked())
		{
			warning('Lock on all entities was requested while locks already acquired', 1);
			return FALSE;
		}
		$this->lock_all = TRUE;
		$singles = count($this->lock_single);
		if ($singles > 0)
		{
			// warning('Discarding '. $singles .' entity locks', 1);
			$this->lock_single = array();
		}
		$groups = count($this->lock_single);
		if ($groups > 0)
		{
			// warning('Discarding '. $groups .' entity group locks', 1);
			$this->lock_group = array();
		}
		return TRUE;
	}

	// Acquire locks on all previously designated entities
	abstract public function acquireLocks ($owner);

	// Release locks on all previously designated entities
	abstract public function releaseLocks ();

	// Checks if a particular entity has been locked
	public function queryLock ($type, $id)
	{
		if (!$this->locked())
			return FALSE;
		return in_array(array('type' => $type, 'id' => $id), $this->acquired_locks);
	}

	// Creates a brand new lock, acquiring it if necessary
	abstract public function createLock ($type, $id);

	// Deletes an entity's lock; if $id is 0, delete the entire category
	abstract public function deleteLock ($type, $id);

	// Checks if lockEntities has been called (and unlockEntities has not yet been called)
	public function locked ()
	{
		return (count($this->acquired_locks) > 0);
	}
}

// Subclass of QM_PDO for MySQL-specific behavior
class QM_PDO_MYSQL extends QM_PDO
{
	public static function open ($sock, $host, $port, $user, $pass, $name)
	{
		$dsn = 'mysql:';
		if ($sock)
			$dsn .= 'unix_socket='. $sock .';';
		elseif ($host)
		{
			$dsn .= 'host='. $host .';';
			if ($port)
				$dsn .= 'port='. $port .';';
		}
		$dsn .= 'dbname='. $name;

		return new QM_PDO_MYSQL($dsn, $user, $pass);
	}

	public function __construct($dsn, $username = '', $password = '', $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);

		$this->exec("SET NAMES 'utf8'");
		$this->exec("SET SESSION sql_mode=''");
		$this->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
	}

	public function clearTable ($table)
	{
		return $this->query('DELETE FROM '. $table);
	}

	public function getSequence ($table)
	{
		return NULL;
	}

	public function setSequence ($table, $value = 1)
	{
		global $sequences;
		if (isset($sequences[$table]))
		{
			$this->post_commit[] = 'ALTER TABLE '. $table .' AUTO_INCREMENT='. $value;
			if (!$this->locked())
				$this->postcommit();
		}	
	}

	public function setLimit ($query, $rows, $offset = 0)
	{
		if ($offset)
			$query .= ' LIMIT '. intval($offset) .','. intval($rows);
		else	$query .= ' LIMIT '. intval($rows);
		return $query;
	}

	public function acquireLocks ($owner)
	{
		if ($this->locked())
		{
			warning('acquireLocks called while locks already acquired', 1);
			return FALSE;
		}
		if ((count($this->lock_single) == 0) && (count($this->lock_group) == 0) && (!$this->lock_all))
		{
			warning('acquireLocks called without requesting any locks', 1);
			return FALSE;
		}
		if (!$this->beginTransaction())
		{
			warning('acquireLocks unable to begin transaction', 1);
			return FALSE;
		}

		$sql = 'SELECT * FROM '. LOCK_TABLE;
		if (!$this->lock_all)
		{
			$sql .= ' WHERE ';
			if (count($this->lock_single))
				$sql .= '(lock_type,lock_id) IN '. sqlArgList($this->lock_single, '(?,?)');
			if ((count($this->lock_single)) && (count($this->lock_group)))
				$sql .= ' OR ';
			if (count($this->lock_group))
				$sql .= 'lock_type IN '. sqlArgList($this->lock_group);
		}
		$sql .= ' FOR UPDATE';

		$q = $this->prepare($sql);

		$parm = array();
		foreach ($this->lock_single as $lock)
		{
			$parm[] = $lock['type'];
			$parm[] = $lock['id'];
		}
		foreach ($this->lock_group as $lock)
			$parm[] = $lock;

		$q->bindAllValues($parm);
		if (!$q->execute())
		{
			warning('acquireLocks failed!', 1);
			return FALSE;
		}
		$locks = $q->fetchAll();
		foreach ($locks as $lock)
			$this->acquired_locks[] = array('type' => $lock['lock_type'], 'id' => $lock['lock_id']);
		return TRUE;
	}

	public function releaseLocks ()
	{
		if (!$this->locked())
		{
			warning('releaseLocks called while locks not acquired', 1);
			return FALSE;
		}
		if (!$this->commit())
		{
			warning('releaseLocks failed to commit transaction', 1);
			return FALSE;
		}
		$this->lock_single = array();
		$this->lock_group = array();
		$this->lock_all = FALSE;
		$this->acquired_locks = array();
		return TRUE;
	}

	public function createLock ($type, $id)
	{
		$q = $this->prepare('INSERT INTO '. LOCK_TABLE .' (lock_type,lock_id) VALUES (?,?)');
		$q->bindIntValue(1, $type);
		$q->bindIntValue(2, $id);
		$q->execute() or warning('Failed to create lock for entity '. $type .':'. $id, 1);
		if ($this->locked())
			$this->acquired_locks[] = array('type' => $type, 'id' => $id);
	}

	public function deleteLock ($type, $id)
	{
		if (!$this->locked())
		{
			warning('Attempted to call deleteLock while no locks acquired', 1);
			return FALSE;
		}

		if ($id == 0)
		{
			if ((!in_array($type, $this->lock_group)) && (!$this->lock_all))
			{
				warning('Attempted to delete locks for unlocked entity group '. $type, 1);
				return FALSE;
			}
			$q = $this->prepare('DELETE FROM '. LOCK_TABLE .' WHERE lock_type=?');
			$q->bindIntValue(1, $type);
			$q->execute() or warning('Failed to delete locks for entity group '. $type, 1);
			foreach ($this->acquired_locks as $num => $lock)
			{
				if ($lock['type'] == $type)
					unset($this->acquired_locks[$num]);
			}
		}
		else
		{
			if (!$this->queryLock($type, $id))
			{
				warning('Attempted to delete lock for unlocked entity '. $type .':'. $id, 1);
				return FALSE;
			}
			$q = $this->prepare('DELETE FROM '. LOCK_TABLE .' WHERE lock_type=? AND lock_id=?');
			$q->bindIntValue(1, $type);
			$q->bindIntValue(2, $id);
			$q->execute() or warning('Failed to delete lock for entity '. $type .':'. $id, 1);
			foreach ($this->acquired_locks as $num => $lock)
			{
				if (($lock['type'] == $type) && ($lock['id'] == $id))
				{
					unset($this->acquired_locks[$num]);
					break;
				}
			}
		}
		return TRUE;
	}
}

// Subclass of QM_PDO for PostgreSQL-specific behavior
class QM_PDO_PGSQL extends QM_PDO
{
	public static function open ($sock, $host, $port, $user, $pass, $name)
	{
		$dsn = 'pgsql:';
		if ($sock)
			$dsn .= 'host='. $sock .' ';
		elseif ($host)
		{
			$dsn .= 'host='. $host .' ';
			if ($port)
				$dsn .= 'port='. $port .' ';
		}
		$dsn .= 'dbname='. $name;

		return new QM_PDO_PGSQL($dsn, $user, $pass);
	}

	public function clearTable ($table)
	{
		return $this->query('DELETE FROM '. $table);
	}

	public function getSequence ($table)
	{
		global $sequences;
		// Not all tables have sequences associated with them
		if (!isset($sequences[$table]))
			return NULL;
		return $sequences[$table];
	}

	public function setSequence ($table, $value = 1)
	{
		$seq = $this->getSequence($table);
		if ($seq)
		{
			$this->post_commit[] = 'ALTER SEQUENCE '. $seq .' RESTART WITH '. $value;
			if (!$this->locked())
				$this->postcommit();
		}
	}

	public function setLimit ($query, $rows, $offset = 0)
	{
		$query .= ' LIMIT '. intval($rows);
		if ($offset)
			$query .= ' OFFSET '. intval($offset);
		return $query;
	}

	public function acquireLocks ($owner)
	{
		if ($this->locked())
		{
			warning('acquireLocks called while locks already acquired', 1);
			return FALSE;
		}
		if ((count($this->lock_single) == 0) && (count($this->lock_group) == 0) && (!$this->lock_all))
		{
			warning('acquireLocks called without requesting any locks', 1);
			return FALSE;
		}
		if (!$this->beginTransaction())
		{
			warning('acquireLocks unable to begin transaction', 1);
			return FALSE;
		}

		$sql = 'SELECT * FROM '. LOCK_TABLE;
		if (!$this->lock_all)
		{
			$sql .= ' WHERE ';
			if (count($this->lock_single))
				$sql .= '(lock_type,lock_id) IN '. sqlArgList($this->lock_single, '(?,?)');
			if ((count($this->lock_single)) && (count($this->lock_group)))
				$sql .= ' OR ';
			if (count($this->lock_group))
				$sql .= 'lock_type IN '. sqlArgList($this->lock_group);
		}
		$sql .= ' FOR UPDATE';

		$q = $this->prepare($sql);

		$parm = array();
		foreach ($this->lock_single as $lock)
		{
			$parm[] = $lock['type'];
			$parm[] = $lock['id'];
		}
		foreach ($this->lock_group as $lock)
			$parm[] = $lock;

		$q->bindAllValues($parm);
		if (!$q->execute())
		{
			warning('acquireLocks failed!', 1);
			return FALSE;
		}
		$locks = $q->fetchAll();
		foreach ($locks as $lock)
			$this->acquired_locks[] = array('type' => $lock['lock_type'], 'id' => $lock['lock_id']);
		return TRUE;
	}

	public function releaseLocks ()
	{
		if (!$this->locked())
		{
			warning('releaseLocks called while locks not acquired', 1);
			return FALSE;
		}
		if (!$this->commit())
		{
			warning('releaseLocks failed to commit transaction', 1);
			return FALSE;
		}
		$this->lock_single = array();
		$this->lock_group = array();
		$this->lock_all = FALSE;
		$this->acquired_locks = array();
		return TRUE;
	}

	public function createLock ($type, $id)
	{
		$q = $this->prepare('INSERT INTO '. LOCK_TABLE .' (lock_type,lock_id) VALUES (?,?)');
		$q->bindIntValue(1, $type);
		$q->bindIntValue(2, $id);
		$q->execute() or warning('Failed to create lock for entity '. $type .':'. $id, 1);
		if ($this->locked())
			$this->acquired_locks[] = array('type' => $type, 'id' => $id);
	}

	public function deleteLock ($type, $id)
	{
		if (!$this->locked())
		{
			warning('Attempted to call deleteLock while no locks acquired', 1);
			return FALSE;
		}

		if ($id == 0)
		{
			if ((!in_array($type, $this->lock_group)) && (!$this->lock_all))
			{
				warning('Attempted to delete locks for unlocked entity group '. $type, 1);
				return FALSE;
			}
			$q = $this->prepare('DELETE FROM '. LOCK_TABLE .' WHERE lock_type=?');
			$q->bindIntValue(1, $type);
			$q->execute() or warning('Failed to delete locks for entity group '. $type, 1);
			foreach ($this->acquired_locks as $num => $lock)
			{
				if ($lock['type'] == $type)
					unset($this->acquired_locks[$num]);
			}
		}
		else
		{
			if (!$this->queryLock($type, $id))
			{
				warning('Attempted to delete lock for unlocked entity '. $type .':'. $id, 1);
				return FALSE;
			}
			$q = $this->prepare('DELETE FROM '. LOCK_TABLE .' WHERE lock_type=? AND lock_id=?');
			$q->bindIntValue(1, $type);
			$q->bindIntValue(2, $id);
			$q->execute() or warning('Failed to delete lock for entity '. $type .':'. $id, 1);
			foreach ($this->acquired_locks as $num => $lock)
			{
				if (($lock['type'] == $type) && ($lock['id'] == $id))
				{
					unset($this->acquired_locks[$num]);
					break;
				}
			}
		}
		return TRUE;
	}
}

// Subclass of QM_PDO for SQLite-specific behavior
class QM_PDO_SQLITE extends QM_PDO
{
	public static function open ($sock, $host, $port, $user, $pass, $name)
	{
		if ($host)
			return NULL;
		$dsn = 'sqlite:'. $sock;

		return new QM_PDO_SQLITE($dsn, $user, $pass);
	}
	public function __construct($dsn, $username = '', $password = '', $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);

		$this->exec("PRAGMA encoding = 'UTF-8'");
		$this->exec("PRAGMA synchronous = OFF");
		$this->sqliteCreateFunction('LEAST', array('QM_PDO_SQLITE', 'sqlite_least'), 2);
	}

	public function clearTable ($table)
	{
		return $this->query('DELETE FROM '. $table);
	}

	public function getSequence ($table)
	{
		return NULL;
	}

	public function setSequence ($table, $value = 1)
	{
		// not implemented
	}

	public function setLimit ($query, $rows, $offset = 0)
	{
		$query .= ' LIMIT '. intval($rows);
		if ($offset)
			$query .= ' OFFSET '. intval($offset);
		return $query;
	}

	public function acquireLocks ($owner)
	{
		if ($this->locked())
		{
			warning('acquireLocks called while locks already acquired', 1);
			return FALSE;
		}
		if ((count($this->lock_single) == 0) && (count($this->lock_group) == 0) && (!$this->lock_all))
		{
			warning('acquireLocks called without requesting any locks', 1);
			return FALSE;
		}
		while (1)
		{
			if ($this->beginTransaction())
				break;
			$err = $this->errorInfo();
			if ($err[1] == 5)	// SQLITE_BUSY
			{
				usleep(250000);
				continue;
			}
			warning('acquireLocks unable to begin transaction', 1);
			return FALSE;
		}

		$this->query('UPDATE '. LOCK_TABLE .' SET lock_id=lock_id+1');
		$this->acquired_locks[] = TRUE;
		return TRUE;
	}

	public function releaseLocks ()
	{
		if (!$this->locked())
		{
			warning('releaseLocks called while locks not acquired', 1);
			return FALSE;
		}
		while (1)
		{
			if ($this->commit())
				break;
			$err = $this->errorInfo();
			if ($err[1] == 5)	// SQLITE_BUSY
			{
				usleep(250000);
				continue;
			}
			warning('releaseLocks failed to commit transaction', 1);
			return FALSE;
		}
		$this->lock_single = array();
		$this->lock_group = array();
		$this->lock_all = FALSE;
		$this->acquired_locks = array();
		return TRUE;
	}

	// For performance reasons, SQLite won't do per-entity locks - it's either all or nothing
	public function queryLock ($type, $id)
	{
		return $this->locked();
	}

	public function createLock ($type, $id)
	{
		// We do need at least one row in the lock table, so we'll add it when requested for ENT_VARS
		if ($type != ENT_VARS)
			return;
		$this->query('INSERT INTO '. LOCK_TABLE .' (lock_id) VALUES (0)') or warning('Failed to create lock for entity '. $type .':'. $id, 1);
	}

	public function deleteLock ($type, $id)
	{
		if (!$this->locked())
		{
			warning('Attempted to call deleteLock while no locks acquired', 1);
			return FALSE;
		}

		if ($type != ENT_VARS)
			return TRUE;

		if (!$this->query('DELETE FROM '. LOCK_TABLE))
		{
			warning('Failed to delete lock for entity '. $type .':'. $id, 1);
			return FALSE;
		}
		return TRUE;
	}

	// SQLite does not implement the LEAST() function (it uses MIN() instead), so it needs to be implemented here
	public static function sqlite_least ($num1, $num2)
	{
		return min($num1, $num2);
	}
}

// Wrapper for PDOStatement, adds query counting and some shortcuts for binding parameters
class QM_PDOStatement extends PDOStatement
{
	protected $dbh;
	protected $queryCount;

	protected function __construct ($dbh, &$queryCount)
	{
		$this->dbh = $dbh;
		$this->queryCount = &$queryCount;
	}

	// Placeholder for query logging
	public function execute ($input_parameters = NULL)
	{
		$this->queryCount++;
		return parent::execute($input_parameters);
	}

	// Shortcut for binding an integer parameter
	public function bindIntValue ($parm, $value)
	{
		return $this->bindValue($parm, $value, PDO::PARAM_INT);
	}

	// Shortcut for binding a string parameter
	public function bindStrValue ($parm, $value)
	{
		return $this->bindValue($parm, $value, PDO::PARAM_STR);
	}

	// Shortcut for binding a float parameter
	// Since PDO lacks a "float" type, bind as string for now
	public function bindFltValue ($parm, $value)
	{
		return $this->bindValue($parm, $value, PDO::PARAM_STR);
	}

	// Automatically bind all parameters based on type (for cases where the parameter count varies)
	public function bindAllValues ($parms)
	{
		foreach ($parms as $i => $val)
		{
			if (is_numeric($val))
			{
				if ($val - floor($val) > 0.00001)
					$this->bindFltValue($i + 1, $val);
				else	$this->bindIntValue($i + 1, $val);
			}
			else	$this->bindStrValue($i + 1, $val);
		}
	}
}
?>
