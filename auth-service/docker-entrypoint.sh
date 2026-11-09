#!/bin/sh
set -e

# Use environment variables with fallbacks
DB_HOST=${DB_HOST:-mysql-db}
DB_PORT=${DB_PORT:-3306}

# Wait for database to be ready
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until nc -z "$DB_HOST" "$DB_PORT" > /dev/null 2>&1; do
  echo "Database (${DB_HOST}) is not available yet - sleeping"
  sleep 1
done
echo "Database is ready!"

if [ "${SKIP_MIGRATIONS}" != "true" ]; then
  # Ensure dependencies are up-to-date (fixes named volume caching issues)
  echo "Installing dependencies..."
  composer install --no-interaction --optimize-autoloader

  # Ensure .env exists
  if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
  fi

  # Ensure APP_KEY is generated
  if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
  fi

  # Ensure INTERNAL_ENCRYPTION_KEY is generated if missing or set to placeholder
  if grep -q "^INTERNAL_ENCRYPTION_KEY=$" .env || grep -q "^INTERNAL_ENCRYPTION_KEY=your_secure_32_char_key_here" .env; then
    echo "Generating INTERNAL_ENCRYPTION_KEY..."
    NEW_KEY=$(openssl rand -hex 16)
    sed -i "s/^INTERNAL_ENCRYPTION_KEY=.*/INTERNAL_ENCRYPTION_KEY=${NEW_KEY}/" .env
  fi

  # Discover packages
  echo "Discovering packages..."
  php artisan package:discover --ansi

  # Run migrations
  echo "Running migrations..."
  php artisan migrate --force

  # Seed only if seed accounts are missing (idempotent — won't overwrite real data)
  ADMIN_EXISTS=$(php artisan tinker --execute="echo \App\Models\User::where('email', 'admin@example.com')->count();" 2>/dev/null | tail -1)
  if [ "${ADMIN_EXISTS}" = "0" ] || [ -z "${ADMIN_EXISTS}" ]; then
    echo "Seed accounts not found — seeding database..."
    php artisan db:seed --force
  else
    echo "Database already has seed accounts — skipping seed."
  fi

  # Generate Passport keys if missing
  if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating Passport keys..."
    php artisan passport:keys --force
  fi

  # Ensure a personal access client exists
  CLIENT_COUNT=$(php artisan tinker --execute="echo Laravel\Passport\Client::whereJsonContains('grant_types', 'personal_access')->count();" 2>/dev/null | tail -1)
  if [ "${CLIENT_COUNT}" = "0" ] || [ -z "${CLIENT_COUNT}" ]; then
    echo "Creating Passport Personal Access Client..."
    php artisan passport:client --personal --name="Central Auth Personal Access Client" --provider=users --no-interaction || true
  fi

  # Fix line endings on Windows mounted volumes for OpenSSL
  sed -i 's/\r$//' storage/oauth-private.key storage/oauth-public.key 2>/dev/null || true

  # Clear cache after migrations
  echo "Clearing cache..."
  php artisan cache:clear
  php artisan optimize:clear
else
  echo "Skipping initialization tasks (SKIP_MIGRATIONS is set to true)..."
fi

# Start the application
echo "Starting application..."
exec "$@"
