#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	UPLOAD_SERVER="$1";
	UPLOAD_METHOD="$2";
	SRC_HOST="$3";
	SRC_PATH="$4";
	DST_HOST="$5";
	DST_PATH="$6";

	if [[ -z "${UPLOAD_SERVER}" ]] || [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${SRC_HOST}" ]] || [[ -z "${SRC_PATH}" ]] || [[ -z "${DST_HOST}" ]] || [[ -z "${DST_PATH}" ]]; then
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
		ssh -o 'LogLevel=QUIET' -t -S "${SSH_CONTROL}" "${DST_HOST}" $@;
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
# Upload and run publish script
#--------------------------------------------------

pwd;

	remote_cmd "mkdir -p '${DST_PATH}/upload/'";
	remote_cmd "scp ./publish.sh '${DST_PATH}/upload/publish.sh'";
	remote_cmd "${DST_PATH}/upload/publish.sh";

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
