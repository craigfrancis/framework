#!/bin/bash

FILE="$0";
INIT=`pwd -P`;
cd `dirname "${FILE}"`;
FILE=`basename "${FILE}"`;
SOURCE=`pwd -P`;

if [ "${FILE}" == "run.sh" ]; then

	INSTALL="0";
	while getopts ":i" OPT; do
		if [ "${OPT}" == "i" ]; then
			INSTALL="1";
		fi
	done

	if [ -L "${INIT}/cli" ]; then

		echo;
		echo "Please use your cli symlink, so we know where your project root is:";
		echo;
		echo "  ./cli $@";
		echo;
		exit;

	elif [ "${INSTALL}" == "1" ]; then

		ln -s "$0" "${INIT}/cli";
		SOURCE="${INIT}";

	else

		echo;
		echo "If this is a new project you probably want to run:";
		echo;
		echo "  $0 -i";
		echo "  ./cli $@";
		echo;
		echo "Or perhaps create a symlink, so we know where your project root is:";
		echo;
		echo "  ln -s $0 ./cli";
		echo "  ./cli $@";
		echo;
		exit;

	fi

fi

while [ -L "${FILE}" ]; do
	FILE=`readlink "${FILE}"`;
	cd `dirname "${FILE}"`;
	FILE=`basename "${FILE}"`;
done

DIR=`pwd -P`;
cd "$SOURCE";

php "${DIR}/run.php" $@;
