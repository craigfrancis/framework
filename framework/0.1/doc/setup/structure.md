# Folder structure

For a quick overview, the folder structure is basically:

	/app/
		/controller/
		/gateway/
		/job/
		/library/
			/class/
			/controller/
			/query/
			/setup/
		/public/
		/template/
		/unit/
		/view/
	/files/
	/framework/
	/httpd/
	/logs/
	/private/
		/files/
		/tmp/
	/resources/

---

## App

The /app/ folder basically contains everything for your website to function. This is where you will put pretty much every file related to your website.

### Controller

As the name suggests, this is where your [controllers](../../doc/setup/controllers.md) go.

### Gateway

Any [gateway scripts](../../doc/setup/gateways.md) go here.

### Job

Any [job scripts](../../doc/setup/jobs.md) go here, otherwise known as maintenance or cron scripts.

### Library

The sub-folders contain:

- **class**: generic classes that can be included as required.
- **controller**: re-usable [controllers](../../doc/setup/controllers.md), which normal controllers can extend.
- **query**: classes for the [query helper](../../doc/helpers/query.md).
- **setup**: setup files (e.g. [configuration](../../doc/setup/config.md), and [cli dump](../../doc/setup/cli.md) files).

### Public

For the web root, so any files are exposed to the internet (e.g. images, CSS, JavaScript).

### Template

Any [template files](../../doc/setup/templates.md) go here.

### Unit

Any [unit files](../../doc/setup/units.md) go here.

### View

Any [view files](../../doc/setup/views.md) go here.

---

## Files

When files are uploaded to the website, perhaps images in a CMS, these are typically linked to the database, and are not part of the usual process of uploading files from demo to live.

It's typically exposed by the web server via an alias, such as /a/files/.

If you need to store files which should not be exposed to the internet (maybe a script needs to first check the user has permission first), put them in /private/files/.

---

## Framework

Where the framework files typically go.

---

## HTTPD

Not a required folder, but where I keep the web server (Apache) configuration files.

This keeps the config under version control, and ensures you have a backup.

---

## Logs

Not a required folder, and typically empty in development... but this is where I keep the web server access/error logs on Live.

---

## Private

Like the /files/ folder, it contains files that are created by the website and are not part of the usual process of uploading files from demo to live.

However these files are not exposed to the internet.

This is also where the /private/tmp/ folder exists.

And if you are using something like [GPG](../../doc/helpers/gpg.md), you will probably want to store the server specific keys in here.

---

## Resources

Stores any random files related to the website, for example PSDs, Site Maps, and the often missing Content Documents.

Typically this folder only exists in version control and on your development machines, and is ignored when uploading to Live.
