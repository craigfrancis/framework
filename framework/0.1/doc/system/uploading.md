# Uploading

You should setup the uploading process, as it is quick and considerably less error prone.

---

## Configuration

Your [configuration file](../../doc/setup/config.md) will setup each location (e.g. Demo and Live servers).

	$config['upload.demo.source'] = 'git'; // or 'svn'
	$config['upload.demo.location'] = 'demo:/www/demo/company.project';
	$config['upload.demo.update'] = false; // or true, or array('project', 'framework')

	$config['upload.live.source'] = 'demo';
	$config['upload.live.location'] = 'live:/www/live/company.project';

Where **source** says where it comes from, on Demo this is usually 'git' or 'svn', otherwise it is the name of another location.

The **location** is the server name and path (separated with a colon), to simply say where it should go.

For example, you could get the Demo server to pull from the Stage server:

	$config['upload.stage.location'] = 'stage:/www/stage/company.project';

	$config['upload.demo.source'] = 'stage';
	$config['upload.demo.location'] = 'demo:/www/demo/company.project';

---

## SCM Updates

If the source uses 'git' or 'svn', then you should checkout the project and framework at the specified location.

The framework will then look at the **update** configuration:

- **true** will run the respecitve 'git pull' or 'svn update'.

- **false** will not do anything (it assumes there is another process in place to update).

- Or specify an array to only update the 'project' or 'framework' working directories.

---

## Running

Simply run the following:

	./cli --upload=demo
	./cli --upload=live

You could even create a "tolive" function in your ~/.bash_login:

	function tolive () {

		if [ -z "${1:-}" ]; then
			NAME=$(pwd | awk '{ sub("^/Volumes/WebServer/Projects/", ""); sub("/.*$", ""); print }');
		else
			NAME=$(echo "$1" | awk '{ sub("^[\.\/]*", ""); sub("[\.\/]*$", ""); print }');
		fi

		if [ -z "${NAME}" ]; then

			echo "Project Not Specified";

		else

			ROOT="/Volumes/WebServer/Projects/${NAME}/";

			if [ ! -d "$ROOT" ]; then

				echo "Project ${NAME} has not been checked out";

			elif [ -L "$ROOT/cli" ]; then

				$ROOT/cli --upload=demo
				$ROOT/cli --upload=live

			else

				echo "Project ${NAME} does is not setup to move to live";

			fi

		fi

	}

---

## Install scripts

After the files have been uploaded, you can create an install script to do any final setup:

	/app/library/setup/install.php

	/app/library/setup/install.sh

These rarely need to do anything though.

For example, the path for /a/files/ is usually setup though the Apache config... but if not, you could create a symlink:

	#!/bin/bash

	if [ -z $1 ]; then
		SERVER="stage";
	else
		SERVER=$1;
	fi

	if [ "${SERVER}" == "live" ]; then
		cd "./app/public/a";
		ln -s "../../../files/" "./files";
	fi
