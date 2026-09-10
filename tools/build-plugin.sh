#!/usr/bin/env bash

set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_dir"

mkdir -p dist
rm -f dist/diastoles.zip
zip -qr dist/diastoles.zip diastoles \
	-x '*.DS_Store' \
	-x '*/.git/*'

echo "Built dist/diastoles.zip"
