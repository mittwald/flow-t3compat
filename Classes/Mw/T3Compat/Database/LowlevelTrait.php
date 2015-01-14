<?php
namespace Mw\T3Compat\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Mw\T3Compat\Exception\IncompatibilityException;

/**
 * Trait that contains the low-level abstraction to the Doctrine API.
 *
 * @package Mw\T3Compat
 * @subpackage Database
 */
trait LowlevelTrait {

	/**
	 * Executes query
	 * MySQLi query() wrapper function
	 * Beware: Use of this method should be avoided as it is experimentally supported by DBAL. You should consider
	 * using exec_SELECTquery() and similar methods instead.
	 *
	 * @param string $query Query to execute
	 * @return Statement MySQLi result object / DBAL object
	 */
	public function sql_query($query) {
		return $this->getDatabaseHandle()->query($query);
	}

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string MySQLi error string.
	 */
	public function sql_error() {
		// Unnecessary. Doctrine throws exceptions, so this should never be reached.
	}

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer MySQLi error number
	 */
	public function sql_errno() {
		// Unnecessary. Doctrine throws exceptions, so this should never be reached.
	}

	/**
	 * Returns the number of selected rows.
	 *
	 * @param Statement $res MySQLi result object / DBAL object
	 * @return integer Number of resulting rows
	 */
	public function sql_num_rows(Statement $res) {
		return $res->rowCount();
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 */
	public function sql_insert_id() {
		return $this->getDatabaseHandle()->lastInsertId();
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @return integer Number of rows affected by last query
	 */
	public function sql_affected_rows() {
		$stmt = $this->getDatabaseHandle()->prepare('SELECT ROW_COUNT()');
		$stmt->execute();

		return $stmt->fetchColumn();
	}

	/**
	 * Move internal result pointer
	 *
	 * @param Statement $res  MySQLi result object / DBAL object
	 * @param integer   $seek Seek result number.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 * @throws IncompatibilityException
	 */
	public function sql_data_seek(Statement $res, $seek) {
		throw new IncompatibilityException('sql_data_seek is not supported by Doctrine!');
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res     MySQLi result object / DBAL object
	 * @param integer                       $pointer Field index.
	 * @return string Returns the name of the specified field index, or FALSE on error
	 */
	public function sql_field_type($res, $pointer) {
		// mysql_field_type compatibility map
		// taken from: http://www.php.net/manual/en/mysqli-result.fetch-field-direct.php#89117
		// Constant numbers see http://php.net/manual/en/mysqli.constants.php
		$mysql_data_type_hash = array(
			1   => 'tinyint',
			2   => 'smallint',
			3   => 'int',
			4   => 'float',
			5   => 'double',
			7   => 'timestamp',
			8   => 'bigint',
			9   => 'mediumint',
			10  => 'date',
			11  => 'time',
			12  => 'datetime',
			13  => 'year',
			16  => 'bit',
			//252 is currently mapped to all text and blob types (MySQL 5.0.51a)
			253 => 'varchar',
			254 => 'char',
			246 => 'decimal'
		);

		$metaInfo = $res->fetch_field_direct($pointer);
		if ($metaInfo === FALSE) {
			return FALSE;
		}
		return $mysql_data_type_hash[$metaInfo->type];
	}

	/**
	 * Open a (persistent) connection to a MySQL server
	 *
	 * @param string $host     Deprecated since 6.1, will be removed in two versions. Database host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with.
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with.
	 * @return boolean|void
	 * @throws \RuntimeException
	 */
	public function sql_pconnect($host = NULL, $username = NULL, $password = NULL) {
	}

	/**
	 * Select a SQL database
	 *
	 * @param string $TYPO3_db Deprecated since 6.1, will be removed in two versions. Database to connect to.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_select_db($TYPO3_db = NULL) {
	}

	/**
	 * @return Connection
	 */
	abstract public function getDatabaseHandle();

}