<?php

	/**
	 * @name: Errorhandler.class
	 * @package: lib > exceptions
	 * @description: Handles thrown exceptions
	 * @author: Florian Götzrath <info@floriangoetzrath.de>
	 */

	class hrefp_ErrorHandler
	{

		/** @var integer messages_threshold field to store the numeric maximum of concurrent message saves */
		const ERR_LOG_FILENAME = "errors.json";
		const ERR_LOG_FILE_SIZE_THRESHOLD_MB = 10;

		/** @var array the capturing of any thrown errors */
		private $err_history;

		/**
		 * hrefp_ErrorHandler constructor
		 */
		function __construct()
		{

			$this->err_history = array();

		} // private function __construct()

		public function getErrHistory()
		{

			return $this->err_history;

		} // public function getErrHistory()

		/**
		 * Registers a new error to the history
		 *
		 * @param $exception
		 * @param $customErrMsg
		 *
		 * @return int              The new number of elements in the error log
		 */
		protected function queueErrToHistory(String $customErrMsg, Exception $exception): int
		{

			// Struct the new entry
			$newEntry = array(
				"time" => (@date('[d/M/Y:H:i:s]')),
				"exception" => $exception,
				"custom_err_msg" => $customErrMsg,
				"hrefp_version" => HREFP_VERSION
			);

			// Check for duplicate
			if(in_array($newEntry, $this->err_history)) return false;

			// Push the new entry to the log
			return array_push($this->err_history, $newEntry);

		} // protected function queueErrToHistory()

		/**
		 * Updates the log file with the latest errors thrown
		 * 
		 * @note: If the log file size surpasses a threshold, it will be cleared and only the latest error will be written to it
		 */
		protected function writeErrorsToLog()
		{

			// Define the log path
			$logFilePath = HREFP_LOG_PATH . '/' . static::ERR_LOG_FILENAME;

			// Determine, whether the file only fits the latest errors sizewise
			$clearFile = false;

			if(file_exists($logFilePath)) 
				$clearFile = number_format(filesize($logFilePath) / 1048576, 2) > static::ERR_LOG_FILE_SIZE_THRESHOLD_MB;	

			// Clear the file in case of it being too large
			if($clearFile) file_put_contents($logFilePath, json_encode(array()));

			// Write to the log file
			file_put_contents($logFilePath, json_encode($this->err_history, JSON_PRETTY_PRINT));

			// Clear the errors stored in this instance
			$this->err_history = array();

		} // protected function writeErrorsToLog()

		/**
		 * Adds an error that will cause the current interaction to die
		 *
		 * @param String         $customErrMsg
		 * @param Exception|null $exception
		 *
		 * @throws Exception
		 */
		public function throwFatalError(String $customErrMsg, $exception = null)
		{

			// Save the error to the history
			$this->queueErrToHistory($customErrMsg, $exception);

			// Update the log file
			$this->writeErrorsToLog();

			// Throw the exception
			if(!($exception instanceof Exception)) $exception = new Exception();

			if(WP_DEBUG) throw $exception;
			else die('['.HREFP_NAME.' '.HREFP_VERSION.'] '.$customErrMsg);

		} // public function throwFatalError()

	} // class hrefp_ErrorHandler