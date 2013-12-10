#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	SRC_SERVER="$1";
	SRC_PATH="$2";
	DST_SERVER="$3";
	DST_PATH="$4";

	if [[ -z "${SRC_SERVER}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_SERVER}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL="~/.ssh/master-%r@%h:%p";

	# ssh -fN -M  -S "${SSH_CONTROL}" "${SRC_SERVER}";

	function remote_cmd {
		ssh -S "${SSH_CONTROL}" "${SRC_SERVER}" $@;
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${SRC_SERVER}" 2> /dev/null;
	}

#--------------------------------------------------
# Check source
#--------------------------------------------------

	CLI_PATH="${SRC_PATH}/cli";

	CLI_EXISTS=`remote_cmd "if [ -h '${CLI_PATH}' ]; then echo 'link'; else echo 'not'; fi"`;

	echo "${CLI_EXISTS}";

	if [[ "${CLI_EXISTS}" != "link" ]]; then
		echo "Cannot find CLI script on server '${SRC_SERVER}', path '${CLI_PATH}'";
		echo;
		remote_close;
		exit;
	fi

#--------------------------------------------------
# Upload
#--------------------------------------------------

	echo "Yay";

	remote_close;
