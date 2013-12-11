#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	FRAMEWORK_ROOT="${1}";
	SRC_PATH="${2}";
	DST_PATH="${3}";

	if [[ -z "${FRAMEWORK_ROOT}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

#--------------------------------------------------
# Check path on destination
#--------------------------------------------------

	if [[ ! -d "${DST_PATH}" ]]; then

		echo;
		echo "This project is not on '${DST_PATH}', press [y] to continue...";
		read KEY;

		if [[ "${KEY}" != 'y' ]]; then
			echo 'Canceled';
			exit 0;
		fi

	fi

#--------------------------------------------------
# Block support
#--------------------------------------------------

	if [[ -f "${DST_PATH}/upload/block.txt" ]]; then

		echo;
		echo 'ERROR: An upload block has been created for this project.';
		echo;
		echo '--------------------------------';
		cat "${DST_PATH}/upload/block.txt";
		echo '--------------------------------';
		exit 0;

	fi

#--------------------------------------------------
# Lock support
#--------------------------------------------------

	# Lock file... maybe in ${DST_PATH}/upload/lock.txt ... with a timestamp and uuid in it?

#--------------------------------------------------
# Execute prep script
#--------------------------------------------------

	${FRAMEWORK_ROOT}/library/cli/upload/publish-prep.sh 'local' "${DST_PATH}";

#--------------------------------------------------
# Upload files
#--------------------------------------------------

	echo;
	echo 'Uploading files:';

	cp -a "${SRC_PATH}/app/"             "${DST_PATH}/upload/files/app/";
	cp -a "${SRC_PATH}/httpd/"           "${DST_PATH}/upload/files/httpd/";
	cp -a "`dirname ${FRAMEWORK_ROOT}`/" "${DST_PATH}/upload/files/framework/";

	echo ' Done';

#--------------------------------------------------
# Execute run script
#--------------------------------------------------

	${FRAMEWORK_ROOT}/library/cli/upload/publish-run.sh 'local' "${DST_PATH}";
