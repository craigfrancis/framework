#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	UPLOAD_SERVER="$1";
	UPLOAD_METHOD="$2";
	DST_PATH="$3";

	if [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit;
	fi

#--------------------------------------------------
# Ensure all folders exist on live
#--------------------------------------------------

	echo;

	if [[ ! -d "${DST_PATH}" ]]; then
		echo "Missing root path: ${DST_PATH}";
		exit;
	fi

	echo "Check main folders:";

	cd "${DST_PATH}";

	if [[ -d "${DST_PATH}/upload/files/" ]]; then
		rm -rf "${DST_PATH}/upload/files/";
	fi

	mkdir -p "${DST_PATH}/app/";
	mkdir -p "${DST_PATH}/backup/";
	mkdir -p "${DST_PATH}/files/";
	mkdir -p "${DST_PATH}/httpd/";
	mkdir -p "${DST_PATH}/logs/";
	mkdir -p "${DST_PATH}/private/";
	mkdir -p "${DST_PATH}/upload/files/";

	echo "  Done";
	echo;

#--------------------------------------------------
# Copy current files for rsync support
#--------------------------------------------------

	if [[ "${UPLOAD_METHOD}" == 'rsync' ]]; then

		# keep modified time

		echo 'TODO: Add rsync support';

	fi
