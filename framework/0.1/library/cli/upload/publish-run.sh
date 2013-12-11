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
# Checking
#--------------------------------------------------

	echo;

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

				echo 'Canceled';
				exit 0;

			fi

			break;

		done

	done

#--------------------------------------------------
# Wait until the user is ready
#--------------------------------------------------

	echo 'Press enter to continue...';
	read KEY;

	if [[ "${KEY}" == 'c' ]]; then
		echo 'Canceled';
		exit 0;
	fi

#--------------------------------------------------
# For each folder
#--------------------------------------------------

	echo 'Move files to live...';

	for F in ${FOLDERS}; do

		#--------------------------------------------------
		# Cleanup
		#--------------------------------------------------

			rm -rf "${DST_PATH}/${F}_del/";

		#--------------------------------------------------
		# Backup
		#--------------------------------------------------

			if [[ -d "${DST_PATH}/${F}/" ]]; then

				BACKUP=`ls -d ${DST_PATH}/backup/${F}_* 2> /dev/null | awk '{print substr($D, length($D) - 9)}'`

				if [[ "${BACKUP}" == "" -o "${CURRENT}" != "${BACKUP}" ]]; then

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

	echo ' Done';

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

	./cli --install;

	echo ' Done';

#--------------------------------------------------
# Cleanup
#--------------------------------------------------

	rm -rf "${DST_PATH}/upload/";

	for F in ${FOLDERS}; do
		rm -rf "${DST_PATH}/${F}_del/";
	done
