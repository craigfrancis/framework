<?php

	class [CLASS_NAME] extends unit {

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

	unit_add('[CLASS_NAME]', array(
			'name' => 'Test',
		));

/*--------------------------------------------------*/

?>