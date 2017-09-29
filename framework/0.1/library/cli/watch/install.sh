#!/bin/bash

set -u;

	# Look for loaded watchers:
	#   launchctl list | grep com.phpprime.watch

rm -f "${2}";
ln -s "${3}" "${2}";

launchctl remove "${1}";
launchctl load "${3}";
