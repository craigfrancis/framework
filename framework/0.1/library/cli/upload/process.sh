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
		exit;
	fi

#--------------------------------------------------
# SSH control connection
#--------------------------------------------------

	SSH_CONTROL='~/.ssh/master-%r@%h:%p';

	echo;
	echo "Connecting to ${DST_HOST}:";
	ssh -fN -M -S "${SSH_CONTROL}" "${DST_HOST}";
	echo "  Done";

	function remote_cmd {
		ssh -t -o 'LogLevel=QUIET' -S "${SSH_CONTROL}" "${DST_HOST}" $@;
	}

	function remote_scp {
		scp -o "ControlPath=${SSH_CONTROL}" -r "${1}" "${DST_HOST}:${2}";
	}

	function remote_rsync {
		echo 'TODO';
	}

	function remote_close {
		ssh -O exit -S "${SSH_CONTROL}" "${DST_HOST}" 2> /dev/null;
	}

#--------------------------------------------------
# Check path on destination
#--------------------------------------------------

	DST_EXISTS=`remote_cmd "if [ -d '${DST_PATH}' ]; then echo -n 'dir'; else echo -n 'not'; fi"`;

	if [[ "${DST_EXISTS}" != 'dir' ]]; then
		echo "Cannot find path '${DST_PATH}' on server '${DST_HOST}'";
		echo;
		remote_close;
		exit;
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

	echo "  Done";

#--------------------------------------------------
# Execute prep script
#--------------------------------------------------

	remote_cmd "${DST_PATH}/upload/publish-prep.sh '${UPLOAD_SERVER}' '${UPLOAD_METHOD}' '${DST_PATH}'";

#--------------------------------------------------
# Upload files
#--------------------------------------------------

	if [[ "${UPLOAD_METHOD}" == 'rsync' ]]; then

		echo 'TODO: Add rsync support';

	else

echo "${FRAMEWORK_ROOT}/../";

		remote_scp "${SRC_PATH}/app/"   "${DST_PATH}/upload/files/app/";
		remote_scp "${SRC_PATH}/httpd/" "${DST_PATH}/upload/files/httpd/";
		remote_scp "${FRAMEWORK_ROOT}/../"  "${DST_PATH}/upload/files/framework/";

	fi

#--------------------------------------------------
# Execute run script
#--------------------------------------------------

	remote_cmd "${DST_PATH}/upload/publish-run.sh '${UPLOAD_SERVER}' '${UPLOAD_METHOD}' '${DST_PATH}'";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	remote_close;




# ${UPLOAD_METHOD} or SRC_HOST? == 'scm' ... Connect to $config['dst_host'] to run install script? how about checking the db?



# Ensure the folder /dst_dir/upload/ exists.

# Check for a block file in /dst_dir/upload/block.txt

# Create lock file at dst /dst_dir/upload/lock.txt... maybe with a timestamp and uuid in it?

# Create empty folder, or cp of live site (rsync), at /dst_dir/upload/files/

# SCP or rsync /app/, /framework/, and /httpd/ folders.

# Run the 'mvtolive' script equivalent (from Fey)... provides Diff, Database, Continue, Cancel.

# Run the ./cli --install script, to create folders in /files/ and /private/files/.
