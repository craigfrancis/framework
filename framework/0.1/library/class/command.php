<?php

// TODO: Not written yet.
//
// Allow a command to be specified, with stdin/stdout/stderr, and return value
//
// Consider a parameterised option, with auto escaped arguments (to work with taint extension):
//
//   command::exec('/path/to/cmd --argument ?')
//
//   $command = new command();
//   $command->exec('/path/to/cmd', ['a', 'b', 'c']);
//   echo $command->stdout_get();
//
// Or, maybe an array of arguments, like pcntl_exec():
//
//   command::exec('/path/to/cmd'. ['--argument "unsafe"']);
//
// Review:
//   http://symfony.com/doc/current/components/process.html
//   https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Process/Process.php
//
// Intention to remove:
//   command_run()
//   passthru()
//   shell_exec()
//   exec()
//   system()
//   popen()
//   proc_open() ... proc_get_status(), proc_nice(), proc_terminate()

?>