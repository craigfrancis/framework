#!/bin/bash

set -u;

launchctl remove "${1}";
launchctl load "${2}";
