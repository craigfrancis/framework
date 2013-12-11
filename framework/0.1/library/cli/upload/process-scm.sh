#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	FRAMEWORK_ROOT="$1";
	DST_HOST="$2";
	DST_PATH="$3";

	if [[ -z "${FRAMEWORK_ROOT}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit 0;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo;
	echo "Connecting to ${DST_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${DST_HOST}";
	echo " Done";

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
	CLI_EXISTS=`remote_cmd "if [ -h '${CLI_PATH}' ]; then echo -n 'link'; else echo -n 'not'; fi"`;

	if [[ "${CLI_EXISTS}" != 'link' ]]; then
		echo "Cannot find CLI script on server '${DST_HOST}', path '${CLI_PATH}' - '${CLI_EXISTS}'";
		echo;
		remote_close;
		exit 0;
	fi

#--------------------------------------------------
# Run
#--------------------------------------------------

	remote_cmd "${CLI_PATH} --install";
	remote_cmd "${CLI_PATH} --diff";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;

	echo;
