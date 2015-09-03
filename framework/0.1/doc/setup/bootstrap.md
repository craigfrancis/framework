
# Bootstrap

The script that gets everything running, nothing for you to do here.

---

For reference, your /app/public/index.php file will run the bootstrap with something like:

	<?php

		define('ROOT', dirname(dirname(dirname(__FILE__))));

		$framework_paths = array(
				ROOT . '/framework/0.1/bootstrap.php', // Local install
				'/path/to/bootstrap.php', // Development
			);

		foreach ($framework_paths as $framework_path) {
			if (is_file($framework_path)) {
				require_once($framework_path);
				exit();
			}
		}

		echo 'Cannot find framework directory: <br /><br />' . "\n";
		foreach ($framework_paths as $framework_path) {
			echo '&#xA0; &#xA0; ' . $framework_path . '<br />' . "\n";
		}

	?>