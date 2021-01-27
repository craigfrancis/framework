#!/bin/bash

#--------------------------------------------------
# Config
#--------------------------------------------------

	set -u;

	new_key_value="$(< /dev/stdin)";
	new_key_identifier="${1}";
	new_key_path="${2}";
	new_key_mode="${3}"; # 1 to create directly, 2 to make a '.new' file first, 3 to move



whoami;

echo "STDIN: '${new_key_value}'";
echo;
echo "1) ${new_key_identifier}";
echo "2) ${new_key_path}";
echo "3) ${new_key_mode}";


exit 5;

#--------------------------------------------------
# Create file
#--------------------------------------------------

	# new_key_path="/etc/prime-config-key";
	#
	# if [ "${new_key_mode}" -eq 2 ]; then
	# 	new_key_path="${new_key_path}.new";
	# fi
	#
	# if [ "${new_key_mode}" -eq 1 ] || [ "${new_key_mode}" -eq 2 ]; then
	#
	# 	echo -n "${new_key_value}"; # > "${new_key_path}";
	#
	# fi
