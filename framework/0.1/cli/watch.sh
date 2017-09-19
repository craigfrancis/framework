#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	export PATH="/usr/local/bin:/usr/bin:/bin";

	framework_root="${1}";
	project_root="${2}";

	update_path="${framework_root}/library/cli/watch/update.php";
	watch_folder="${project_root}/app";
	watch_path="${project_root}/private/watch/files.txt";
	watch_log="${project_root}/private/watch/log.txt";
	temp_folder=$(mktemp -d "${TMPDIR:-/tmp/}$(basename $0).XXXXXXXXXXXX");
	temp_path="${temp_folder}/watch.txt";

#--------------------------------------------------
# Reset old log
#--------------------------------------------------

	if test "`find ${watch_log} -mmin +60`"; then
		echo -n > "${watch_log}";
	fi

#--------------------------------------------------
# Record update
#--------------------------------------------------

	date >> "${watch_log}";

#--------------------------------------------------
# Find changes
#--------------------------------------------------

	if [[ -f "${watch_path}" ]]; then
		find "${watch_folder}" -type f ! -name "watch.php" -newer "${watch_path}" -print0 | xargs -0 stat -f "%m %N" | sort -rn > "${temp_path}";
	else
		find "${watch_folder}" -type f ! -name "watch.php" -print0 | xargs -0 stat -f "%m %N" | sort -rn > "${temp_path}";
	fi

	last_file=`head -1 "${temp_path}" | sed 's/^[0-9]* //'`;

#--------------------------------------------------
# Update
#--------------------------------------------------

	if [[ "${last_file}" != "" ]]; then
		touch -r "${last_file}" "${last_file}";
		mv "${temp_path}" "${watch_path}";
		php "${update_path}" -r "${project_root}" >> "${watch_log}";
	fi

#--------------------------------------------------
# Cleanup
#--------------------------------------------------

	rm -rf "${temp_folder}";
