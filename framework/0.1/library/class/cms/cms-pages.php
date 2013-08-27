<?php

	class cms_pages_base extends check {

		//--------------------------------------------------
		// URL to ID conversion functions

			public static function page_url_get($id) {
			}

			public static function page_id_get($url) {

				//--------------------------------------------------
				// Convert url to array

					$folders = path_to_array($url);

				//--------------------------------------------------
				// Home page

					$db = db_get();

					if (count($folders) == 0) {

						$sql = 'SELECT
									cp.id
								FROM
									' . DB_PREFIX . 'cms_page AS cp
								WHERE
									cp.parent_id = "0" AND
									cp.url = "home" AND
									cp.deleted = "0000-00-00 00:00:00"';

						if ($row = $db->fetch($sql)) {
							return $row['id'];
						} else {
							return NULL;
						}

					}

				//--------------------------------------------------
				// Sub pages

					$page_id = 0;

					foreach ($folders as $folder) {

						$sql = 'SELECT
									cp.id
								FROM
									' . DB_PREFIX . 'cms_page AS cp
								WHERE
									cp.parent_id = "' . $db->escape($page_id) . '" AND
									cp.url = "' . $db->escape($folder) . '" AND
									cp.deleted = "0000-00-00 00:00:00"';

						if ($row = $db->fetch($sql)) {
							$page_id = $row['id'];
						} else {
							return NULL;
						}

					}

					return $page_id;

			}

		//--------------------------------------------------
		// Factory singleton

			final private function __construct() {
				// Being private prevents direct creation of object.
			}

			final private function __clone() {
				trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
			}

	}

?>