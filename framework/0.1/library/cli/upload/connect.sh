#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	UPLOAD_SERVER="$1";
	UPLOAD_METHOD="$2";
	SRC_HOST="$3";
	SRC_PATH="$4";
	DST_HOST="$5";
	DST_PATH="$6";

	if [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${SRC_HOST}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo;
	echo "Connecting to ${SRC_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${SRC_HOST}";
	echo "  Done";
	echo;

	function remote_cmd {
		ssh -o 'LogLevel=QUIET' -t -S "${SSH_CONTROL}" "${SRC_HOST}" $@;
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${SRC_HOST}" 2> /dev/null;
	}

#--------------------------------------------------
# Check cli on source
#--------------------------------------------------

	CLI_PATH="${SRC_PATH}/cli";
	CLI_EXISTS=`remote_cmd "if [ -h '${CLI_PATH}' ]; then echo -n 'link'; else echo -n 'not'; fi"`;

	if [[ "${CLI_EXISTS}" != 'link' ]]; then
		echo "Cannot find CLI script on server '${SRC_HOST}', path '${CLI_PATH}' - '${CLI_EXISTS}'";
		echo;
		remote_close;
		exit;
	fi

#--------------------------------------------------
# Run
#--------------------------------------------------

	remote_cmd "${CLI_PATH} --upload='${UPLOAD_SERVER}'";
	remote_close;
