#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	SERVER="${1}";
	SRC_SERVER="${2}";
	SRC_HOST="${3}";
	SRC_PATH="${4}";
	DST_SERVER="${5}";

	if [[ -z "${SERVER}" ]] || [[ -z "${SRC_SERVER}" ]] || [[ -z "${SRC_HOST}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_SERVER}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo "Connecting [${SERVER}] to ${SRC_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${SRC_HOST}";
	echo -e "  \033[1;34mDone\033[0m";

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

	echo;

	remote_cmd "${CLI_PATH} --confirm-server='${SRC_SERVER}' --upload='${DST_SERVER}'";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;
