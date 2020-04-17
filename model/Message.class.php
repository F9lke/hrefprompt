<?php

	/**
	 * @name: Message
	 * @package: model
	 * @description: Model representation of the prompt messages
	 * @author: Florian Götzrath <info@floriangoetzrath.de>
	 */

	class Message
	{

		/** @var integer messages_threshold field to store the numeric maximum of concurrent message saves */
		const MESSAGES_THRESHOLD = 6;

		/** @var string table_name field to store the name of the model specific table */
		const TABLE_NAME = "prompt_messages";
		/** @var string table_prefix field to store the table specific prefix */
		const TABLE_PREFIX = "msg_";

		/** @var array $data field to store model specific data */
		public $data;

		/**
		 * Creates the table if this has not been done before
		 */
		public function installIfNecessary()
		{

			if(!(bool)($GLOBALS['hrefp_db']->exec('SHOW TABLES like "'.static::TABLE_NAME.'";')))
			{

				$default_msg = __("Die von Ihnen besuchte Seite versucht, Sie an eine andere Seite weiterzuleiten. <br> Falls Sie diese Seite nicht besuchen möchten, können Sie hier [abbrechen]abbrechen[/abbrechen].<br>Mit [weiterleiten]weiterleiten[/weiterleiten] verlassen Sie die Seite und werden weitergeleitet.", "hrefp");

				$GLOBALS['hrefp_db']->exec("
					CREATE TABLE `".static::TABLE_NAME."` (
                        `".static::TABLE_PREFIX."id` int(10) NOT NULL,
                        `".static::TABLE_PREFIX."content` text CHARACTER SET utf8 NOT NULL,
                        `".static::TABLE_PREFIX."created_at` datetime DEFAULT CURRENT_TIMESTAMP
					) ENGINE=InnoDB DEFAULT CHARSET=latin1;
				");

				$GLOBALS['hrefp_db']->exec("ALTER TABLE `".static::TABLE_NAME."` ADD PRIMARY KEY (`".static::TABLE_PREFIX."id`);");
				$GLOBALS['hrefp_db']->exec("ALTER TABLE `".static::TABLE_NAME."` MODIFY `".static::TABLE_PREFIX."id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;");

				$GLOBALS['hrefp_db']->exec("
					INSERT INTO `".static::TABLE_NAME."` (`".static::TABLE_PREFIX."id`, `".static::TABLE_PREFIX."content`, `".static::TABLE_PREFIX."created_at`) VALUES
					(1, '".$default_msg."', '".hrefp_dateToDBDate(time())."')
				");

			}

		} // public function installIfNecessary()

		/**
		 * Loads all saved messages to the class instance
		 */
		public function loadAll()
		{

			if(hrefp_isSizedArray($this->data['all_messages'])) return false;

			$res = $GLOBALS['hrefp_db']->select("SELECT * FROM " . static::TABLE_NAME);

			$this->data['all_messages'] = hrefp_isSizedArray($res) ? $res : array();

		} // public function loadAll()

		/**
		 * Loads the latest message saved to the database
		 */
		public function loadLatestMessage()
		{

			if(!hrefp_isSizedArray($this->data['all_messages'])) $this->loadAll();

			$this->data['latest_message'] = count($this->data['all_messages']) > 0
				? end($this->data['all_messages'])
				: false;

		} // public function loadLatestMessage()

		public function addMessage(String $content)
		{

			// Reset the data field to make another load on action necessary
			$this->data = null;

			// Save the new message to the database
			return $GLOBALS['hrefp_db']->insert(static::TABLE_NAME, array(
				static::TABLE_PREFIX."content" => $content
			));

		} // public function addMessage()

		public function removeMessageById(Int $id)
		{

			// Reset the data field to make another load on action necessary
			$this->data = null;

			// Remove a message by id
			return $GLOBALS['hrefp_db']->delete(static::TABLE_NAME, array(
				static::TABLE_PREFIX."id" => $id
			));

		} // public function removeMessage()

		public function getNumberOfSavedMessages()
		{

			if(!hrefp_isSizedArray($this->data['all_messages'])) $this->loadAll();

			return count($this->data['all_messages']);

		} // public function getNumberOfSavedMessages()

	} // class Message