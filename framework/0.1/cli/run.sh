#!/bin/bash

export PATH="/bin/:/usr/bin/";

FILE="$0";
cd `dirname "${FILE}"`;
FILE=`basename "${FILE}"`;
SOURCE=`pwd -P`;

while [ -L "${FILE}" ]; do
	FILE=`readlink "${FILE}"`;
	cd `dirname "${FILE}"`;
	FILE=`basename "${FILE}"`;
done

DIR=`pwd -P`;
cd "$SOURCE";

php $DIR/run.php $@;
