<?php

	echo "\n";

//--------------------------------------------------
// Config

	function config_key_file_example() {
		echo '##########' . "\n";
		echo 'export PRIME_CONFIG_KEY="' . encryption::key_symmetric_create() . '"' . "\n";
		echo '##########' . "\n\n";
	}

//--------------------------------------------------
// Key file exists

	$config_key_path = '/etc/prime-config-key';
	if (!is_file($config_key_path)) {
		echo 'Missing key file: ' . $config_key_path . "\n\n";
		echo 'Try creating with the following line:' . "\n\n";
		config_key_file_example();
		exit();
	}

//--------------------------------------------------
// Is loaded into Apache

	if (is_dir('/usr/local/opt/httpd/bin')) {
		$envvars_path = '/usr/local/opt/httpd/bin/envvars';
	} else {
		$envvars_path = '/etc/apache2/envvars';
	}

	if (!is_file($envvars_path)) {
		echo 'Cannot find Apache envvars file: ' . $envvars_path . "\n\n";
		exit();
	}

	$envvars_content = @file_get_contents($envvars_path);
	$envvars_line = '. ' . $config_key_path;

	if (strpos($envvars_content, $envvars_line) === false) {
		echo 'Missing config key file in Apache envvars file: ' . $envvars_path . "\n\n";
		echo '##########' . "\n";
		echo $envvars_line . "\n";
		echo '##########' . "\n\n";
		echo 'Your Apache config should also include:' . "\n\n";
		echo '  <VirtualHost>' . "\n";
		echo '    ...' . "\n";
		echo '    SetEnv PRIME_CONFIG_KEY ${PRIME_CONFIG_KEY}' . "\n";
		echo '  </VirtualHost>' . "\n\n";
		exit();
	}

//--------------------------------------------------
// Content, and permission checks

	$config_key_owner = fileowner($config_key_path);
	$config_key_group = filegroup($config_key_path);
	$config_key_permissions = substr(sprintf('%o', fileperms($config_key_path)), -4);
	$config_key_readable = is_readable($config_key_path);

	if ($config_key_readable) {

		$permission_changes = [];
		if ($config_key_owner != 0) $permission_changes[] = 'chown 0 ' . $config_key_path;
		if ($config_key_group != 0) $permission_changes[] = 'chgrp 0 ' . $config_key_path;
		if ($config_key_permissions != '0400') $permission_changes[] = 'chmod 0400 ' . $config_key_path;
		if (count($permission_changes) > 0) {
			echo "\033[1;31m" . 'Warning:' . "\033[0m" . ' The config key file should use:' . "\n";
			foreach ($permission_changes as $permission_change) {
				echo '  ' . $permission_change . "\n";
			}
			echo "\n";
		}

		$config_key_content = file_get_contents($config_key_path);

	} else {

		echo 'Cannot access config key file ' . $config_key_path . "\n\n";
		echo 'Either run via sudo, or enter the key.' . "\n\n";
		echo 'Key: ';
		$config_key_content = trim(fgets(STDIN));
		echo "\n";

	}

	if (($pos = strpos($config_key_content, '=')) !== false) {
		$config_key_content = substr($config_key_content, ($pos + 1));
	}

	$config_key_content = trim($config_key_content);

	if ($config_key_content == '') {
		if ($config_key_readable) {
			echo 'Empty key file: ' . $config_key_path . "\n\n";
			echo 'Try creating with the following line:' . "\n\n";
			config_key_file_example();
		}
		exit();
	}

	putenv('PRIME_CONFIG_KEY=' . $config_key_content);

//--------------------------------------------------
// Encrypt

	echo 'Value: ';

	$value = trim(fgets(STDIN));

	$encrypted = config::get_encrypted($value);

	echo "\n" . '$config_encrypted[\'' . SERVER . '\'][\'name\'] = \'' . $encrypted . '\';' . "\n\n";

?>