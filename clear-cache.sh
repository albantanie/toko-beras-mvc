#!/bin/bash

echo "Clearing Laravel caches..."

# Clear application cache
./vendor/bin/sail artisan cache:clear

# Clear config cache
./vendor/bin/sail artisan config:clear

# Clear route cache
./vendor/bin/sail artisan route:clear

# Clear view cache
./vendor/bin/sail artisan view:clear

# Clear compiled views
./vendor/bin/sail artisan view:cache

# Clear session data
./vendor/bin/sail artisan session:flush

echo "All caches cleared successfully!"
