<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(
				'name' => array('default' => 'Unknown'),
			);

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