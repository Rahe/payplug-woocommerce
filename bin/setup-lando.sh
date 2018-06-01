#!/usr/bin/env bash

composer install --prefer-dist -o
mkdir wordpress
cd wordpress
wp core download
wp config create --dbname="wordpress" --dbuser="wordpress" --dbpass="wordpress" --dbhost="database" --dbprefix="wp_"
wp core install --url="payplug.localhost" --title="Test" --admin_user="admin" --admin_password="admin" --admin_email="admin@payplug.localhost" --skip-email
wp rewrite structure '/%postname%/' --hard


# Install Woocommerce to needed version
wp plugin install --activate woocommerce --force
wp plugin install --activate wordpress-importer
wget https://raw.githubusercontent.com/woocommerce/woocommerce/master/sample-data/sample_products.xml

# Import the products
wp import sample_products.xml --authors=create

# Storefront theme
wp theme install --activate storefront

# Make the folder for woocommerce, copy files initialy and folder
cd ..
mkdir wordpress/wp-content/plugins/payplug-woocommerce/
cp -R payplug.php wordpress/wp-content/plugins/payplug-woocommerce/
cp -R composer.json wordpress/wp-content/plugins/payplug-woocommerce/
cp -R composer.lock wordpress/wp-content/plugins/payplug-woocommerce/
cp -R src wordpress/wp-content/plugins/payplug-woocommerce/
cp -R assets wordpress/wp-content/plugins/payplug-woocommerce/
cp -R languages wordpress/wp-content/plugins/payplug-woocommerce/
cd wordpress/wp-content/plugins/payplug-woocommerce/
composer install --no-dev --prefer-dist -o