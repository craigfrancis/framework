#!/bin/bash

	echo "Process";
	echo;

# ${UPLOAD_MODE} == 'scm' ... Connect to $config['dst_host'] to run install script? how about checking the db?



# Ensure the folder /dst_dir/upload/ exists.

# Check for a block file in /dst_dir/upload/block.txt

# Create lock file at dst /dst_dir/upload/lock.txt... maybe with a timestamp and uuid in it?

# Create empty folder, or cp of live site (rsync), at /dst_dir/upload/files/

# SCP or rsync /app/, /framework/, and /httpd/ folders.

# Run the 'mvtolive' script equivalent (from Fey)... provides Diff, Database, Continue, Cancel.

# Run the ./cli --install script, to create folders in /files/ and /private/files/.
