#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	UPLOAD_SERVER="$1";
	UPLOAD_METHOD="$2";
	DST_PATH="$3";

	if [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit 0;
	fi

#--------------------------------------------------
# Ensure all folders exist on live
#--------------------------------------------------

	echo;

	if [[ ! -d "${DST_PATH}" ]]; then
		echo "Missing root path: ${DST_PATH}";
		exit 0;
	fi

	echo "Check main folders:";

	cd "${DST_PATH}";

	if [[ -d "${DST_PATH}/upload/files/" ]]; then
		rm -rf "${DST_PATH}/upload/files/";
	fi

	mkdir -p "${DST_PATH}/app/";
	mkdir -p "${DST_PATH}/backup/";
	mkdir -p "${DST_PATH}/files/";
	mkdir -p "${DST_PATH}/framework/";
	mkdir -p "${DST_PATH}/httpd/";
	mkdir -p "${DST_PATH}/logs/";
	mkdir -p "${DST_PATH}/private/";
	mkdir -p "${DST_PATH}/upload/files/";

	echo " Done";

#--------------------------------------------------
# Copy current files for rsync support
#--------------------------------------------------

	if [[ "${UPLOAD_METHOD}" != 'scp' ]]; then

		cp -a "${DST_PATH}/app/"       "${DST_PATH}/upload/files/app/";
		cp -a "${DST_PATH}/httpd/"     "${DST_PATH}/upload/files/httpd/";
		cp -a "${DST_PATH}/framework/" "${DST_PATH}/upload/files/framework/";

	fi
