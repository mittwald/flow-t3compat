<?php
namespace Mw\T3Compat\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

use Doctrine\DBAL\Driver\Statement;
use TYPO3\Flow\Annotations as Flow;

/**
 * Interface definition for a database connection.
 *
 * Matches the interface of TYPO3 CMS's DatabaseConnection class.
 *
 * @package    Mw\T3Compat
 * @subpackage Database
 */
interface DatabaseConnection {

	/**
	 * The AND constraint in where clause
	 * @var string
	 */
	const AND_Constraint = 'AND';

	/**
	 * The OR constraint in where clause
	 * @var string
	 */
	const OR_Constraint = 'OR';

	public function exec_INSERTquery($table, $fields_values, $no_quote_fields = FALSE);

	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE);

	public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE);

	public function exec_DELETEquery($table, $where);

	public function exec_SELECTquery(
		$select_fields,
		$from_table,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$limit = ''
	);

	public function exec_SELECT_mm_query(
		$select,
		$local_table,
		$mm_table,
		$foreign_table,
		$whereClause = '',
		$groupBy = '',
		$orderBy = '',
		$limit = ''
	);

	public function exec_SELECT_queryArray($queryParts);

	public function exec_SELECTgetRows(
		$select_fields,
		$from_table,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$limit = '',
		$uidIndexField = ''
	);

	public function exec_SELECTgetSingleRow(
		$select_fields,
		$from_table,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$numIndex = FALSE
	);

	public function exec_SELECTcountRows($field, $table, $where = '');

	public function exec_TRUNCATEquery($table);

	public function INSERTquery($table, $fields_values, $no_quote_fields = FALSE);

	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE);

	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE);

	public function DELETEquery($table, $where);

	public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '');

	public function SELECTsubquery($select_fields, $from_table, $where_clause);

	public function TRUNCATEquery($table);

	public function listQuery($field, $value, $table);

	public function searchQuery($searchWords, $fields, $table, $constraint = self::AND_Constraint);

	public function prepare_SELECTquery(
		$select_fields,
		$from_table,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$limit = '',
		array $input_parameters = array()
	);

	public function prepare_SELECTqueryArray(array $queryParts, array $input_parameters = array());

	public function prepare_PREPAREDquery($query, array $queryComponents);

	public function fullQuoteStr($str, $table, $allowNull = FALSE);

	public function fullQuoteArray($arr, $table, $noQuote = FALSE, $allowNull = FALSE);

	public function quoteStr($str, $table);

	public function escapeStrForLike($str, $table);

	public function cleanIntArray($arr);

	public function cleanIntList($list);

	public function stripOrderBy($str);

	public function stripGroupBy($str);

	public function splitGroupOrderLimit($str);

	public function getDateTimeFormats($table);

	public function sql_query($query);

	public function sql_error();

	public function sql_errno();

	public function sql_num_rows(Statement $res);

	public function sql_fetch_assoc(Statement $res);

	public function sql_fetch_row(Statement $res);

	public function sql_free_result(Statement $res);

	public function sql_insert_id();

	public function sql_affected_rows();

	public function sql_data_seek(Statement $res, $seek);

	public function sql_field_type($res, $pointer);

	public function sql_pconnect($host = NULL, $username = NULL, $password = NULL);

	public function sql_select_db($TYPO3_db = NULL);

	public function admin_get_dbs();

	public function admin_get_tables();

	public function admin_get_fields($tableName);

	public function admin_get_keys($tableName);

	public function admin_get_charsets();

	public function admin_query($query);

	public function setDatabaseHost($host = 'localhost');

	public function setDatabasePort($port = 3306);

	public function setDatabaseSocket($socket = NULL);

	public function setDatabaseName($name);

	public function setDatabaseUsername($username);

	public function setDatabasePassword($password);

	public function setPersistentDatabaseConnection($persistentDatabaseConnection);

	public function setConnectionCompression($connectionCompression);

	public function setInitializeCommandsAfterConnect(array $commands);

	public function setConnectionCharset($connectionCharset = 'utf8');

	public function connectDB($host = NULL, $username = NULL, $password = NULL, $db = NULL);

	public function isConnected();

	public function getDatabaseHandle();

	public function setDatabaseHandle($handle);

	public function debug($func, $query = '');

	/**
	 * @param Statement $res
	 * @return bool
	 */
	public function debug_check_recordset(Statement $res);

}
