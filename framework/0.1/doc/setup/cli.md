
# CLI

Or otherwise known as the 'Command Line Interface'.

Available in the project route is a single symlink:

	./cli

It allows you to return [configuration](../../doc/setup/config.md) options:

	./cli --config
	./cli --config=output.site_name

Run a [gateway](../../doc/setup/gateways.md) script:

	./cli --gateway
	./cli --gateway=name

Run the [maintenance](../../doc/setup/jobs.md) scripts (e.g. via a cron job):

	./cli --maintenance

Run the install process:

	./cli --install

Create new things, such as [units](../../doc/setup/units.md):

	./cli --new

Correct permission problems:

	./cli --permissions

Look for general issues, such as varying db collations/engines.

	./cli --check

Update or compare against the 2 configuration files that list the database structure, and folders in the [files directories](../../doc/setup/structure.md).

	./cli --dump
	./cli --diff

[Upload](../../doc/system/uploading.md) the project to a particular server:

	./cli --upload=demo
	./cli --upload=live

