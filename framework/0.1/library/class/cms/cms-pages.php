<?php

	class cms_pages_base extends check {

		//--------------------------------------------------
		// Variables

			private $cache_children = array();
			private $cache_page_ids = array();

		//--------------------------------------------------
		// Listing

			public static function children_get($folder_url) {

				$obj = cms_pages::instance_get();

				if (isset($obj->cache_children[$folder_url])) {
					return $obj->cache_children[$folder_url];
				}

				$folder_id = cms_pages::page_id_get($folder_url);

				$db = db_get();

				$sql = 'SELECT
							cp.id,
							cp.ref
						FROM
							' . DB_PREFIX . 'cms_page AS cp
						WHERE
							cp.parent_id = "' . $db->escape($folder_id) . '" AND
							cp.deleted = "0000-00-00 00:00:00"
						ORDER BY
							cp.sort,
							cp.ref';

				foreach ($db->fetch_all($sql) as $row) {

					$page_url = $folder_url . $row['ref'] . '/';

					$pages[] = array(
							'ref' => $row['ref'],
							'url' => $page_url,
							'title' => cms_page_title($page_url),
						);

				}

				$obj->cache_children[$folder_url] = $pages;

				return $pages;

			}

		//--------------------------------------------------
		// URL to ID conversion functions

			public static function page_url_get($id) {

				// $k = 0;
				// $pageUrl = '/';

				// do {

				// 	$db->query('SELECT
				// 					parent_id,
				// 					ref
				// 				FROM
				// 					' . DB_T_PREFIX . 'page
				// 				WHERE
				// 					id = "' . $db->escape($pageId) . '" AND
				// 					deleted = "0000-00-00 00:00:00"');

				// 	if ($row = $db->fetchAssoc()) {
				// 		$pageId = $row['parent_id'];
				// 		$pageUrl = '/' . $row['ref'] . $pageUrl;
				// 	} else {
				// 		return NULL;
				// 	}

				// } while ($pageId > 0 && $k++ < 10);

				// if ($pageUrl == '/home/') {
				// 	return '/';
				// } else {
				// 	return $pageUrl;
				// }

			}

			public static function page_id_get($url) {

				$obj = cms_pages::instance_get();

				$db = db_get();

				$folders = path_to_array($url);

				if (count($folders) == 0) { // Home page
					$folders = array('home');
				}

				$page_id = 0;

				foreach ($folders as $folder) {

					if (array_key_exists($folder, $obj->cache_page_ids)) {

						$page_id = $obj->cache_page_ids[$folder];

					} else {

						$sql = 'SELECT
									cp.id
								FROM
									' . DB_PREFIX . 'cms_page AS cp
								WHERE
									cp.parent_id = "' . $db->escape($page_id) . '" AND
									cp.ref = "' . $db->escape($folder) . '" AND
									cp.deleted = "0000-00-00 00:00:00"';

						if ($row = $db->fetch($sql)) {
							$page_id = $row['id'];
						} else {
							$page_id = NULL;
						}

						$obj->cache_page_ids[$folder] = $page_id;

					}

					if ($page_id === NULL) {
						break;
					}

				}

				return $page_id;

			}

		//--------------------------------------------------
		// Singleton

			private static function instance_get() {
				static $instance = NULL;
				if (!$instance) {
					$instance = new cms_pages();
				}
				return $instance;
			}

			final private function __construct() {
				// Being private prevents direct creation of object.
			}

			final private function __clone() {
				trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
			}

	}

?>