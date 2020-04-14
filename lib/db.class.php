<?php

	/**
	 * @name: db.class
	 * @package: lib
	 * @description: High-Level Database Access Abstraction Object
	 * @author: Florian Götzrath <info@floriangoetzrath.de>
	 */

	final class hrefp_db
	{

		/** @var String the formatted host string that is used to build the connection  */
		const DSN = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
		/** @var PDO|null the connection that this class operates on */
		private $pdo;

		/**
		 * hrefp_db constructor
		 */
		function __construct()
		{

			try{
				$this->pdo = new PDO(static::DSN, DB_USER, DB_PASSWORD);
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch(PDOException $e) {
				$GLOBALS['hrefp_err']->throwFatalError("A fatal error occured while connecting to the database.", $e);
			}

		} // function __construct()

		/**
		 * Select Abstraction, part of CRUD operations
		 *
		 * @param String     $query
		 * @param array|null $bindValues
		 *
		 * @return mixed
		 */
		public function select(String $query, array $bindValues = null)
		{

			return $this->selectInternal(function($stmt) {
				/** @var PDOStatement $stmt */
				return $stmt->fetchAll();
			}, $query, $bindValues);

		} // public function select()

		/**
		 * Insert Abstraction, part of CRUD operations
		 *
		 * @param String $tblName
		 * @param array  $insertMappings
		 *
		 * @return bool|int
		 */
		public function insert(String $tblName, array $insertMappings)
		{

			// Cancel if no values to be inserted were passed
			if(empty($insertMappings)) return false;

			// Manipulate values and build the query statement
			$tblName = $this->applyQuotesToIdentifier($tblName);
			$columnNames = array_keys($insertMappings);
			$columnNames = array_map([$this, "applyQuotesToIdentifier"], $columnNames);

			$columnList = implode(', ', $columnNames);
			$values = array_fill(0, count($insertMappings), '?');
			$placeholderList = implode(', ', $values);

			$statement = "INSERT INTO $tblName ($columnList) VALUES ($placeholderList);";

			// Finish by executing the statement
			return $this->exec($statement, array_values($insertMappings));

		} // public function insert()

		/**
		 * Update Abstraction, part of CRUD operations
		 *
		 * @param String $tblName
		 * @param array  $updateMapping
		 * @param array  $whereMapping
		 *
		 * @return bool|int
		 */
		public function update(String $tblName, array $updateMapping, array $whereMapping)
		{

			// Cancel if no values for essential parameters were passed
			if(empty($updateMapping) || empty($whereMapping)) return false;

			// Escape the table name and prepare variables for the update mapping analysis
			$tblName = $this->applyQuotesToIdentifier($tblName);
			$bindValues = [];
			$setDirectives = [];

			// For each mapping of a column name to its respective new value
			foreach($updateMapping as $updateCol => $updateVal)
			{

				if(is_int($updateCol) && !hrefp_isSizedString($updateCol)) continue;

				$setDirectives[] = $this->applyQuotesToIdentifier($updateCol) . ' = ?';
				$bindValues[] = $updateVal;

			}

			// Prepare a variable for the where mapping analysis
			$wherePredicates = [];

			// For each mapping of a column name to its respective value to filter by
			foreach($whereMapping as $whereCol => $whereVal)
			{

				$wherePredicates[] = $this->applyQuotesToIdentifier($whereCol) . ' = ?';
				$bindValues[] = $whereVal;

			}

			// Build the full statement (still using placeholders)
			$statement = 'UPDATE '.$tblName.' SET '.implode(', ', $setDirectives).' WHERE '.implode(' AND ', $wherePredicates).';';

			// Execute the parameterized statement and pass the values to be bound to it
			return $this->exec($statement, $bindValues);

		} // public function update()

		/**
		 * Delete Abstraction, part of CRUD operations
		 *
		 * @param String $tblName
		 * @param array  $whereMappings
		 *
		 * @return bool|int
		 */
		public function delete(String $tblName, array $whereMappings)
		{

			// If no values were passed that could be used to filter, cancel
			if(empty($whereMappings)) return false;

			// Escape the table name and init function variables
			$tblName = $this->applyQuotesToIdentifier($tblName);
			$bindValues = [];
			$wherePredicates = [];

			// Process the whereMappings
			foreach($whereMappings as $whereCol => $whereVal)
			{

				$wherePredicates[] = $this->applyQuotesToIdentifier($whereCol) . ' = ?';
				$bindValues[] = $whereVal;

			}

			// Build the full statement (still using placeholders)
			$statement = 'DELETE FROM '.$tblName.' WHERE '.implode(' AND ', $wherePredicates).';';

			// Execute the parameterized statement and supply the values to be bound to it
			return $this->exec($statement, $bindValues);

		} // public function delete()

		/**
		 * Executes a provided statement
		 *
		 * @param String     $statement
		 * @param array|null $bindValues
		 *
		 * @return int                      The number of rows affected by the query
		 */
		public function exec(String $statement, array $bindValues = null)
		{

			// Try to prepare the statement
			try{
				$statement = str_replace(";", "", $statement);
				$stmt = $this->pdo->prepare($statement);
			}
			catch (PDOException $e) {
				$GLOBALS['hrefp_err']->throwFatalError("A fatal error occured while preparing a pdo statement.", $e);
			}

			// Try to execute the statement
			try {
				$stmt->execute($bindValues);
			}
			catch (PDOException $e) {
				$GLOBALS['hrefp_err']->throwFatalError("A fatal error occured while executing a pdo statement.", $e);
			}

			// End the function call by providing the rows affected
			return $stmt->rowCount();

		} // public function exec()

		/**
		 * Selects from the database using the specified query and returns what the supplied callback extracts from the result set
		 *
		 * You should not include any dynamic input in the query
		 *
		 * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the third argument
		 *
		 * @param callable $callback        The callback that receives the executed statement and can then extract and return the desired results
		 * @param string $query             The query to select with
		 * @param array|null $bindValues    The values to bind as replacements for the `?` characters in the query                                      OPTIONAL
		 *
		 * @return mixed                    Whatever the callback has extracted and returned from the result set
		 */
		private function selectInternal(callable $callback, String $query, array $bindValues = null)
		{

			// Try to prepare the statement
			try{
				$stmt = $this->pdo->prepare($query);
			}
			catch (PDOException $e) {
				$GLOBALS['hrefp_err']->throwFatalError("A fatal error occured while preparing a pdo statement.", $e);
			}

			// Try to bind the supplied values and execute the query
			try {
				$stmt->execute($bindValues);
			}
			catch (PDOException $e) {
				$GLOBALS['hrefp_err']->throwFatalError("A fatal error occured while executing a pdo statement.", $e);
			}

			// Apply the passed callback and fetch the results
			$results = $callback($stmt);

			// Assess the result and end the function call
			if(empty($results) && $stmt->rowCount() === 0) return null;
			else return $results;

		} // private function selectInternal()

		/**
		 * Escapes an e.g. tablename for correct sql syntax
		 *
		 * @param $identifier
		 *
		 * @return string
		 */
		public function applyQuotesToIdentifier($identifier)
		{

			$char = '`';

			return $char . str_replace($char, $char . $char, $identifier) . $char;

		} // public function applyQuotesToIdentifier()

	} // final class hrefp_db