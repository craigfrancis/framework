#!/bin/bash

find . -type l -print0 | xargs -0 rm;

for F in `ls -d ../../*/app`; do
	P=`echo "$F" | sed -e 's/.*\/\(.*\)\/app/\1/'`;
	ln -s "$F" "$P";
done
