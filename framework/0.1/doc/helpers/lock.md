
# Lock

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/lock.php).

	//--------------------------------------------------
	// Example setup

		$lock = new lock('example');

		$lock = new lock('item', $id);

	//--------------------------------------------------
	// Example usage

		if ($lock->check()) {
			// Checks to see if we have the lock, but doesn't try to open if not
		}

		if ($lock->open()) {

			$lock->data_set('name', 'Craig');

			$lock->data_set(array(
					'field_1' => 'AAA',
					'field_2' => 'BBB',
					'field_3' => 'CCC',
				));

			sleep(5);

			if (!$lock->open()) {
				// Check to see if we still have the lock (not expired)
			}

			$lock->time_out_set(30); // If more time is needed

			sleep(5);

			$lock->close();

		} else {

			$response->set('name', $lock->data_get('name'));

		}
