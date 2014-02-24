<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(
				'name' => array('default' => 'Unknown'),
			);

		// protected $config = array(
		// 		'id'   => array('type' => 'int'),
		// 		'url'  => array('type' => 'url'),
		// 		'url'  => array('type' => 'url', 'default' => './thank-you/'),
		// 		'url'  => array('default' => NULL),
		// 		'name' => array('default' => 'Unknown'),
		// 		'list' => array('default' => array()),
		// 	);

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Resources

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