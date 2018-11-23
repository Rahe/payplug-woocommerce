#!/usr/bin/env bash

# Get to the root of the plugin folder
cd "$(dirname "$0")"
cd ..

mkdir -p wordpress/wp-content/plugins/payplug-woocommerce/
cp payplug.php wordpress/wp-content/plugins/payplug-woocommerce/
cp composer.json wordpress/wp-content/plugins/payplug-woocommerce/
cp composer.lock wordpress/wp-content/plugins/payplug-woocommerce/
cp -R src wordpress/wp-content/plugins/payplug-woocommerce/
cp -R assets wordpress/wp-content/plugins/payplug-woocommerce/
cp -R languages wordpress/wp-content/plugins/payplug-woocommerce/

cd wordpress/wp-content/plugins/payplug-woocommerce/ && composer install --no-dev --prefer-dist -o
