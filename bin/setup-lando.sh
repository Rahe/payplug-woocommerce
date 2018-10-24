#!/usr/bin/env bash

WOOCOMMERCE_VERSION=${1-3.5.0}
WP_VERSION=${2-latest}

composer install --prefer-dist -o
mkdir -p wordpress
cd wordpress
wp db reset --yes
wp core download --version=${WP_VERSION} --force
wp config create --dbname="wordpress" --dbuser="wordpress" --dbpass="wordpress" --dbhost="database" --dbprefix="wp_"
wp core install --url="https://payplug.lndo.site" --title="Test" --admin_user="admin" --admin_password=admin --admin_email="admin@payplug.lndo.site" --skip-email
wp rewrite structure '/%postname%/' --hard


# Install Woocommerce to needed version
wp plugin install --activate woocommerce --force --version=${WOOCOMMERCE_VERSION}
wp plugin install --activate wordpress-importer
wget https://raw.githubusercontent.com/woocommerce/woocommerce/master/sample-data/sample_products.xml

# Import the products
wp import sample_products.xml --authors=create
rm sample_products.xml

# Storefront theme
wp theme install --activate storefront

# Make the folder for woocommerce, copy files initialy and folder
cd ..
mkdir -p wordpress/wp-content/plugins/payplug-woocommerce/
cp -R payplug.php wordpress/wp-content/plugins/payplug-woocommerce/
cp -R composer.json wordpress/wp-content/plugins/payplug-woocommerce/
cp -R composer.lock wordpress/wp-content/plugins/payplug-woocommerce/
cp -R src wordpress/wp-content/plugins/payplug-woocommerce/
cp -R assets wordpress/wp-content/plugins/payplug-woocommerce/
cp -R languages wordpress/wp-content/plugins/payplug-woocommerce/
cd wordpress/wp-content/plugins/payplug-woocommerce/
composer install --no-dev --prefer-dist -o

# Activate plugin
cd ../../../
wp plugin activate payplug-woocommerce