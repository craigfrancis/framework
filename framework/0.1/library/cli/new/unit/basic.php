<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(
				'name' => array('type' => 'str', 'default' => 'Unknown'),
			);

		// protected $config = array(
		// 		'id'   => array('type' => 'int'),
		// 		'url1' => array('type' => 'url'),
		// 		'url2' => array('type' => 'url', 'default' => './thank-you/'),
		// 		'url3' => array('type' => 'url', 'default' => NULL),
		// 		'name' => array('type' => 'str', 'default' => 'Unknown'),
		// 		'item' => array('type' => 'obj'),
		// 		'list' => array('default' => []),
		// 	);

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

	$unit = unit_add('[CLASS_NAME]', array(
			'name' => 'Test',
		));

/*--------------------------------------------------*/

?>