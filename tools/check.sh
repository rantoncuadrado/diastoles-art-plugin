#!/usr/bin/env bash

set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_dir"

docker compose config --quiet
node --check diastoles/public/diastoles.js

while IFS= read -r php_file; do
	container_file="/var/www/html/wp-content/plugins/diastoles/${php_file#diastoles/}"
	docker compose exec -T wordpress php -l "$container_file" </dev/null >/dev/null
done < <(find diastoles -type f -name '*.php' | sort)

if ! docker compose run --rm wpcli plugin is-active diastoles >/dev/null 2>&1; then
	echo "Diastoles plugin is not active."
	exit 1
fi

api_state="$(curl --silent --show-error --fail http://localhost:8080/wp-json/diastoles/v1/state)"
if ! echo "$api_state" | jq -e 'has("authenticated") and has("ai_mode")' >/dev/null; then
	echo "The public state endpoint returned an unexpected response."
	exit 1
fi

echo "All local checks passed."
