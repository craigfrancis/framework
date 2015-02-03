#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	ROOT="${1}";
	DST_SOURCE="${2}";
	DST_HOST="${3}";
	DST_PATH="${4}";
	UPDATE="${5}";

	if [[ -z "${ROOT}" ]] || [[ -z "${DST_SOURCE}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

#--------------------------------------------------
# Git push
#--------------------------------------------------

	if [[ "${DST_SOURCE}" == 'git' ]]; then

		cd "${ROOT}";
		git push origin master;

	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo;
	echo "Connecting to ${DST_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${DST_HOST}";
	echo '  Done';

	function remote_cmd {
		ssh -t -o 'LogLevel=QUIET' -S "${SSH_CONTROL}" "${DST_HOST}" $@;
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${DST_HOST}" 2> /dev/null;
	}

#--------------------------------------------------
# Check cli on destination
#--------------------------------------------------

	CLI_PATH="${DST_PATH}/cli";
	CLI_EXISTS=`remote_cmd "if [[ -h '${CLI_PATH}' ]]; then echo -n 'link'; else echo -n 'not'; fi"`;

	if [[ "${CLI_EXISTS}" != 'link' ]]; then

		echo;
		echo "Cannot find CLI script on:";
		echo;
		echo "  Server: ${DST_HOST}";
		echo "  Path: ${CLI_PATH}";
		echo;
		echo 'Please run:';
		echo;
		echo "  ssh ${DST_HOST};";
		if [[ "${DST_SOURCE}" == 'git' ]]; then
			echo "  git checkout XXX '${DST_PATH}';";
		else
			echo "  svn checkout XXX '${DST_PATH}';";
		fi
		echo;
		echo 'Possibly checkout the framework project, followed by:';
		echo;
		echo "  cd ${DST_PATH}";
		echo "  ln -s ../path/to/framework/0.1/cli/run.sh cli";
		echo "  ./cli --install";
		echo "  ./cli --permissions";
		echo;

		remote_close;
		exit 0;

	fi

#--------------------------------------------------
# Run update
#--------------------------------------------------

	if [[ "${UPDATE}" =~ "project" ]]; then

		echo;
		echo "Update project:";

		if [[ "${DST_SOURCE}" == 'git' ]]; then
			remote_cmd "cd ${DST_PATH} && git pull" | awk '{ print "  " $0;}';
		else
			remote_cmd "cd ${DST_PATH} && svn update" | awk '{ print "  " $0;}';
		fi

	fi

	if [[ "${UPDATE}" =~ "framework" ]]; then

		echo;
		echo "Update framework:";

		remote_cmd "cd \`${CLI_PATH} --config=FRAMEWORK_ROOT\` && git pull" | awk '{ print "  " $0;}';

	fi

#--------------------------------------------------
# Run install
#--------------------------------------------------

	# USER_OWNER=`remote_cmd "ls -ld '${DST_PATH}' | awk '{print \\\$3}'"`;
	# USER_NAME=`remote_cmd "whoami"`;

	# remote_cmd "${CLI_PATH} --install";

	# Can't really run 'install', as the scm post-commit hook will be under
	# a different user (probably apache), so won't be able to create or
	# change permissions on any files.

#--------------------------------------------------
# Check database
#--------------------------------------------------

	remote_cmd "${CLI_PATH} --diff";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;

	echo;
