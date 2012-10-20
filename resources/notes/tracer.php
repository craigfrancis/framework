<?php

	$GLOBALS['tracer_last_file'] = NULL;
	$GLOBALS['tracer_last_call'] = NULL;
	$GLOBALS['tracer_log_file'] = fopen('/tmp/php-prime-trace', 'w');

	function tracer() {

		$functions = array();

		$line = NULL;
		$file = NULL;
		$last = NULL;

		foreach (debug_backtrace() as $call) {
			if ($call['function'] == 'tracer') {
			} else if ($call['function'] == 'require_once') {

				if ($line == NULL) {
					$line = $last['line'];
					$file = $last['file'];
				}

			} else {

				$args = array();
				foreach ($call['args'] as $arg) {
					if (is_string($arg)) {
						$arg = trim($arg);
						$args[] = '"' . str_replace("\n", '\n', substr($arg, 0, 20)) . (strlen($arg) > 20 ? '...' : '') . '"';
					} else if (is_numeric($arg)) {
						$args[] = $arg;
					} else if (is_bool($arg)) {
						$args[] = ($arg ? 'true' : 'false');
					} else if (is_array($arg)) {
						$args[] = 'array(...)';
					} else {
						$args[] = '???';
					}
				}
				$functions[] = (isset($call['class']) ? $call['class'] . '::' : '') . $call['function'] . '(' . implode(', ', $args) . ')';
			}
			$last = $call;
		}

		if (count($functions) == 0) {
			return;
		}

		if ($line == NULL) {
			$line = $call['line'];
			$file = $call['file'];
		}

		if ($GLOBALS['tracer_last_file'] === NULL || $GLOBALS['tracer_last_file'] !== $file) {
			fwrite($GLOBALS['tracer_log_file'], "\n" . $file . "\n");
			$GLOBALS['tracer_last_file'] = $file;
		}

		$call = '  '. str_pad($line, 3, '0', STR_PAD_LEFT) . ') ' . implode('->', array_reverse($functions)) . ';';
		if ($GLOBALS['tracer_last_call'] === NULL || $GLOBALS['tracer_last_call'] !== $call) {
			fwrite($GLOBALS['tracer_log_file'], $call . "\n");
			$GLOBALS['tracer_last_call'] = $call;
		}

	}

	declare(ticks = 1);
	register_tick_function('tracer');

?>