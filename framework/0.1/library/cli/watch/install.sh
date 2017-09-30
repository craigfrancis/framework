#!/bin/bash

set -u;

	# Look for loaded watchers:
	#   launchctl list | grep com.phpprime.watch

launchctl remove "${1}";
launchctl load "${2}";
