<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = [
				'name' => ['type' => 'str', 'default' => 'Unknown'],
			];

		// protected $config = [
		// 		'id'   => ['type' => 'int'],
		// 		'url1' => ['type' => 'url'],
		// 		'url2' => ['type' => 'url', 'default' => './thank-you/'],
		// 		'url3' => ['type' => 'url', 'default' => NULL],
		// 		'name' => ['type' => 'str', 'default' => 'Unknown'],
		// 		'item' => ['type' => 'obj'],
		// 		'list' => ['default' => []],
		// 	];

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				// $db = db_get();

			//--------------------------------------------------
			// Variables

				$this->set('name', $config['name']);

		}

	}

/*--------------------------------------------------*/
/* Example

	$unit = unit_add('[CLASS_NAME]', [
			'name' => 'Test',
		]);

/*--------------------------------------------------*/

?>