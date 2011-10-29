#!/bin/bash

PATH="$0";
cd `/usr/bin/dirname "${PATH}"`;
PATH=`/usr/bin/basename "${PATH}"`;
SOURCE=`pwd -P`;

while [ -L "${PATH}" ]; do
	PATH=`/usr/bin/readlink "${PATH}"`;
	cd `/usr/bin/dirname "${PATH}"`;
	PATH=`/usr/bin/basename "${PATH}"`;
done

DIR=`pwd -P`;
cd "$SOURCE";

/usr/bin/php $DIR/run.php $@;
