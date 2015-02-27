
# Jobs

All jobs should be saved in:

	/app/job/01.xyz.php

Where the (optional) number at the beginning allows the order of the jobs to be defined.

The script itself should really contain a class, such as:

	class xyz_job extends job {

		public function should_run() {
			return ($this->last_run === NULL || $this->last_run < strtotime(date('Y-m-d 00:00:00'))); // Once a day
		}

		public function email_addresses_get() {
			return array(
					'stage' => array(
							'admin@example.com',
						),
					'demo' => array(
							'admin@example.com',
						),
					'live' => array(
							'admin@example.com',
						),
				);
		}

		public function run() {

		}

	}

Normally all of the 'jobs' can be run via the cli:

	./cli --maintenance

Or via the framework provided gateway (if enabled):

	https://www.example.com/a/api/maintenance/

It is possible to run the maintenance scripts via your own code (not really needed):

	$maintenance = new maintenance();
	$maintenance->run();

But to run a single job manually (skipping the 'should_run' check), use:

	$maintenance = new maintenance();
	echo $maintenance->execute('xyz');

Notice that it will also return echo'ed output, this is because a job should not really print anything (e.g. a cron job)... so it returns it as a variable for you to use.

---

$config['gateway.maintenance'] = true;