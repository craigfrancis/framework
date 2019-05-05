<?php

//--------------------------------------------------
// Parse options

	$options = getopt('r:');

	if (isset($options['r']) && is_dir($options['r'])) {

		if (substr($options['r'], -1) === '/') {
			exit('The project root cannot end with a slash' . "\n");
		}

		define('ROOT', $options['r']);

	} else {

		echo "\n" . './update.php -r "PROJECT_ROOT"' . "\n\n";
		exit();

	}

//--------------------------------------------------
// Config

	define('FRAMEWORK_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Levels added in PHP 7.0
	define('FRAMEWORK_INIT_ONLY', true);

	function load_framework($inc_setup = false) {
		require_once(FRAMEWORK_ROOT . '/bootstrap.php');
		if ($inc_setup) {
			script_run_once(ROOT . '/app/library/setup/setup.php');
		}
	}

//--------------------------------------------------
// WebP

	$webp_command = '/usr/local/bin/cwebp';
	if (!is_executable($webp_command)) {
		$webp_command = NULL;
	}

//--------------------------------------------------
// Project specific watch function

	$watch_path = ROOT . '/app/library/setup/watch.php';
	if (is_file($watch_path)) {
		require_once($watch_path);
	}

	if (!function_exists('watch_update')) {
		function watch_update($path, $modified) {
			return false;
		}
	}

//--------------------------------------------------
// Changed files

	$root_length = strlen(ROOT);
	$files = file(ROOT . '/private/watch/files.txt'); // PRIVATE_ROOT has not been set

	foreach ($files as $file) {

		$file = explode(' ', trim($file), 2);

		if (count($file) === 2 && substr($file[1], 0, $root_length) === ROOT) {

			$path = substr($file[1], $root_length);

			$result = watch_update($path, $file[0]);

			if ($result === false) {

				$file_contents = NULL; // Only load if we are going to process
				$file_dest = NULL;

				$file_ext = strrpos($path, '.');
				$file_ext = ($file_ext !== false ? substr($path, ($file_ext + 1)) : NULL);

				if (substr($path, 0, 17) === '/app/public/a/js/' && $file_ext == 'js') {

					require_once(FRAMEWORK_ROOT . '/vendors/jsmin/jsmin.php');

					$file_contents = file_get_contents(ROOT . $path);
					$file_contents = jsmin::minify($file_contents);

					$file_dest = ROOT . str_replace('/js/', '/min/js/', $path);

					$result = 'Framework JS Min';

				} else if (substr($path, 0, 18) === '/app/public/a/css/' && $file_ext == 'css') {

					// https://stackoverflow.com/a/1379487/6632

					$file_contents = file_get_contents(ROOT . $path);
					$file_contents = preg_replace('#/\*.*?\*/#s', '', $file_contents); // Remove comments
					$file_contents = preg_replace('/[ \t]*([{}|:;,])[ \t]+/', '$1', $file_contents); // Remove whitespace (keeping newlines)
					$file_contents = preg_replace('/^[ \t]+/m', '', $file_contents); // Remove whitespace at the start
					$file_contents = str_replace(';}', '}', $file_contents); // Remove unnecessary ;'s

					$file_dest = ROOT . str_replace('/css/', '/min/css/', $path);

					$result = 'Framework CSS Min';

				} else if (substr($path, 0, 18) === '/app/public/a/img/' && $webp_command && ($file_ext == 'jpg' || $file_ext == 'png')) {

					$webp_path = substr($path, 0, -3) . 'webp';

					$command = escapeshellcmd($webp_command) . ' ' . escapeshellarg(ROOT . $path) . ' -o ' . escapeshellarg(ROOT . $webp_path);

					$output = [];
					$result = NULL;

					exec($command, $output, $result);

					$result = 'Framework WebP [' . $result . ']';

				}

				if ($file_contents && $file_dest) {
					$folder = dirname($file_dest);
					if (!is_dir($folder)) {
						mkdir($folder, 0755, true); // Writable for user only
					}
					file_put_contents($file_dest, $file_contents);
				}

			}

			if ($result === false) {
				$result = '[SKIPPED]';
			}

			echo $file[0] . ' ' . $path . ' => ' . $result . "\n";

		}

	}

?>