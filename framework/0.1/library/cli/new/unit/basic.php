<?php

	class [CLASS_NAME]_unit extends unit {

		public function setup($config) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'name' => 'Unknown',
					), $config);

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