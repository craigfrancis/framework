#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	UPLOAD_SERVER="${1}";
	SRC_SERVER="${2}";
	SRC_HOST="${3}";
	SRC_PATH="${4}";

	if [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${SRC_HOST}" ]] || [[ -z "${SRC_PATH}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo;
	echo "Connecting ${SRC_SERVER} to ${SRC_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${SRC_HOST}";
	echo '  Done';

	function remote_cmd {
		ssh -t -o 'LogLevel=QUIET' -S "${SSH_CONTROL}" "${SRC_HOST}" $@;
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${SRC_HOST}" 2> /dev/null;
	}

#--------------------------------------------------
# Check cli on source
#--------------------------------------------------

	CLI_PATH="${SRC_PATH}/cli";
	CLI_EXISTS=`remote_cmd "if [[ -h '${CLI_PATH}' ]]; then echo -n 'link'; else echo -n 'not'; fi"`;

	if [[ "${CLI_EXISTS}" != 'link' ]]; then
		echo "Cannot find CLI script on server '${SRC_HOST}', path '${CLI_PATH}' - '${CLI_EXISTS}'";
		echo;
		remote_close;
		exit 0;
	fi

#--------------------------------------------------
# Run
#--------------------------------------------------

	remote_cmd "${CLI_PATH} --upload='${UPLOAD_SERVER}'";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;

	echo;
