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

echo "Prep...";
echo "# ${UPLOAD_SERVER}";
echo "# ${UPLOAD_METHOD}";
echo "# ${DST_PATH}";
echo;

#--------------------------------------------------
# Ensure all folders exist on live
#--------------------------------------------------

	cd "${DST_PATH}";

	if [ -d "${DST_PATH}/upload/files/" ]; then
echo "Remove failed files upload";
		rm -rf "${DST_PATH}/upload/files/";
	fi

	mkdir -p "${DST_PATH}/{app,backup,files,httpd,logs,private,FISH}/";
	mkdir -p "${DST_PATH}/upload/files/";

#--------------------------------------------------
# Copy current files for rsync support
#--------------------------------------------------

	if [[ "${UPLOAD_METHOD}" == 'rsync' ]]; then

# keep modified time

		echo 'TODO: Add rsync support';

	else
