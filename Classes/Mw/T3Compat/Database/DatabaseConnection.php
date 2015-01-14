<?php
namespace Mw\T3Compat\Database;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Mw\T3Compat\Exception\IncompatibilityException;
use Mw\T3Compat\Utility\GeneralUtility;
use TYPO3\Flow\Annotations as Flow;

class DatabaseConnection {
	/**
	 * The AND constraint in where clause
	 *
	 * @var string
	 */
	const AND_Constraint = 'AND';

	/**
	 * The OR constraint in where clause
	 *
	 * @var string
	 */
	const OR_Constraint = 'OR';

	/**
	 * the date and time formats compatible with the database in general
	 *
	 * @var array
	 */
	static protected $dateTimeFormats = array(
		'date' => array(
			'empty' => '0000-00-00',
			'format' => 'Y-m-d'
		),
		'datetime' => array(
			'empty' => '0000-00-00 00:00:00',
			'format' => 'Y-m-d H:i:s'
		)
	);

	/**
	 * @var GeneralUtility
	 * @Flow\Inject
	 */
	protected $generalUtility;

	/**
	 * @var ObjectManager
	 * @Flow\Inject
	 */
	protected $entityManager;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $connection;

	public function initialize() {

	}

	public function initializeObject() {
		if ($this->entityManager instanceof EntityManager) {
			$this->connection = $this->entityManager->getConnection();
		}
	}

	/************************************
	 *
	 * Query execution
	 *
	 * These functions are the RECOMMENDED DBAL functions for use in your applications
	 * Using these functions will allow the DBAL to use alternative ways of accessing data (contrary to if a query is returned!)
	 * They compile a query AND execute it immediately and then return the result
	 * This principle heightens our ability to create various forms of DBAL of the functions.
	 * Generally: We want to return a result pointer/object, never queries.
	 * Also, having the table name together with the actual query execution allows us to direct the request to other databases.
	 *
	 **************************************/

	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 *
	 * @param string $table Table name
	 * @param array $fields_values Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		$this->connection->insert($table, $fields_values);
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param array $rows Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		foreach ($rows as $row) {
			$this->connection->insert($table, $row);
		}
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param array $fields_values Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		$this->query($this->UPDATEquery($table, $where, $fields_values, $no_quote_fields));
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_DELETEquery($table, $where) {
		$this->query($this->DELETEquery($table, $where));
	}

	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 * @param string $select_fields List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param string $from_table Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param string $where_clause Additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		$res = $this->query($query);

