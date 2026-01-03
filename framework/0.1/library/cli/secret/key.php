<?php

/*

	public static function key_get($name, $key_index = NULL) {

			// $pass = secret::key_get('example.key', 'all');
			// $pass = secret::key_get('example.key', 1);
			// $pass = secret::key_get('example.key');

		exit_with_error('Cannot return keys at the moment'); // TODO [secret-keys] - Need to check how keys need to be stored, especially with symmetric vs asymmetric... ref $asymmetric_type

		// $obj = secret::instance_get();
		//
		// if (!is_array($obj->variables) || !array_key_exists($name, $obj->variables)) {
		// 	throw new error_exception('Unknown secret "' . $name . '" when using secret::key_get().');
		// }
		//
		// $type = ($obj->variables[$name]['type'] ?? NULL);
		// if ($type !== 'key') {
		// 	throw new error_exception('When using secret::key_get(), it must be to get a secret the type "key"; not ' . debug_dump($type), 'i.e. $secret[\'' . $name . '\'] = [\'type\' => \'key\']');
		// }
		//
		// $key_type = ($obj->variables[$name]['key_type'] ?? NULL);
		// if ($key_type === NULL) {
		// 	$key_type = 'symmetric';
		// } else if ($key_type !== 'symmetric' && $key_type !== 'asymmetric') {
		// 	throw new error_exception('The secret::get() can only work return a secret with the type "key", not ' . debug_dump($key_type));
		// }
		//
		// if ($obj->data_encoded === NULL) {
		// 	self::_data_load();
		// }
		//
		// $keys = ($obj->data_encoded[$name]['keys'] ?? NULL);
		//
		// if ($key_index === 'all') {
		// 	$return = [];
		// 	foreach ($keys as $id => $key) { // Cannot use array_column(), as it re-indexes the array.
		// 		$return[$id] = ... Only return key_secret (and maybe key_public)?
		// 	}
		// 	return $return;
		// }
		//
		// if ($keys === NULL) {
		// 	$keys = [];
		// }
		//
		// if ($key_index === NULL) {
		// 	end($keys);
		// 	$key_index = key($keys);
		// }
		//
		// if ($key_index === NULL || !array_key_exists($key_index, $keys)) {
		// 	throw new error_exception('Cannot find the encryption key with the specified ID.', 'Key Name: ' . $name . "\n" . 'Key ID: ' . debug_dump($key_index));
		// }
		//
		//
		// $selected_key_type = ($keys[$key_index]['key_type'] ?? NULL);
		//
		// if ($selected_key_type !== $obj->variables[$name]['key_type']) {
		// 	throw new error_exception('The encryption key found is "' . $selected_key_type . '", but it should be "' . $obj->variables[$name]['key_type'] . '".', 'Key Name: ' . $name . "\n" . 'Key ID: ' . debug_dump($key_index));
		// }
		//
		// if ($selected_key_type == 'symmetric') {
		//
		// 	return $keys[$key_index]['key_secret'];
		//
		// } else if ($selected_key_type == 'asymmetric') {
		//
		// 	return [
		// 			$keys[$key_index]['key_public'],
		// 			$keys[$key_index]['key_secret'],
		// 		];
		//
		// } else {
		//
		// 	throw new error_exception('The secret key type "' . $selected_key_type . '" is unrecognised for "' . $name . '".');
		//
		// }

	}


/*



/*--------------------------------------------------*/




