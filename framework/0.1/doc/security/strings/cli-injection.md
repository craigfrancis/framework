
# CLI Injection

If you do ever need to use the command line (aka shell) to run another program, typically though:

- [system](https://php.net/system)()
- [shell_exec](https://php.net/shell_exec)()
- [exec](https://php.net/exec)()
- [popen](https://php.net/popen)()
- [passthru](https://php.net/passthru)()
- [backtick operators](https://php.net/operators.execution) - avoid

The command you want to execute should use the "command" helper:

	$command = new command();

	// $command->stdin_set('Stdin Text');

	$exit_code = $command->exec('/path/to/command.sh --arg=?', [
			'arg1',
			'extra1',
			'extra2',
			'extra3',
		]);

	// $command->stderr_get());
	// $command->stdout_get());

This ensures the command is a developer defined string (aka a "literal-string"), and that all of the arguments are escaped.
