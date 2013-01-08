
# Loading helper

To see some how the loading helper can be used, look at the [examples](/examples/loading/).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/loading.php).

	//--------------------------------------------------
	// Example setup

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

	//--------------------------------------------------
	// Example usage

		// $loading->template_test();

		$loading->check(); // Will exit() with loading page if still running, return false if not running, or return the variables if there was a time-out.

		if ($form->submitted()) {
			if ($form->valid()) {

				// $loading->refresh_url_set('/../');

				$loading->start('Starting action'); // String will replace [MESSAGE] in the template, or array for multiple tags.

				sleep(5);

				$loading->update('Updating progress');

				sleep(5);

				// $loading->done();
				$loading->done('/../'); // Specify a URL if you want to redirect to a different url.
				exit();

			}
		}

	//--------------------------------------------------
	// Example with 'lock'

		$loading = new loading(array(
				'lock_type' => 'loading', // Set to use a lock, the name is passed directly to the lock helper.
				'lock_ref'  => NULL,      // Optional lock ref (e.g. pass in an ID if you want to lock a specific item).
			));

		$loading->check();

		if ($loading->start('Starting action')) {

			sleep(5);

			$loading->update('Updating progress');

			sleep(5);

			$loading->done();
			$loading->done('/../'); // Optional URL if you want to redirect users to a new page (e.g. a static thank you page)

		} else {

			// Could not open lock

		}

	//--------------------------------------------------
	// Optional template 'loading'

		/app/public/a/css/global/loading.css

			This file will be used with a template like
			the one below, or you can create your own.

		/app/template/example.ctp

			This file will be used if 'template_name' is
			set to 'example', the contents should be
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
