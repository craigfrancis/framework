
# CLI Injection

If you do ever need to use the command line (aka shell) to run another program, typically though:

- [system](https://php.net/system)()
- [shell_exec](https://php.net/shell_exec)()
- [exec](https://php.net/exec)()
- [popen](https://php.net/popen)()
- [passthru](https://php.net/passthru)()
- [backtick operators](https://php.net/operators.execution) - avoid

The command you want to execute should use [escapeshellcmd](https://php.net/escapeshellcmd), and any arguments should use [escapeshellarg](https://php.net/escapeshellarg).

For example:

	system('ls ' . escapeshellarg($dir));
