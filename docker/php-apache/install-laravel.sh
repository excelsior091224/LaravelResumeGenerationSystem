#!/bin/sh
set -eu

APP_PATH=/workspaces/LaravelResumeGenerationSystem/src

if [ ! -f "$APP_PATH/artisan" ]; then
    mkdir -p "$APP_PATH"
    rm -rf /tmp/laravel
    composer create-project laravel/laravel /tmp/laravel --prefer-dist
    cp -a /tmp/laravel/. "$APP_PATH/"
    rm -rf /tmp/laravel
fi

chgrp -R www-data "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
chmod -R ug+rwX "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
find "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" -type d -exec chmod g+s {} +
