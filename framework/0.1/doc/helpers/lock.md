
# Lock helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/main/framework/0.1/library/class/lock.php).

Often used with the [loading helper](../../doc/helpers/loading.md), it allows you to obtain a lock, and ensures that no-one else can edit a particular resource.

---

## Example setup

For a site wide resource:

	$lock = new lock('example');

For a particular resource:

	$lock = new lock('item', $id);

---

## Example usage

Checks to see someone has the lock, but doesn't try to open if not:

	if ($lock->locked()) {
	}

Check to see if we have the lock, but doesn't try to open if not:

	if ($lock->check()) {
	}

Try to open the lock, set data, and close afterwards.

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

		$this->set('name', $lock->data_get('name'));

	}
