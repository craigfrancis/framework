#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	SERVER="${1}";
	ROOT="${2}";
	DST_SOURCE="${3}";
	DST_HOST="${4}";
	DST_PATH="${5}";
	UPDATE="${6}";

	if [[ -z "${ROOT}" ]] || [[ -z "${DST_SOURCE}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

	umask 0002;

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo "Connecting [${SERVER}] to ${DST_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${DST_HOST}";
	echo -e "  \033[1;34mDone\033[0m";

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
			echo "  git clone XXX '${DST_PATH}';";
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

		# - The "-C" option isn't available in
		#   git 1.7, as used in Debian stable.
		# - For some reason the spaces are collapsed
		#   in awk when running remotely.
		# - Cannot run awk locally, as the pull may
		#   require an ssh passphrase.

	if [[ "${UPDATE}" =~ "project" ]]; then

		echo;
		echo "Update project:";

		if [[ "${DST_SOURCE}" == 'git' ]]; then
			remote_cmd "umask 0002 && cd ${DST_PATH} && git pull | awk '/^Already up-to-date/ {print \" \" \" \033[1;34m\" \$0 \"\033[0m\"; next} { print \" \" \" \" \$0; }' && git submodule update --init";
		else
			remote_cmd "umask 0002 && svn update ${DST_PATH} | awk '{ print \" \" \" \" \$0;}'";
		fi

	fi

	if [[ "${UPDATE}" =~ "framework" ]]; then

		echo;
		echo "Update framework:";

		remote_cmd "umask 0002 && cd \`${CLI_PATH} --config=FRAMEWORK_ROOT\` && git pull | awk '/^Already up-to-date/ {print \" \" \" \033[1;34m\" \$0 \"\033[0m\"; next} { print \" \" \" \" \$0; }'";

	fi

#--------------------------------------------------
# Run install
#--------------------------------------------------

	echo;
	echo 'Run install script...';

	remote_cmd "${CLI_PATH} --install" | awk '{ print "    " $0;}';

	echo -e "  \033[1;34mDone\033[0m";

	# USER_OWNER=`remote_cmd "ls -ld '${DST_PATH}' | awk '{print \\\$3}'"`;
	# USER_NAME=`remote_cmd "whoami"`;
	#
	# Can't really run 'install', as the scm post-commit hook will be under
	# a different user (probably apache), so won't be able to create or
	# change permissions on any files.

#--------------------------------------------------
# Check database
#--------------------------------------------------

	echo;
	echo 'Check database...';

	remote_cmd "${CLI_PATH} --diff" | awk '{ print "    " $0;}';

	echo -e "  \033[1;34mDone\033[0m";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;

	echo;
