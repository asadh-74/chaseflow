#!/usr/bin/env bash
# ChaseFlow setup: turns the repo (which only holds the app files) into a complete, runnable
# Laravel 11 project, fixes the deployment config, starts a local MySQL, migrates, seeds, tests.
# Run from the repo root inside a GitHub Codespace:   bash chaseflow-setup.sh
set -euo pipefail
cd "$(dirname "$(readlink -f "$0")")"

[ -f composer.json ] && [ -d app ] || { echo "Run this from the root of the chaseflow repo."; exit 1; }

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

patch_composer() {
  say "Fixing composer.json (faker version, S3 driver)"
  python3 - <<'PY'
import json
c = json.load(open('composer.json'))
c['require-dev']['fakerphp/faker'] = '^1.23'                 # ^9.2 does not exist
c['require']['league/flysystem-aws-s3-v3'] = '^3.0'          # needed for the S3 disk
json.dump(c, open('composer.json', 'w'), indent=4)
open('composer.json', 'a').write('\n')
PY
}

write_infra() {
  say "Writing Dockerfile, start script, render.yaml, CI workflow"
  mkdir -p docker .github/workflows

  cat > Dockerfile <<'EOF_DOCKER'
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libzip-dev \
 && docker-php-ext-install zip pdo_mysql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
 && chmod +x docker/start.sh

CMD ["docker/start.sh"]
EOF_DOCKER

  cat > docker/start.sh <<'EOF_START'
#!/bin/bash
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Managed MySQL (Aiven etc.) uses its own CA: pass the PEM text in the MYSQL_CA_PEM env var.
if [ -n "${MYSQL_CA_PEM:-}" ]; then
  printf '%s\n' "$MYSQL_CA_PEM" > /usr/local/share/mysql-ca.pem
  export MYSQL_ATTR_SSL_CA=/usr/local/share/mysql-ca.pem
fi

php artisan package:discover --ansi
[ -n "${APP_KEY:-}" ] || php artisan key:generate --force

php artisan migrate --force
php artisan db:seed --force        # idempotent (updateOrCreate): demo data without duplicates

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache

# The free Render plan has no separate worker or cron service, so run both inside this container.
( while true; do runuser -u www-data -- php artisan queue:work --queue=default,mail --tries=3 --sleep=3 --max-time=3000 || true; sleep 2; done ) &
( while true; do runuser -u www-data -- php artisan schedule:work || true; sleep 5; done ) &

PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
EOF_START
  chmod +x docker/start.sh

  cat > .dockerignore <<'EOF_IGNORE'
.git
.github
.env
vendor
node_modules
database/database.sqlite
storage/logs/*
EOF_IGNORE

  cat > render.yaml <<'EOF_RENDER'
# One free web service. The queue worker and the scheduler run inside it (see docker/start.sh).
# MySQL is NOT provided by Render: use an external MySQL (e.g. Aiven free plan) and fill the
# "sync: false" values in the Render dashboard.
services:
  - type: web
    name: chaseflow
    runtime: docker
    plan: free
    healthCheckPath: /up
    envVars:
      - key: APP_NAME
        value: ChaseFlow
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: APP_KEY
        sync: false            # output of: php artisan key:generate --show
      - key: APP_URL
        sync: false            # https://<your-service>.onrender.com
      - key: LOG_CHANNEL
        value: stderr
      - key: DB_CONNECTION
        value: mysql
      - key: DB_HOST
        sync: false
      - key: DB_PORT
        sync: false
      - key: DB_DATABASE
        sync: false
      - key: DB_USERNAME
        sync: false
      - key: DB_PASSWORD
        sync: false
      - key: MYSQL_CA_PEM
        sync: false            # the CA certificate text from your MySQL provider
      - key: CACHE_STORE
        value: database
      - key: SESSION_DRIVER
        value: database
      - key: QUEUE_CONNECTION
        value: database
      - key: MAIL_MAILER
        value: log             # switch to "ses" once AWS SES is verified
      - key: MAIL_FROM_ADDRESS
        value: invoices@example.com
      - key: MAIL_FROM_NAME
        value: ChaseFlow
      - key: FILESYSTEM_DISK
        value: local           # switch to "s3" once the bucket exists
      - key: AWS_ACCESS_KEY_ID
        sync: false
      - key: AWS_SECRET_ACCESS_KEY
        sync: false
      - key: AWS_DEFAULT_REGION
        value: us-east-1
      - key: AWS_BUCKET
        sync: false
      - key: LATE_FEE_PERCENT
        value: "5"
EOF_RENDER

  cat > .github/workflows/ci.yml <<'EOF_CI'
name: CI
on:
  push:
  pull_request:
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_sqlite
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan test
EOF_CI
}

patch_readme() {
  say "Correcting the README (AWS free tier, Render deployment)"
  python3 - <<'PY'
import re
s = open('README.md').read()
s = s.replace("- Free tier: 62,000 SES emails/month and 5 GB S3 — effectively free for a portfolio/demo.",
              "- Free tier: new AWS accounts get 3,000 SES message charges/month for 12 months plus Free Tier credits; "
              "S3 costs cents for a handful of PDFs. Check the AWS pricing pages for current terms.")
new_deploy = """## Deployment on Render (free)

Render has no managed MySQL, so the database lives outside Render (Aiven's free MySQL works well).

1. Create a free MySQL service on Aiven. Copy host, port, database, user, password and the CA certificate.
2. Push this repo to GitHub. In Render: **New -> Blueprint** -> pick the repo (reads `render.yaml`).
3. Fill the prompted values: `APP_KEY` (`php artisan key:generate --show`), `APP_URL`, `DB_*`, `MYSQL_CA_PEM`.
4. Optional AWS: verify a sender in SES, create a private S3 bucket and an IAM user limited to those two,
   then set `AWS_*`, `MAIL_MAILER=ses`, `FILESYSTEM_DISK=s3`.

The queue worker and the nightly scheduler run inside the single web container (`docker/start.sh`).
Free web services sleep when idle, so the nightly run only happens while the service is awake.

"""
s = re.sub(r"## Deployment on Render \(Blueprint\).*?(?=## AWS notes)", new_deploy, s, flags=re.S)
open('README.md', 'w').write(s)
PY
}

if [ "${CF_ONLY_FILES:-}" = "1" ]; then patch_composer; write_infra; patch_readme; exit 0; fi

patch_composer
write_infra
patch_readme

if [ ! -f artisan ]; then
  say "Adding the Laravel 11 skeleton files that are missing from the repo"
  TMP="$(mktemp -d)/skeleton"
  composer create-project laravel/laravel:^11.0 "$TMP" --no-install --no-scripts --no-interaction
  cp -an "$TMP"/. .                         # -n: never overwrite the repo's own files
  rm -rf "$TMP"
  # The skeleton ships its own users/cache migrations; the repo already has equivalents.
  rm -f database/migrations/0001_01_01_000000_create_users_table.php \
        database/migrations/0001_01_01_000001_create_cache_table.php
fi

say "Installing PHP dependencies"
composer install --no-interaction

[ -f .env ] || cp .env.example .env
setenv() { if grep -qE "^#?\s*$1=" .env; then sed -i -E "s|^#?\s*$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
php artisan key:generate --force

say "Starting a local MySQL 8 in Docker"
if ! docker ps -a --format '{{.Names}}' | grep -q '^chaseflow-mysql$'; then
  docker run -d --name chaseflow-mysql -p 3306:3306 \
    -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=chaseflow mysql:8.4 >/dev/null
else
  docker start chaseflow-mysql >/dev/null
fi
for i in $(seq 1 40); do
  docker exec chaseflow-mysql mysqladmin ping -uroot -psecret --silent >/dev/null 2>&1 && break
  sleep 2
done

setenv APP_NAME ChaseFlow
setenv DB_CONNECTION mysql
setenv DB_HOST 127.0.0.1
setenv DB_PORT 3306
setenv DB_DATABASE chaseflow
setenv DB_USERNAME root
setenv DB_PASSWORD secret
setenv CACHE_STORE database
setenv SESSION_DRIVER database
setenv QUEUE_CONNECTION database
setenv MAIL_MAILER log

say "Migrating and seeding"
php artisan migrate:fresh --seed --force

say "Running the tests"
php artisan test || echo "Some tests failed - paste the output into the chat and I will fix them."

cat <<'DONE'

Done. Start the app (two terminals, or run the first one in the background):
  php artisan queue:work --queue=default,mail &
  php artisan serve --host=0.0.0.0 --port=8000
Open port 8000 from the Ports tab and log in with  admin@chaseflow.test / password
Emails are written to storage/logs/laravel.log while MAIL_MAILER=log.
DONE
