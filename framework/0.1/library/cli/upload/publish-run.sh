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


echo "Run...";
echo "# ${UPLOAD_SERVER}";
echo "# ${UPLOAD_METHOD}";
echo "# ${DST_PATH}";
echo;

# Run the 'mvtolive' script equivalent (from Fey)... provides Diff, Database, Continue, Cancel.
# Run the ./cli --install script, to create folders in /files/ and /private/files/.
