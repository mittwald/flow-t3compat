<?php
namespace Mw\T3Compat\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

/**
 * Trait that contains the query building aspect of the database compatibility layer.
 *
 * @package Mw\T3Compat
 * @subpackage Database
 */
trait QueryBuildingTrait {

	/**
	 * Creates a SELECT SQL-statement
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table    See exec_SELECTquery()
	 * @param string $where_clause  See exec_SELECTquery()
	 * @param string $groupBy       See exec_SELECTquery()
	 * @param string $orderBy       See exec_SELECTquery()
	 * @param string $limit         See exec_SELECTquery()
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
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 *
	 *
	 * @param string  $table         See exec_UPDATEquery()
	 * @param string  $where         See exec_UPDATEquery()
	 * @param array   $fields_values See exec_UPDATEquery()
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
			$query = 'UPDATE ' . $table . ' SET ' . implode(
					',',
					$fields
				) . ((string)$where !== '' ? ' WHERE ' . $where : '');
			return $query;
		} else {
			throw new \InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !',
				1270853880
			);
		}
	}

	abstract public function fullQuoteArray($arr, $table, $noQuote = FALSE, $allowNull = FALSE);

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
			$query = "DELETE FROM {$table} " . ((string)$where !== '' ? ' WHERE ' . $where : '');
			return $query;
		} else {
			throw new \InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !',
				1270853881
			);
		}
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
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 *
	 * @param string  $table           See exec_INSERTquery()
	 * @param array   $fields_values   See exec_INSERTquery()
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
		$query = "INSERT INTO {$table} (" . implode(',', array_keys($fields_values)) . ") VALUES ("
			. implode(',', $fields_values) . ")";
		// Return query
		return $query;
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string  $table           Table name
	 * @param array   $fields          Field names
	 * @param array   $rows            Table rows. Each row should be an array with field values mapping to $fields
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
		$query  = "INSERT INTO {$table} (" . implode(', ', $fields) . ') VALUES ';
		$rowSQL = array();
		foreach ($rows as $row) {
			// Quote and escape values
			$row      = $this->fullQuoteArray($row, $table, $no_quote_fields);
			$rowSQL[] = '(' . implode(', ', $row) . ')';
		}
		$query .= implode(', ', $rowSQL);
		// Return query
		return $query;
	}

	/**
	 * Creates a SELECT SQL-statement to be used as subquery within another query.
	 * BEWARE: This method should not be overriden within DBAL to prevent quoting from happening.
	 *
	 * @param string $select_fields List of fields to select from the table.
	 * @param string $from_table    Table from which to select.
	 * @param string $where_clause  Conditional WHERE statement
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
			throw new \InvalidArgumentException(
				'$value must not contain a comma (,) in $this->listQuery() !',
				1294585862
			);
		}
		$pattern = $this->quoteStr($value, $table);
		$where   = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';
		return $where;
	}

	/**
	 * Returns a WHERE clause which will make an AND or OR search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * @param array  $searchWords Array of search words
	 * @param array  $fields      Array of fields
	 * @param string $table       Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @param string $constraint  How multiple search words have to match ('AND' or 'OR')
	 * @return string WHERE clause for search
	 */
	public function searchQuery($searchWords, $fields, $table, $constraint = DatabaseConnection::AND_Constraint) {
		switch ($constraint) {
			case DatabaseConnection::OR_Constraint:
				$constraint = 'OR';
				break;
			default:
				$constraint = 'AND';
		}

		$queryParts = array();
		foreach ($searchWords as $sw) {
			$like         = ' LIKE \'%' . $this->quoteStr($sw, $table) . '%\'';
			$queryParts[] = $table . '.' . implode(($like . ' OR ' . $table . '.'), $fields) . $like;
		}
		$query = '(' . implode(') ' . $constraint . ' (', $queryParts) . ')';

		return $query;
	}

	abstract public function quoteStr($str, $table);

}