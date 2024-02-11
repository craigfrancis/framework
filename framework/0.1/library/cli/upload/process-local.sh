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

		echo "This project is not on '${DST_PATH}', press [y] to continue...";
		read KEY;

		if [[ "${KEY}" != 'y' ]]; then
			echo '  Canceled';
			echo;
			exit 0;
		fi

		echo;

	fi

#--------------------------------------------------
# Block support
#--------------------------------------------------

	if [[ -f "${DST_PATH}/upload/block.txt" ]]; then

		echo 'ERROR: An upload block has been created for this project.';
		echo;
		echo '--------------------------------';
		echo "${DST_PATH}/upload/block.txt";
		cat "${DST_PATH}/upload/block.txt";
		echo '--------------------------------';
		exit 0;

	fi

#--------------------------------------------------
# Lock support
#--------------------------------------------------

	# Lock file... maybe in ${DST_PATH}/upload/lock.txt ... with a timestamp and uuid in it?

#--------------------------------------------------
# Create upload folder
#--------------------------------------------------

	mkdir -m 0775 -p "${DST_PATH}/upload/";

#--------------------------------------------------
# Execute prep script
#--------------------------------------------------

	chmod -f 755 ${FRAMEWORK_ROOT}/library/cli/upload/publish-{prep,run}.sh

	${FRAMEWORK_ROOT}/library/cli/upload/publish-prep.sh 'local' "${DST_PATH}";

#--------------------------------------------------
# Upload files
#--------------------------------------------------

	echo 'Uploading files:';

	rsync --delete -a --exclude=.svn "${SRC_PATH}/app/"             "${DST_PATH}/upload/files/app/";
	rsync --delete -a --exclude=.svn "${SRC_PATH}/httpd/"           "${DST_PATH}/upload/files/httpd/";
	rsync --delete -a --exclude=.svn "`dirname ${FRAMEWORK_ROOT}`/" "${DST_PATH}/upload/files/framework/";

	echo -e "  \033[1;34mDone\033[0m";

#--------------------------------------------------
# Execute run script
#--------------------------------------------------

	echo;

	${FRAMEWORK_ROOT}/library/cli/upload/publish-run.sh 'local' "${DST_PATH}";
