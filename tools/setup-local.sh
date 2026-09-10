#!/usr/bin/env bash

set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_dir"

docker compose up -d database wordpress

echo "Waiting for WordPress..."
for attempt in $(seq 1 60); do
	if curl --silent --fail http://localhost:8080/ >/dev/null; then
		break
	fi
	if [ "$attempt" -eq 60 ]; then
		echo "WordPress did not become ready. Run: docker compose logs wordpress"
		exit 1
	fi
	sleep 2
done

if ! docker compose run --rm wpcli core is-installed >/dev/null 2>&1; then
	docker compose run --rm wpcli core install \
		--url="http://localhost:8080" \
		--title="Diastoles local" \
		--admin_user="admin" \
		--admin_password="diastoles-local" \
		--admin_email="local@example.test" \
		--skip-email
fi

docker compose run --rm wpcli plugin activate diastoles

page_id="$(docker compose run --rm wpcli post list --post_type=page --name=diastoles --field=ID | tr -d '\r')"
if [ -z "$page_id" ]; then
	page_id="$(docker compose run --rm wpcli post create \
		--post_type=page \
		--post_status=publish \
		--post_title="Diastoles" \
		--post_name="diastoles" \
		--post_content="[diastoles_experience]" \
		--porcelain | tr -d '\r')"
fi

docker compose run --rm wpcli option update show_on_front page
docker compose run --rm wpcli option update page_on_front "$page_id"
docker compose run --rm wpcli rewrite structure '/%postname%/' --hard

echo
echo "Diastoles is ready:"
echo "  Experience: http://localhost:8080/"
echo "  Admin:      http://localhost:8080/wp-admin/"
echo "  Username:   admin"
echo "  Password:   diastoles-local"
echo
echo "AI mode defaults to Mock. Configure Anthropic under Diastoles > AI settings."
