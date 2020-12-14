<?php

	/**
	 * @name: MainController.class
	 * @package: controller
	 * @description: Controller to grant fundamental, system functionality
	 * @author: Florian Götzrath <info@floriangoetzrath.de>
	 */

	require_once(realpath(__DIR__).'/../hrefprompt.php');

	class hrefp_MainController
	{

		/* @var $isAdmin boolean field to store whether the request originates from an administrative interface */
		public $isAdmin;

		/* @var $db db field to store the db connection */
		public $db;
		/* @var $models array field to store model instances */
		public $models;
		/* @var $data array field to store controller related information */
		public $data;
		/* @var $view array field to store values for the eventual use */
		public $view;

		/**
		 * hrefp_MainController constructor
		 */
		function __construct()
		{

			$this->isAdmin = is_admin();

			$this->db = &$GLOBALS['hrefp_db'];
			$this->data = array();
			$this->view = array();

			$this->loadModels();

		} // public function __construct()

		/**
		 * Fires after WordPress has finished loading but before any headers are sent
		 *
		 * Utilized to load necessary resources
		 *
		 * @see https://developer.wordpress.org/reference/hooks/init/
		 */
		public function init()
		{

			if(!$this->isAdmin)
			{

				// If a non admin page is getting loaded there is no intention of integrating any routes --> enqueue_resources has to be called manually
				$this->enqueue_resources();

				// Pass essential values to the scripts
				wp_localize_script("prompter_script", "prompter", array(
					"msg" => array(
						"title" => __("Weiterleitungshinweis", "hrefp"),
						"content" => static::replacePromptMessageTemplating(
							$this->getPromptMessage(get_option("hrefp_confirmation_style") === "confirmation")
						),
						"actions" => __("Aktionen", "hrefp"),
						"close" => __("Schließen", "hrefp"),
						"cancel" => __("Abbrechen", "hrefp"),
						"redirect" => __("Weiterleiten", "hrefp")
					),
					"confstyle" => get_option("hrefp_confirmation_style")
				));

			}

		} // public function init()

		/**
		 * Fires before the administration menu loads in the admin area
		 *
		 * Registers the plugin menu in the backend
		 *
		 * @see https://developer.wordpress.org/reference/hooks/admin_menu/
		 */
		public function admin_menu()
		{

			// Register the plugin menu entry
			add_menu_page( HREFP_NAME, __("Linkabfrage", "hrefp"), "edit_posts", "hrefp-admin", "hrefp_MainController::dispatch", null);

		} // public function admin_menu()

		/**
		 * Includes plugin related resources
		 *
		 * @return bool
		 */
		public function enqueue_resources(): bool
		{

			// Check if the resources have already been added
			if(hrefp_isset_true($GLOBALS['resources_added'])) return false;

			// Load resources independent of the request's origin regarding frontend or backend
			wp_enqueue_style("hrefp_mainstyle", HREFP_PUBLIC_URL.'/css/main.css');
			wp_enqueue_style("hrefp_bootratp_css", HREFP_LIBRARY_URL.'/ui/bootstrap/css/bootstrap.min.css');

			// Load resources based on wether the current request is coming from the backend or the frontend
			if($this->isAdmin)
			{

				wp_enqueue_style('hrefp_adminstyle', HREFP_PUBLIC_URL.'/css/admin.css');

			}
			else
			{

				wp_enqueue_script("prompter_script", HREFP_PUBLIC_URL.'/js/prompter.transpiled.js');
				wp_enqueue_style('hrefp_frontendstyle', HREFP_PUBLIC_URL.'/css/frontend.css');

			}

			// Flag the resources as loaded
			$GLOBALS['resources_added'] = true;

			return true;

		} // public function enqueue_resources_for_route()

		/**
		 * Handles and distributes requests received
		 */
		public static function dispatch()
		{

			$MC = new hrefp_MainController();
			$_REQUEST['page'] = strtolower(sanitize_text_field($_REQUEST['page']));

			// Cancel if the request did not originate from the WordPres backend
			if(!$MC->isAdmin) return false;

			// Distribute the request to the corresponding tempalte
			switch($_REQUEST['page'])
			{

				case "hrefp-admin":
					$MC->view['route'] = "hrefp-admin";
					$MC->renderTemplate(HREFP_VIEWS_PATH.'/admin/configuration.phtml');
					break;

				default:
				case "hrefp-404":
					$MC->view['route'] = "hrefp-404";
					$MC->renderTemplate(HREFP_VIEWS_PATH.'/admin/404.phtml');
					break;

			}

			// Call necessary functions
			switch(strtolower($_REQUEST['action']))
			{

				case "prompt_msg_submit":
					$MC->updatePromptMessage($_REQUEST['prompt_msg']);
					break;

				case "confirmation_style_update":
					$MC->updateCofirmationStyle((bool)$_REQUEST['confirmation_style_modal']);
					break;

				case "reuse_prompt_msg":
					$MC->reusePromptMessage(sanitize_key($_REQUEST['prompt_msg_id']));
					break;

			}

		} // public static function dispatch()

		/**
		 * Loads an instance of each model available
		 */
		public function loadModels()
		{

			// Get all model files
			$availableModels = array_diff(scandir(HREFP_MODEL_PATH), array(".", ".."));

			// Iterate over each model file, require it and instantiate the class structure
			foreach($availableModels as $model_file)
			{
				if(!is_dir(HREFP_MODEL_PATH."/".$model_file) && preg_match("/(.*)\.class\.php/i", $model_file))
				{

					// Include and instantiate the model
					require_once(HREFP_MODEL_PATH."/".$model_file);

					$className = str_replace(".class.php", "", $model_file);
					$this->models[strtolower($className)] = (new $className());

					// Create the corresponding table of the model if necessary
					$this->models[strtolower($className)]->installIfNecessary();

				}
			}

		} // public function loadModels()

		/**
		 * Queues a file to be rendered
		 *
		 * @param String $filePath           The path to the template relative to the project root
		 * @param array  $templateParams     Additional variables that can be used in the template                              OPTIONAL
		 * @param bool   $isCriticalTemplate Determines, whether the user shall be redirected to a 404 page on load failure     OPTIONAL
		 *
		 * @return bool|void
		 */
		public function renderTemplate(String $filePath, array $templateParams = array(), bool $isCriticalTemplate = false)
		{

			$isFileAvailable = is_file($filePath) && file_exists($filePath);

			// Apply exclusion criteria
			if(!$isFileAvailable && !$isCriticalTemplate) return false;
			if(!$isFileAvailable && $isCriticalTemplate) return $this->redirect("admin.php?page=hrefp-404", true);

			// Loads stylesheets and scripts of the plugin
			$this->enqueue_resources();

			// Pass a copy of the class instance to enable its use in the template
			$MC = $this;

			// If specified variables were passed to be accesible in the template, store them
			if(hrefp_isSizedArray($templateParams)) $MC->view['template'] = $templateParams;

			// Eventually render the file
			include $filePath;

		} // public function renderTemplate()

		/**
		 * Redirects to a given url
		 *
		 * @param String $url               The url (relative to project root) the user should be redirected to
		 * @param bool   $forceRedirect     Determines, whether the redirect is absolutely necessary and is to be executed even after headers have been sent
		 *
		 * @return bool
		 */
		public function redirect(String $url, Bool $forceRedirect = false)
		{

			$areHeadersSent = (bool)headers_sent();

			// Evaluate if a redirect is even possible and if it has to be implemented via JavaScript
			if($areHeadersSent && !$forceRedirect) return false;
			if($areHeadersSent && $forceRedirect) echo "<script>window.location.replace('".$url."');</script>";

			// Finally change the location the standard way
			if(hrefp_isSizedString($url))
			{

				unset($this->db);
				wp_redirect("Location: " . html_entity_decode($url));
				exit;

			}

		} // public function redirect()

		/**
		 * Gets the active prompt message
		 *
		 * @param bool $tagsStripped	Determines, whether potentially existing html tags should be stripped
		 * 
		 * @return string
		 */
		public function getPromptMessage(bool $tagsStripped = false): String
		{

			// Load the latest message from the database
			$this->models['message']->loadLatestMessage();

			// Return the message or a default one
			$msg = html_entity_decode(
				$this->models['message']->data['latest_message'][Message::TABLE_PREFIX.'content']
			);

			return $tagsStripped ? hrefp_format_sentences(hrefp_strip_tags($msg)) : $msg;

		} // public function getPromptMessage()

		/**
		 * Gets the data of a prompt message by id
		 *
		 * @param Int $id
		 *
		 * @return array|null
		 */
		public function getPromptMessageDataByID(Int $id): array
		{

			// Load all saved messages
			$this->models['message']->loadAll();

			// Search for the data object of the requested message
			$requested_msg = null;

			foreach($this->models['message']->data['all_messages'] as $current_msg)
			{

				if((int)$current_msg[Message::TABLE_PREFIX.'id'] === $id)
					$requested_msg = $current_msg;

			}

			return $requested_msg;

		} // public function getPromptMessageDataByID()

		/**
		 * Gets all prompt messages saved to the database
		 *
		 * @return array
		 */
		public function getAllPromptMessages(): array
		{

			// Ensure that all messages are loaded
			$this->models['message']->loadAll();

			// Decode the msg
			foreach($this->models['message']->data['all_messages'] as $k => $msg_data)
			{

				$msg_data['msg_content'] = html_entity_decode($msg_data['msg_content']);
				$this->models['message']->data['all_messages'][$k] = $msg_data;

			}

			return $this->models['message']->data['all_messages'];

		} // public function getAllPromptMessages()

		/**
		 * Changes the message to prompt the user with when trying to exit the page by getting redirected to an
		 * external one
		 *
		 * Actually simply adds the new message in order to create a sort of history of prompt messages that all be
		 * restored Note that, this "history" only contains the last 5 messages
		 *
		 * @param String $msg The new message
		 *
		 * @return bool|void
		 */
		public function updatePromptMessage(String $msg)
		{

			// Sanitize the message
			$msg = sanitize_text_field(htmlentities($msg));

			// Load all saved messages
			$this->models['message']->loadAll();

			// If the message does not differ to at least one already saved, cancel
			$isAlreadyPresent = false;

			foreach($this->models['message']->data['all_messages'] as $current_msg)
			{

				if($current_msg[Message::TABLE_PREFIX.'content'] === $msg)
					$isAlreadyPresent = true;

			}

			if($isAlreadyPresent) return $this->redirect("?page=hrefp-admin", true);

			// Save the new message to the database
			$this->models['message']->addMessage($msg);

			// Count the number of all messages stored
			$nMessages = $this->models['message']->getNumberOfSavedMessages();

			// If the number of messages surpasses the threshold, delete the oldest
			if($nMessages > Message::MESSAGES_THRESHOLD)
			{

				$this->models['message']->removeMessageById(
					$this->models['message']->data['all_messages'][0][Message::TABLE_PREFIX.'id']
				);

			}

			// Refresh to clear the url parameters and display the changes
			$this->redirect("?page=hrefp-admin", true);

		} // public function updatePromptMessage()

		/**
		 * Updates the application option regarding the prompt frontend style
		 *
		 * @param bool $modalStyleEnabled
		 */
		public function updateCofirmationStyle($modalStyleEnabled)
		{

			// Save the setting
			update_option("hrefp_confirmation_style", ($modalStyleEnabled ? "modal" : "confirmation"));

			// Refresh to clear the url parameters and display the changes
			$this->redirect("?page=hrefp-admin", true);

		} // public function updateCofirmationStyle()

		/**
		 * Reenables an old prompt message as the currently active one
		 *
		 * @param Int $oldMsgID The identifier of the old message that is to be reused
		 *
		 * @return bool
		 */
		public function reusePromptMessage(Int $oldMsgID)
		{

			// Get the data of the message entry
			$requested_msg = $this->getPromptMessageDataByID($oldMsgID);

			// If the requested message is not to be found, cancel
			if(!hrefp_isSizedArray($requested_msg)) return false;

			// If the requested message it the last one saved and thus is already active, cancel
			if(end($this->models['message']->data['all_messages']) === $requested_msg) return false;

			// Delete the requested message from its current position in the database
			$this->models['message']->removeMessageById($requested_msg[Message::TABLE_PREFIX.'id']);

			// Add the message as the last database entry to ensure it is active
			$this->models['message']->addMessage($requested_msg[Message::TABLE_PREFIX.'content']);

			// Refresh to clear the url parameters and display the changes
			$this->redirect("?page=hrefp-admin", true);

		} // public function reusePromptMessage()

		/**
		 * Replaces template placeholders serving the purpose of enabling the use of modal actions within the configurable prompt message content
		 *
		 * @param String $msg
		 *
		 * @return String
		 */
		public static function replacePromptMessageTemplating(String $msg): String
		{

			// Define the possible placeholder values with the corresponding element and classlist
			$placeholders = array(
				"a.modal-redirect.modal-redirect-text" => __("[weiterleiten]", "hrefp"),
				"a.modal-close.modal-close-text" => __("[abbrechen]", "hrefp")
			);

			// Loop through the placeholder values, match them and replace the part of the string accordingly
			foreach($placeholders as $pseudoMarkup => $matchVal)
			{
				if(strpos($msg, $matchVal) !== false)
				{

					// Separate tagname and classname
					$pseudoMarkupParts = explode(".", $pseudoMarkup);
					$classList = implode(" ", array_diff($pseudoMarkupParts, array($pseudoMarkupParts[0])));

					// Build the markup for the closing part of the template
					$closingMatchVal = str_replace("[", "[/", $matchVal);

					// Replace the templating
					$msg = str_replace($matchVal, "<".$pseudoMarkupParts[0]." class='".$classList."'>", $msg);
					$msg = str_replace($closingMatchVal, "</".$pseudoMarkupParts[0].">", $msg);

				}
				else continue;
			}

			return $msg;

		} // public static function replacePromptMessageTemplating()

	} // class hrefp_MainController