		return $res;
	}

	/**
	 * Creates and executes a SELECT query, selecting fields ($select) from two/three tables joined
	 * Use $mm_table together with $local_table or $foreign_table to select over two tables. Or use all three tables to select the full MM-relation.
	 * The JOIN is done with [$local_table].uid <--> [$mm_table].uid_local  / [$mm_table].uid_foreign <--> [$foreign_table].uid
	 * The function is very useful for selecting MM-relations between tables adhering to the MM-format used by TCE (TYPO3 Core Engine). See the section on $GLOBALS['TCA'] in Inside TYPO3 for more details.
	 *
	 * @param string $select Field list for SELECT
	 * @param string $local_table Tablename, local table
	 * @param string $mm_table Tablename, relation table
	 * @param string $foreign_table Tablename, foreign table
	 * @param string $whereClause Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT! You have to prepend 'AND ' to this parameter yourself!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_mm_query($select, $local_table, $mm_table, $foreign_table, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '') {
		$foreign_table_as = $foreign_table == $local_table ? $foreign_table . str_replace('.', '', uniqid('_join', TRUE)) : '';
		$mmWhere = $local_table ? $local_table . '.uid=' . $mm_table . '.uid_local' : '';
		$mmWhere .= ($local_table and $foreign_table) ? ' AND ' : '';
		$tables = ($local_table ? $local_table . ',' : '') . $mm_table;
		if ($foreign_table) {
			$mmWhere .= ($foreign_table_as ?: $foreign_table) . '.uid=' . $mm_table . '.uid_foreign';
			$tables .= ',' . $foreign_table . ($foreign_table_as ? ' AS ' . $foreign_table_as : '');
		}
		return $this->exec_SELECTquery($select, $tables, $mmWhere . ' ' . $whereClause, $groupBy, $orderBy, $limit);
	}

	/**
	 * Executes a select based on input query parts array
	 *
	 * @param array $queryParts Query parts array
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_queryArray($queryParts) {
		return $this->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @param string $uidIndexField If set, the result array will carry this field names value as index. Requires that field to be selected of course!
	 * @return array|NULL Array of rows, or NULL in case of SQL error
	 */
	public function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
		$res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		$output = array();
		if ($uidIndexField) {
			while ($tempRow = $this->sql_fetch_assoc($res)) {
				$output[$tempRow[$uidIndexField]] = $tempRow;
			}
		} else {
			while ($output[] = $this->sql_fetch_assoc($res)) {

			}
			array_pop($output);
		}
		$this->sql_free_result($res);
		return $output;
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND gets a result set and returns an array with a single record in.
	 * LIMIT is automatically set to 1 and can not be overridden.
	 *
	 * @param string $select_fields List of fields to select from the table.
	 * @param string $from_table Table(s) from which to select.
	 * @param string $where_clause Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param boolean $numIndex If set, the result will be fetched with sql_fetch_row, otherwise sql_fetch_assoc will be used.
	 * @return array|FALSE|NULL Single row, FALSE on empty result, NULL on error
	 */
	public function exec_SELECTgetSingleRow($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $numIndex = FALSE) {
		$res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, '1');
		$output = NULL;

		if ($numIndex) {
			$output = $this->sql_fetch_row($res);
		} else {
			$output = $this->sql_fetch_assoc($res);
		}

		$this->sql_free_result($res);
		return $output;
	}

	/**
	 * Counts the number of rows in a table.
	 *
	 * @param string $field Name of the field to use in the COUNT() expression (e.g. '*')
	 * @param string $table Name of the table to count rows for
	 * @param string $where (optional) WHERE statement of the query
	 * @return mixed Number of rows counter (integer) or FALSE if something went wrong (boolean)
	 */
	public function exec_SELECTcountRows($field, $table, $where = '') {
		$resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);

		list($count) = $this->sql_fetch_row($resultSet);
		$count = (int)$count;
		$this->sql_free_result($resultSet);

		return $count;
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table Database tablename
	 * @return Statement Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		$this->query($this->TRUNCATEquery($table));
	}

	/**
	 * Central query method. Also checks if there is a database connection.
	 * Use this to execute database queries instead of directly calling $this->link->query()
	 *
	 * @param string $query The query to send to the database
	 * @return Statement
	 */
	protected function query($query) {
		return $this->connection->query($query);
	}

	/**************************************
	 *
	 * Query building
	 *
	 **************************************/
	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 *
	 * @param string $table See exec_INSERTquery()
	 * @param array $fields_values See exec_INSERTquery()
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return string|NULL Full SQL query for INSERT, NULL if $fields_values is empty
	 */
	public function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (!is_array($fields_values) || count($fields_values) === 0) {
			return NULL;
		}
		// Quote and escape values
		$fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields, TRUE);
		// Build query
		$query = 'INSERT INTO ' . $table . ' (' . implode(',', array_keys($fields_values)) . ') VALUES ' . '(' . implode(',', $fields_values) . ')';
		// Return query
		return $query;
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param array $rows Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return string|NULL Full SQL query for INSERT, NULL if $rows is empty
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (count($rows) === 0) {
			return NULL;
		}
		// Build query
		$query = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES ';
		$rowSQL = array();
		foreach ($rows as $row) {
			// Quote and escape values
			$row = $this->fullQuoteArray($row, $table, $no_quote_fields);
			$rowSQL[] = '(' . implode(', ', $row) . ')';
		}
		$query .= implode(', ', $rowSQL);
		// Return query
		return $query;
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 *
	 *
	 * @param string $table See exec_UPDATEquery()
	 * @param string $where See exec_UPDATEquery()
	 * @param array $fields_values See exec_UPDATEquery()
	 * @param boolean $no_quote_fields
	 * @throws \InvalidArgumentException
	 * @return string Full SQL query for UPDATE
	 */
	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			$fields = array();
			if (is_array($fields_values) && count($fields_values)) {
				// Quote and escape values
				$nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields, TRUE);
				foreach ($nArr as $k => $v) {
					$fields[] = $k . '=' . $v;
				}
			}
			// Build query
			$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . ((string)$where !== '' ? ' WHERE ' . $where : '');
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !', 1270853880);
		}
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table See exec_DELETEquery()
	 * @param string $where See exec_DELETEquery()
	 * @return string Full SQL query for DELETE
	 * @throws \InvalidArgumentException
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table . ((string)$where !== '' ? ' WHERE ' . $where : '');
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !', 1270853881);
		}
	}

	/**
	 * Creates a SELECT SQL-statement
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . ((string)$where_clause !== '' ? ' WHERE ' . $where_clause : '');
		// Group by
		$query .= (string)$groupBy !== '' ? ' GROUP BY ' . $groupBy : '';
		// Order by
		$query .= (string)$orderBy !== '' ? ' ORDER BY ' . $orderBy : '';
		// Group by
		$query .= (string)$limit !== '' ? ' LIMIT ' . $limit : '';
		// Return query
		return $query;
	}

	/**
	 * Creates a SELECT SQL-statement to be used as subquery within another query.
	 * BEWARE: This method should not be overriden within DBAL to prevent quoting from happening.
	 *
	 * @param string $select_fields List of fields to select from the table.
	 * @param string $from_table Table from which to select.
	 * @param string $where_clause Conditional WHERE statement
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTsubquery($select_fields, $from_table, $where_clause) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . ((string)$where_clause !== '' ? ' WHERE ' . $where_clause : '');
		// Return query
		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 * @return string Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
		// Table should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'TRUNCATE TABLE ' . $table;
		// Return query:
		return $query;
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers,
	 * "34,234,5" (with no spaces between). This query would be able to select that
	 * record based on the value "34", "234" or "5" regardless of their position in
	 * the list (left, middle or right).
	 * The value must not contain a comma (,)
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * @param string $field Field name
	 * @param string $value Value to find in list
	 * @param string $table Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @return string WHERE clause for a query
	 * @throws \InvalidArgumentException
	 */
	public function listQuery($field, $value, $table) {
		$value = (string)$value;
		if (strpos($value, ',') !== FALSE) {
			throw new \InvalidArgumentException('$value must not contain a comma (,) in $this->listQuery() !', 1294585862);
		}
		$pattern = $this->quoteStr($value, $table);
		$where = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';
		return $where;
	}

	/**
	 * Returns a WHERE clause which will make an AND or OR search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * @param array $searchWords Array of search words
	 * @param array $fields Array of fields
	 * @param string $table Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @param string $constraint How multiple search words have to match ('AND' or 'OR')
	 * @return string WHERE clause for search
	 */
	public function searchQuery($searchWords, $fields, $table, $constraint = self::AND_Constraint) {
		switch ($constraint) {
			case self::OR_Constraint:
				$constraint = 'OR';
				break;
			default:
				$constraint = 'AND';
		}

		$queryParts = array();
		foreach ($searchWords as $sw) {
			$like = ' LIKE \'%' . $this->quoteStr($sw, $table) . '%\'';
			$queryParts[] = $table . '.' . implode(($like . ' OR ' . $table . '.'), $fields) . $like;
		}
		$query = '(' . implode(') ' . $constraint . ' (', $queryParts) . ')';

		return $query;
	}

	/**************************************
	 *
	 * Prepared Query Support
	 *
	 **************************************/
	/**
	 * Creates a SELECT prepared SQL statement.
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 * @return Statement Prepared statement
	 */
	public function prepare_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', array $input_parameters = array()) {
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		return $this->connection->prepare($query);
	}

	/**
	 * Creates a SELECT prepared SQL statement based on input query parts array
	 *
	 * @param array $queryParts Query parts array
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 * @return Statement Prepared statement
	 */
	public function prepare_SELECTqueryArray(array $queryParts, array $input_parameters = array()) {
		return $this->prepare_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT'], $input_parameters);
	}

	/**
	 * Prepares a prepared query.
	 *
	 * @param string $query The query to execute
	 * @param array $queryComponents The components of the query to execute
	 * @return Statement
	 * @internal This method may only be called by \TYPO3\CMS\Core\Database\PreparedStatement
	 */
	public function prepare_PREPAREDquery($query, array $queryComponents) {
		return $this->connection->prepare($query);
	}

	/**************************************
	 *
	 * Various helper functions
	 *
	 * Functions recommended to be used for
	 * - escaping values,
	 * - cleaning lists of values,
	 * - stripping of excess ORDER BY/GROUP BY keywords
	 *
	 **************************************/
	/**
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @param boolean $allowNull Whether to allow NULL values
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function fullQuoteStr($str, $table, $allowNull = FALSE) {
		if ($allowNull && $str === NULL) {
			return 'NULL';
		}

		return '\'' . $this->connection->quote($str) . '\'';
	}

	/**
	 * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
	 *
	 * @param array $arr Array with values (either associative or non-associative array)
	 * @param string $table Table name for which to quote
	 * @param boolean|array $noQuote List/array of keys NOT to quote (eg. SQL functions) - ONLY for associative arrays
	 * @param boolean $allowNull Whether to allow NULL values
	 * @return array The input array with the values quoted
	 * @see cleanIntArray()
	 */
	public function fullQuoteArray($arr, $table, $noQuote = FALSE, $allowNull = FALSE) {
		if (is_string($noQuote)) {
			$noQuote = explode(',', $noQuote);
		} elseif (!is_array($noQuote)) {
			$noQuote = FALSE;
		}
		foreach ($arr as $k => $v) {
			if ($noQuote === FALSE || !in_array($k, $noQuote)) {
				$arr[$k] = $this->fullQuoteStr($v, $table, $allowNull);
			}
		}
		return $arr;
	}

	/**
	 * Substitution for PHP function "addslashes()"
	 * Use this function instead of the PHP addslashes() function when you build queries - this will prepare your code for DBAL.
	 * NOTICE: You must wrap the output of this function in SINGLE QUOTES to be DBAL compatible. Unless you have to apply the single quotes yourself you should rather use ->fullQuoteStr()!
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return string Output string; Quotes (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function quoteStr($str, $table) {
		return $this->connection->quote($str);
	}

	/**
	 * Escaping values for SQL LIKE statements.
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to escape string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return string Output string; % and _ will be escaped with \ (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function escapeStrForLike($str, $table) {
		return addcslashes($str, '_%');
	}

	/**
	 * Will convert all values in the one-dimensional array to integers.
	 * Useful when you want to make sure an array contains only integers before imploding them in a select-list.
	 *
	 * @param array $arr Array with values
	 * @return array The input array with all values cast to (int)
	 * @see cleanIntList()
	 */
	public function cleanIntArray($arr) {
		return array_map('intval', $arr);
	}

	/**
	 * Will force all entries in the input comma list to integers
	 * Useful when you want to make sure a commalist of supposed integers really contain only integers; You want to know that when you don't trust content that could go into an SQL statement.
	 *
	 * @param string $list List of comma-separated values which should be integers
	 * @return string The input list but with every value cast to (int)
	 * @see cleanIntArray()
	 */
	public function cleanIntList($list) {
		return implode(',', $this->generalUtility->intExplode(',', $list));
	}

	/**
	 * Removes the prefix "ORDER BY" from the input string.
	 * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by can't guarantee that "ORDER BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "ORDER BY title, uid
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripGroupBy()
	 */
	public function stripOrderBy($str) {
		return preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Removes the prefix "GROUP BY" from the input string.
	 * This function is used when you call the SELECTquery() function and want to pass the GROUP BY parameter by can't guarantee that "GROUP BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "GROUP BY title, uid
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripOrderBy()
	 */
	public function stripGroupBy($str) {
		return preg_replace('/^(?:GROUP[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Takes the last part of a query, eg. "... uid=123 GROUP BY title ORDER BY title LIMIT 5,2" and splits each part into a table (WHERE, GROUPBY, ORDERBY, LIMIT)
	 * Work-around function for use where you know some userdefined end to an SQL clause is supplied and you need to separate these factors.
	 *
	 * @param string $str Input string
	 * @return array
	 */
	public function splitGroupOrderLimit($str) {
		// Prepending a space to make sure "[[:space:]]+" will find a space there
		// for the first element.
		$str = ' ' . $str;
		// Init output array:
		$wgolParts = array(
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);
		// Find LIMIT
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['LIMIT'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find ORDER BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['ORDERBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find GROUP BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['GROUPBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Rest is assumed to be "WHERE" clause
		$wgolParts['WHERE'] = $str;
		return $wgolParts;
	}

	/**
	 * Returns the date and time formats compatible with the given database table.
	 *
	 * @param string $table Table name for which to return an empty date. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how date and time should be formatted).
	 * @return array
	 */
	public function getDateTimeFormats($table) {
		return self::$dateTimeFormats;
	}

	/**************************************
	 *
	 * MySQL(i) wrapper functions
	 * (For use in your applications)
	 *
	 **************************************/
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
		return $this->connection->query($query);
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
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * MySQLi fetch_assoc() wrapper function
	 *
	 * @param Statement $res MySQLi result object / DBAL object
	 * @return array|boolean Associative array of result row.
	 */
	public function sql_fetch_assoc(Statement $res) {
		return $res->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * MySQLi fetch_row() wrapper function
	 *
	 * @param Statement $res MySQLi result object / DBAL object
	 * @return array|boolean Array with result rows.
	 */
	public function sql_fetch_row(Statement $res) {
		return $res->fetch(\PDO::FETCH_NUM);
	}

	/**
	 * Free result memory
	 * free_result() wrapper function
	 *
	 * @param Statement $res MySQLi result object / DBAL object
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_free_result(Statement $res) {
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 */
	public function sql_insert_id() {
		return $this->connection->lastInsertId();
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @return integer Number of rows affected by last query
	 */
	public function sql_affected_rows() {
		$stmt = $this->connection->prepare('SELECT ROW_COUNT()');
		$stmt->execute();

		return $stmt->fetchColumn();
	}

	/**
	 * Move internal result pointer
	 *
	 * @param Statement $res MySQLi result object / DBAL object
	 * @param integer $seek Seek result number.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_data_seek(Statement $res, $seek) {
		throw new IncompatibilityException('sql_data_seek is not supported by Doctrine!');
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @param integer $pointer Field index.
	 * @return string Returns the name of the specified field index, or FALSE on error
	 */
	public function sql_field_type($res, $pointer) {
		// mysql_field_type compatibility map
		// taken from: http://www.php.net/manual/en/mysqli-result.fetch-field-direct.php#89117
		// Constant numbers see http://php.net/manual/en/mysqli.constants.php
		$mysql_data_type_hash = array(
			1=>'tinyint',
			2=>'smallint',
			3=>'int',
			4=>'float',
			5=>'double',
			7=>'timestamp',
			8=>'bigint',
			9=>'mediumint',
			10=>'date',
			11=>'time',
			12=>'datetime',
			13=>'year',
			16=>'bit',
			//252 is currently mapped to all text and blob types (MySQL 5.0.51a)
			253=>'varchar',
			254=>'char',
			246=>'decimal'
		);
		if ($this->debug_check_recordset($res)) {
			$metaInfo = $res->fetch_field_direct($pointer);
			if ($metaInfo === FALSE) {
				return FALSE;
			}
			return $mysql_data_type_hash[$metaInfo->type];
		} else {
			return FALSE;
		}
	}

	/**
	 * Open a (persistent) connection to a MySQL server
	 *
	 * @param string $host Deprecated since 6.1, will be removed in two versions. Database host IP/domain[:port]
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

	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/
	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool.
	 * In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 */
	public function admin_get_dbs() {
	}

	/**
	 * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
	 * In a DBAL this method should 1) look up all tables from the DBMS  of
	 * the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
	 *
	 * @return array Array with tablenames as key and arrays with status information as value
	 */
	public function admin_get_tables() {
	}

	/**
	 * Returns information about each field in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * @param string $tableName Table name
	 * @return array Field information in an associative array with fieldname => field row
	 */
	public function admin_get_fields($tableName) {
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 * @return array Key information in a numeric array
	 */
	public function admin_get_keys($tableName) {
	}

	/**
	 * Returns information about the character sets supported by the current DBM
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * This is used by the Install Tool to convert tables with non-UTF8 charsets
	 * Use in Install Tool only!
	 *
	 * @return array Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 */
	public function admin_get_charsets() {
	}

	/**
	 * mysqli() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param string $query Query to execute
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function admin_query($query) {
	}

	/******************************
	 *
	 * Connect handling
	 *
	 ******************************/

	/**
	 * Set database host
	 *
	 * @param string $host
	 */
	public function setDatabaseHost($host = 'localhost') {
	}

	/**
	 * Set database port
	 *
	 * @param integer $port
	 */
	public function setDatabasePort($port = 3306) {
	}

	/**
	 * Set database socket
	 *
	 * @param string|NULL $socket
	 */
	public function setDatabaseSocket($socket = NULL) {
	}

	/**
	 * Set database name
	 *
	 * @param string $name
	 */
	public function setDatabaseName($name) {
	}

	/**
	 * Set database username
	 *
	 * @param string $username
	 */
	public function setDatabaseUsername($username) {
	}

	/**
	 * Set database password
	 *
	 * @param string $password
	 */
	public function setDatabasePassword($password) {
	}

	/**
	 * Set persistent database connection
	 *
	 * @param boolean $persistentDatabaseConnection
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 */
	public function setPersistentDatabaseConnection($persistentDatabaseConnection) {
	}

	/**
	 * Set connection compression. Might be an advantage, if SQL server is not on localhost
	 *
	 * @param bool $connectionCompression TRUE if connection should be compressed
	 */
	public function setConnectionCompression($connectionCompression) {
	}

	/**
	 * Set commands to be fired after connection was established
	 *
	 * @param array $commands List of SQL commands to be executed after connect
	 */
	public function setInitializeCommandsAfterConnect(array $commands) {
	}

	/**
	 * Set the charset that should be used for the MySQL connection.
	 * The given value will be passed on to mysqli_set_charset().
	 *
	 * The default value of this setting is utf8.
	 *
	 * @param string $connectionCharset The connection charset that will be passed on to mysqli_set_charset() when connecting the database. Default is utf8.
	 * @return void
	 */
	public function setConnectionCharset($connectionCharset = 'utf8') {
	}

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @param string $host Deprecated since 6.1, will be removed in two versions Database. host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with
	 * @param string $db Deprecated since 6.1, will be removed in two versions. Database name to connect to
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @internal param string $user Username to connect with.
	 * @return void
	 */
	public function connectDB($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
	}

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return TRUE;
	}

	/**
	 * Returns current database handle
	 *
	 * @return Connection
	 */
	public function getDatabaseHandle() {
		return $this->connection;
	}

	/**
	 * Set current database handle, usually \mysqli
	 *
	 * @param mixed $handle
	 */
	public function setDatabaseHandle($handle) {
	}

	/******************************
	 *
	 * Debugging
	 *
	 ******************************/
	/**
	 * Debug function: Outputs error if any
	 *
	 * @param string $func Function calling debug()
	 * @param string $query Last query if not last built query
	 * @return void
	 */
	public function debug($func, $query = '') {
	}

	/**
	 * Checks if record set is valid and writes debugging information into devLog if not.
	 *
	 * @param boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @return boolean TRUE if the  record set is valid, FALSE otherwise
	 */
	public function debug_check_recordset($res) {
	}

	/**
	 * Serialize destructs current connection
	 *
	 * @return array All protected properties that should be saved
	 */
	public function __sleep() {
		return array();
	}
}
