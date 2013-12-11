#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	FRAMEWORK_ROOT="$1";
	SRC_PATH="$2";
	DST_PATH="$3";

	if [[ -z "${FRAMEWORK_ROOT}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo "Missing parameters";
		echo;
		exit 0;
	fi

#--------------------------------------------------
# Check path on destination
#--------------------------------------------------

	echo "TODO: Local upload";