/*

	protected $key_path = '/etc/prime-config-key';



	//--------------------------------------------------
	// File exists

		if (!is_file($this->key_path)) {

// TODO [secret] - Creating /etc/prime-config-key

// - If already root, do this automatically? or after a prompt?
// - If not root, use the shell script, via $this->key_create(), $command->exec(), with sudo?
// - Otherwise print contents to create file.

			echo "\n";
			echo 'Missing key file: ' . $this->key_path . "\n";
			echo "\n";
			$this->key_example_print();
			exit();

		}

	//--------------------------------------------------
	// Permission checks

		$config_key_owner = fileowner($this->key_path);
		$config_key_group = filegroup($this->key_path);
		$config_key_permissions = substr(sprintf('%o', fileperms($this->key_path)), -4);

		$permission_changes = [];
		if ($config_key_owner != 0 && SERVER != 'stage') $permission_changes[] = 'chown 0 ' . $this->key_path;
		if ($config_key_group != 0) $permission_changes[] = 'chgrp 0 ' . $this->key_path;
		if ($config_key_permissions !== '0400') $permission_changes[] = 'chmod 0400 ' . $this->key_path;
		if (count($permission_changes) > 0) {
			echo "\n";
			echo "\033[1;31m" . 'Warning:' . "\033[0m" . ' The config key file should use:' . "\n";
			echo "\n";
			foreach ($permission_changes as $permission_change) {
				echo $permission_change . "\n";
			}
			echo "\n";
			if (SERVER != 'stage') {
				return;
			}
		}

	//--------------------------------------------------
	// Is loaded into Apache

		if (is_dir('/usr/local/opt/httpd/bin')) {
			$envvars_path = '/usr/local/opt/httpd/bin/envvars';
		} else if (is_dir('/opt/homebrew/opt/httpd/bin')) {
			$envvars_path = '/opt/homebrew/opt/httpd/bin/envvars';
		} else {
			$envvars_path = '/etc/apache2/envvars';
		}

		if (!is_file($envvars_path)) {
			echo "\n";
			echo 'Cannot find Apache envvars file: ' . $envvars_path . "\n";
			echo "\n";
			return;
		}

		$envvars_content = @file_get_contents($envvars_path);
		$envvars_line = '. ' . $this->key_path;

		if (strpos($envvars_content, $envvars_line) === false) {
			echo "\n";
			echo 'Missing config key file in Apache envvars file: ' . $envvars_path . "\n";
			echo "\n";
			echo '##########' . "\n";
			echo $envvars_line . "\n";
			echo '##########' . "\n";
			echo "\n";
			echo 'Your Apache config should also include:' . "\n";
			echo "\n";
			echo '  <VirtualHost>' . "\n";
			echo '    ...' . "\n";
			echo '    SetEnv PRIME_CONFIG_KEY ${PRIME_CONFIG_KEY}' . "\n";
			echo '  </VirtualHost>' . "\n";
			echo "\n";
			return;
		}

*/




	//--------------------------------------------------
	/*

		//--------------------------------------------------
		// Key

			public function key_example_print() {
				echo 'Try creating with the following line:' . "\n";
				echo "\n";
				echo '##########' . "\n";
				echo $this->key_example();
				echo '##########' . "\n";
			}

			public function key_example() {
				return 'export PRIME_CONFIG_KEY=' . encryption::key_symmetric_create();
			}

			// private function key_create() {
			//
			// 	$new_key_value = encryption::key_symmetric_create();
			// 	$new_key_identifier = encryption::key_identifier_get($new_key_value);
			//
			// 	$command = new command();
			// 	$command->stdin_set($new_key_value);
			// 	$command->exec('sudo -k', [
			// 			FRAMEWORK_ROOT . '/library/cli/secret/key-new.sh',
			// 			$new_key_identifier,
			// 			$this->key_path,
			// 			1,
			// 		]);
			//
			// 	debug($command->stdout_get());
			//
			// }
			//
			// private function key_cleanup($key) {
			//
			// 	if (($pos = strpos($key, '=')) !== false) { // Remove the export + name prefix
			// 		$key = substr($key, ($pos + 1));
			// 	}
			//
			// 	$key = trim($key);
			//
			// 	if ($key !== '' && !in_array(encryption::key_type_get($key), ['KS1', 'KS2'])) {
			// 		exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised key type.' . "\n\n");
			// 	}
			//
			// 	return $key;
			//
			// }

	*/
	//--------------------------------------------------









	//--------------------------------------------------
	// Attempt to get key value

		// private $key_value = NULL;
		//
		// $this->key_value = trim(getenv('PRIME_CONFIG_KEY'));
		//
		// if (!$this->key_value) {
		//
		// 	if (is_readable($this->key_path)) {
		//
		// 		$this->key_value = $this->key_cleanup(file_get_contents($this->key_path));
		//
		// 	} else if (config::get('output.domain')) {
		//
		// 		$this->key_value = NULL; // Use the API
		//
		// 	} else {
		//
		// 		$this->key_value = ''; // Not available
		//
		// 	}
		//
		// }

	//--------------------------------------------------

		// 	if ($this->key_value || $this->key_value === NULL) {
		//
		// 		// The key value has been set, or is NULL (use the API)
		//
		// 	} else if ($action !== 'check') {
		//
		// 		echo "\n";
		// 		echo 'Cannot access config key file ' . $this->key_path . "\n";
		// 		echo "\n";
		// 		echo 'Either:' . "\n";
		// 		echo '1) Set $config[\'output.domain\']' . "\n";
		// 		echo '2) Run via sudo' . "\n";
		// 		echo '3) Enter the key' . "\n";
		// 		echo "\n";
		// 		echo 'Key: ';
		// 		$this->key_value = $this->key_cleanup(fgets(STDIN));
		//
		// 		if ($this->key_value === '') {
		// 			echo "\n";
		// 			if (is_readable($this->key_path)) {
		// 				echo 'Empty key file: ' . $this->key_path . "\n";
		// 				echo "\n";
		// 				$this->key_example_print();
		// 			}
		// 			exit();
		// 		}
		//
		// 	}
		//
		//--------------------------------------------------

?>