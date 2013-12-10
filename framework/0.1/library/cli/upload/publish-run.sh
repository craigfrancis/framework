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
		exit 0;
	fi

	CURRENT=`date '+%Y_%m_%d'`;

#--------------------------------------------------
# Folders
#--------------------------------------------------

	FOLDERS="app httpd framework";

#--------------------------------------------------
# Testing
#--------------------------------------------------

	echo;

	CONTINUE="false";

	while [ $CONTINUE = "false" ];
	do

		OPTIONS="Diff Database Continue Cancel";

		select RUN in $OPTIONS; do

			if [ "$RUN" = "Diff" ]; then

				echo "--------------------------------------------------";

				for F in $FOLDERS; do
					echo "${F}";
					diff -r --ignore-space-change "${DST_PATH}/${F}/" "${DST_PATH}/upload/files/${F}" | sed -r "s#^diff.*(${LIVEROOT}.*) (${DEMOROOT}.*)#\nDIFF \2 \1#g";
					echo "--------------------------------------------------";
				done

			elif [ "$RUN" = "Database" ]; then

				echo "TODO";

			elif [ "$RUN" = "Continue" ]; then

				CONTINUE="true";

			elif [ "$RUN" = "Cancel" ]; then

				echo "Canceled";
				exit 0;

			fi

			echo;
			break;

		done

	done

#--------------------------------------------------
# Wait until the user is ready
#--------------------------------------------------

	echo "Press enter to continue...";
	read KEY;

	if [ "$KEY" = "c" ]; then
		echo "Canceled";
		exit 0;
	fi

#--------------------------------------------------
# For each folder
#--------------------------------------------------

	echo "Move files to live..."

	for F in $FOLDERS; do

		#--------------------------------------------------
		# Cleanup
		#--------------------------------------------------

			rm -rf "${DST_PATH}/${F}_del/";

		#--------------------------------------------------
		# Backup
		#--------------------------------------------------

			if [ -d "${DST_PATH}/${F}/" ]; then

				BACKUP=`ls -d ${DST_PATH}/backup/${F}_* 2> /dev/null | awk '{print substr($D, length($D) - 9)}'`

				if [ "${BACKUP}" = "" -o "${CURRENT}" != "${BACKUP}" ]; then

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

	echo " Done";

#--------------------------------------------------
# CLI
#--------------------------------------------------

	cd "${DST_PATH}/";

	if [ -f "${DST_PATH}/cli" ]; then
		rm "${DST_PATH}/cli";
	fi

	ln -s "./framework/0.1/cli/run.sh" "./cli";

#--------------------------------------------------
# Run install file
#--------------------------------------------------

	chmod 755 "./cli";

	echo ;
	echo "Run install script... ";

	./cli --install;

	echo " Done";

#--------------------------------------------------
# Cleanup
#--------------------------------------------------

	rm -rf "${DST_PATH}/upload/";

	for F in $FOLDERS; do
		rm -rf "${DST_PATH}/${F}_del/";
	done
