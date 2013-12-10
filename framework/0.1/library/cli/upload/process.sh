#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	FRAMEWORK_ROOT="$1";
	UPLOAD_SERVER="$2";
	UPLOAD_METHOD="$3";
	SRC_HOST="$4";
	SRC_PATH="$5";
	DST_HOST="$6";
	DST_PATH="$7";

	if [[ -z "${FRAMEWORK_ROOT}" ]] || [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${SRC_HOST}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
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

	function remote_scp {
		scp -o "ControlPath=${SSH_CONTROL}" -r "${1}" "${DST_HOST}:${2}";
	}

	function remote_rsync {
		rsync -e "ssh -o 'ControlPath=${SSH_CONTROL}'" --delete -v -a -c "${1}" "${DST_HOST}:${2}";
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${DST_HOST}" 2> /dev/null;
	}

#--------------------------------------------------
# Check path on destination
#--------------------------------------------------

	DST_EXISTS=`remote_cmd "if [ -d '${DST_PATH}' ]; then echo -n 'dir'; else echo -n 'not'; fi"`;

	if [[ "${DST_EXISTS}" != 'dir' ]]; then

		echo;
		echo "This project is not on '${DST_HOST}:${DST_PATH}', press [y] to continue...";
		read KEY;

		if [ "$KEY" != "y" ]; then
			echo "Canceled";
			remote_close;
			exit 0;
		fi

	fi

#--------------------------------------------------
# Block support
#--------------------------------------------------

	BLOCK_EXISTS=`remote_cmd "if [ -f '${DST_PATH}/upload/block.txt' ]; then echo -n 'yes'; else echo -n 'no'; fi"`;

	if [[ "${BLOCK_EXISTS}" != 'no' ]]; then

		echo;
		echo "ERROR: An upload block has been created for this project.";
		echo;
		echo "--------------------------------";
		remote_cmd "cat '${DST_PATH}/upload/block.txt'";
		echo "--------------------------------";
		exit 0;

	fi

#--------------------------------------------------
# Upload publish scripts
#--------------------------------------------------

	echo;
	echo "Uploading prep scripts:";

	remote_cmd "mkdir -p '${DST_PATH}/upload/'";
	remote_scp "${FRAMEWORK_ROOT}/library/cli/upload/publish-prep.sh" "${DST_PATH}/upload/publish-prep.sh";
	remote_scp "${FRAMEWORK_ROOT}/library/cli/upload/publish-run.sh" "${DST_PATH}/upload/publish-run.sh";
	remote_cmd "chmod 755 ${DST_PATH}/upload/publish-{prep,run}.sh";

	echo " Done";

#--------------------------------------------------
# Execute prep script
#--------------------------------------------------

	remote_cmd "${DST_PATH}/upload/publish-prep.sh '${UPLOAD_SERVER}' '${UPLOAD_METHOD}' '${DST_PATH}'";

#--------------------------------------------------
# Upload files
#--------------------------------------------------

	echo;
	echo "Uploading files:";

	if [[ "${UPLOAD_METHOD}" == 'scp' ]]; then

		remote_scp "${SRC_PATH}/app/"             "${DST_PATH}/upload/files/app/";
		remote_scp "${SRC_PATH}/httpd/"           "${DST_PATH}/upload/files/httpd/";
		remote_scp "`dirname ${FRAMEWORK_ROOT}`/" "${DST_PATH}/upload/files/framework/";

	else

		remote_rsync "${SRC_PATH}/app/"             "${DST_PATH}/upload/files/app/";
		remote_rsync "${SRC_PATH}/httpd/"           "${DST_PATH}/upload/files/httpd/";
		remote_rsync "`dirname ${FRAMEWORK_ROOT}`/" "${DST_PATH}/upload/files/framework/";

	fi

	echo " Done";

#--------------------------------------------------
# Execute run script
#--------------------------------------------------

	remote_cmd "${DST_PATH}/upload/publish-run.sh '${UPLOAD_SERVER}' '${UPLOAD_METHOD}' '${DST_PATH}'";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;
