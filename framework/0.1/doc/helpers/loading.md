
# Loading helper

To see some how the loading helper can be used, look at the [example](/examples/loading/).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/loading.php).

Example setup

	$loading = new loading();

	$loading = new loading('profile');

	$loading = new loading(array(
			'profile'           => 'profile', // Use 'loading.profile.*' config.
			'time_out'          => (5 * 60),  // Seconds before script will timeout.
			'refresh_frequency' => 2,         // Seconds browser will wait before trying again.
			'refresh_url'       => '/../',    // If you want the user to load a different url while waiting (e.g. add a new parameter).
			'template_name'     => 'loading', // Customised loading page name (in /app/template/), see example below.
			'template_path'     => '/../',    // Customised loading page path.
			'lock_type'         => 'loading', // See lock example below.
			'lock_ref'          => NULL,
		));

---

## Basic setup

This uses the [session helper](../../doc/system/session.md) to store the loading state:

	// $loading->template_test();

	$loading->check(); // Will exit() with loading page if still running, return false if not running, or return the variables if there was a time-out.

	if ($form->submitted()) {
		if ($form->valid()) {

			// $loading->refresh_url_set('/../');
				// If you need to change the url (e.g. adding an id)

			$loading->start('Starting action');
				// A string will replace [MESSAGE] in the template,
				// or use an array for multiple tags.

			sleep(5);

			$loading->update('Updating progress');

			sleep(5);

			$loading->done('/../');
				// If you specify a URL, it will perform a redirect.

			exit();

		}
	}

---

## Lock file method

If you have a process that shouldn't allow multiple users to run it at the same time, we can use the [lock helper](../../doc/helpers/lock.md).

	$loading = new loading(array(
			'lock_type' => 'loading', // Set to use a lock, the name is passed directly to the lock helper.
			'lock_ref'  => NULL,      // Optional lock ref (e.g. pass in an ID if you want to lock a specific item).
		));

	$loading->check();

	if ($form->submitted()) {

		if ($loading->locked()) {
			$form->error_add('Already processing');
		}

		if ($form->valid()) {

			if ($loading->start('Starting action')) {

				sleep(5);

				$loading->update('Updating progress');

				sleep(5);

				$loading->done('/../');
					// If you specify a URL, it will perform a redirect.

				exit();

			} else {

				$form->error_add('Could not open lock');

			}

		}

	}

Optional template

	/app/public/a/css/global/loading.css

		This file will be used with a template like
		the one below, or you can create your own.

	/app/template/loading.ctp

		This file will be used if 'template_name' is
		set to 'loading', the contents should be
		something like the following.

		<!DOCTYPE html>
		<html lang="<?= html(config::get('output.lang')) ?>" xml:lang="<?= html(config::get('output.lang')) ?>" xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<meta charset="<?= html(config::get('output.charset')) ?>" />
			<title>Loading</title>
			<link rel="stylesheet" type="text/css" href="<?= html(version_path('/a/css/global/loading.css')) ?>" media="all" />
		</head>
		<body>
			<div id="page_content" role="main">
				<h1>Loading</h1>
				<p>[MESSAGE]... [[TIME_START]]</p>
			</div>
		</body>
		</html>
