#!/bin/bash
set -e

if [ "$#" -ne 1 ]; then
    echo "Usage: ./pin.sh COMMIT"
    exit 1
fi

git checkout "$1"
