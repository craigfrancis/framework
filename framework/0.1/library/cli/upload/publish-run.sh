#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	UPLOAD_METHOD="${1}";
	DST_PATH="${2}";

	if [[ -z "${UPLOAD_METHOD}" ]] || [[ -z "${DST_PATH}" ]]; then
		echo 'Missing parameters';
		echo;
		exit 0;
	fi

	CURRENT=`date '+%Y_%m_%d'`;
	FOLDERS="app httpd framework";

#--------------------------------------------------
# Check Secrets
#--------------------------------------------------

	# TODO [secrets]

	# Check all of the secrets exist... remember we don't know which secrets file to check (ENV stores the key), so would probably involve the API? don't think a 'current' symlink would work during a key rotation.

	# Would it be worth doing this via PHP, as multiple calls to ./cli could be slower... e.g. maybe have a `./cli --publish`?

#--------------------------------------------------
# Checking
#--------------------------------------------------

	cd "${DST_PATH}/upload/files/";
	rm -f "./cli";
	ln -s "./framework/0.1/cli/run.sh" "./cli";
	chmod 755 "./cli";

	CONTINUE="false";

	while [ "${CONTINUE}" = "false" ];
	do

		select RUN in 'Diff' 'Database' 'Continue' 'Cancel'; do

			if [[ "${RUN}" == 'Diff' ]]; then

				echo;
				echo '--------------------------------------------------';

				for F in ${FOLDERS}; do
					echo "${F}";
					diff -r --ignore-space-change -x database.txt "${DST_PATH}/${F}/" "${DST_PATH}/upload/files/${F}" | sed -r "s#^diff.*(${DST_PATH}.*) (${DST_PATH}/upload/files/.*)#\nDIFF \2 \1#g";
					echo '--------------------------------------------------';
				done

				echo;

			elif [[ "${RUN}" == 'Database' ]]; then

				./cli --diff;

			elif [[ "${RUN}" == 'Continue' ]]; then

				CONTINUE="true";

				echo;

			elif [[ "${RUN}" == 'Cancel' ]]; then

				rm -rf "${DST_PATH}/upload/";
				echo 'Canceled';
				echo;
				exit 0;

			fi

			sleep 0.1; # Allow output from echo (stdout) to be sent before next 'select'.

			break; # Don't run 'select' normally (we need to re-print options - both for unrecognised input, and diff/database actions).

		done

	done

#--------------------------------------------------
# Wait until the user is ready
#--------------------------------------------------

	echo 'Press [enter] to continue, or [c] to cancel...';
	read KEY;

	if [[ "${KEY}" == 'c' ]]; then
		rm -rf "${DST_PATH}/upload/";
		echo 'Canceled';
		echo;
		exit 0;
	fi

#--------------------------------------------------
# For each folder
#--------------------------------------------------

	echo 'Move files to live...';

	for F in ${FOLDERS}; do

		#--------------------------------------------------
		# Clean up
		#--------------------------------------------------

			rm -rf "${DST_PATH}/${F}_del/";

		#--------------------------------------------------
		# Backup
		#--------------------------------------------------

			if [[ -d "${DST_PATH}/${F}/" ]]; then

				BACKUP=`ls -d ${DST_PATH}/backup/${F}_* 2> /dev/null | awk '{print substr($D, length($D) - 9)}'`

				if [[ "${BACKUP}" == "" ]] || [[ "${CURRENT}" != "${BACKUP}" ]]; then

					rm -rf ${DST_PATH}/backup/${F}_*;
					mv "${DST_PATH}/${F}/" "${DST_PATH}/backup/${F}_${CURRENT}/";

				else

					mv "${DST_PATH}/${F}/" "${DST_PATH}/${F}_del/"; # Fast move

				fi

			fi

		#--------------------------------------------------
		# Move to live
		#--------------------------------------------------

			mv "${DST_PATH}/upload/files/${F}" "${DST_PATH}/${F}/";

	done

	echo -e "  \033[1;34mDone\033[0m";

#--------------------------------------------------
# CLI
#--------------------------------------------------

	cd "${DST_PATH}/";
	rm -f "./cli";
	ln -s "./framework/0.1/cli/run.sh" "./cli";
	chmod 755 "./cli";

#--------------------------------------------------
# Run install file
#--------------------------------------------------

	echo;
	echo 'Run install script...';

	./cli --install | awk '{ print "    " $0;}';

	echo -e "  \033[1;34mDone\033[0m";

#--------------------------------------------------
# Double check the database
#--------------------------------------------------

	echo;
	echo 'Check database...';

	./cli --diff | awk '{ print "    " $0;}';

	echo -e "  \033[1;34mDone\033[0m";

#--------------------------------------------------
# Clean up
#--------------------------------------------------

	rm -rf "${DST_PATH}/upload/";

	for F in ${FOLDERS}; do
		rm -rf "${DST_PATH}/${F}_del/";
	done

	echo;
