<?php

	/**
	 * @name: Errorhandler.class
	 * @package: lib > exceptions
	 * @description: Handles thrown exceptions
	 * @author: Florian Götzrath <info@floriangoetzrath.de>
	 */

	// TODO: Write error history to log file on fatal error but keep it limited by certain filesize thresholds

	class ErrorHandler
	{

		/** @var array the capturing of any thrown errors */
		private $err_history;

		/**
		 * ErrorHandler constructor
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

			// Throw the exception
			if(!($exception instanceof Exception)) $exception = new Exception();

			if(WP_DEBUG) throw $exception;
			else die('['.HREFP_NAME.' '.HREFP_VERSION.'] '.$customErrMsg);

		} // public function throwFatalError()

	} // class ErrorHandler