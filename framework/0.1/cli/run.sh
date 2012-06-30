#!/bin/bash

FILE="$0";
cd `/usr/bin/dirname "${FILE}"`;
FILE=`/bin/basename "${FILE}"`;
SOURCE=`pwd -P`;

while [ -L "${FILE}" ]; do
	FILE=`readlink "${FILE}"`;
	cd `/usr/bin/dirname "${FILE}"`;
	FILE=`/bin/basename "${FILE}"`;
done

DIR=`pwd -P`;
cd "$SOURCE";

/usr/bin/php $DIR/run.php $@;
