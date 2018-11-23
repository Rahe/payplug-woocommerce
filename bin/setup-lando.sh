#!/usr/bin/env bash

# Variables
WOOCOMMERCE_VERSION=${WOOCOMMERCE_VERSION-""}
WP_VERSION="${WP_VERSION:-latest}"
WP_LOCALE="${WP_LOCALE:-fr_FR}"
PHP_VERSION="${PHP_VERSION:-7.0}"

# Get to the root of the plugin folder
cd "$(dirname "$0")"
cd ..

# Hack - awaiting https://github.com/lando/lando/pull/750
perl -pi -we "s/^  php: .*/  php: '$PHP_VERSION'/" .lando.yml

lando start -v
#lando wp --version || lando bash test/install-wp-cli.sh

# Remove previous version of WP
rm -rf wordpress/[a-z]*

# Download WP into the right version and locale
lando wp core download \
    --path=/app/wordpress/ \
    --version=$WP_VERSION \
    --locale=$WP_LOCALE

# Create the wp-config.php file
lando wp config create \
    --path=/app/wordpress/ \
    --dbname=wordpress \
    --dbuser=wordpress \
    --dbpass=wordpress \
    --dbhost=database

# Deactive the updates
lando wp config set \
    --path=/app/wordpress/ \
    --type=constant \
    --raw \
    WP_AUTO_UPDATE_CORE false

# Activate the DEBUG
lando wp config set \
    --path=/app/wordpress/ \
    --type=constant \
    --raw \
    WP_DEBUG true

# Reset the database
lando wp db reset \
    --path=/app/wordpress/ \
    --yes

# Create our instance
wp_url="https://payplug.lndo.site"
lando wp core install \
    --path=/app/wordpress/ \
    --url="$wp_url" \
    '--title="My Test Site"' \
    --admin_user="admin" \
    --admin_password="admin" \
    --admin_email="admin@payplug.lndo.site" \
    --skip-email

# Setup permalinks and create the .htaccess file
lando wp rewrite structure \
    --path=/app/wordpress/ \
    '/%postname%/' \
    --hard

# Install Woocommerce to needed version
lando wp plugin install \
    --activate woocommerce \
    --force \
    --version=${WOOCOMMERCE_VERSION} \
    --path=/app/wordpress/

# Add importer
lando wp plugin install \
    --activate \
    wordpress-importer \
    --path=/app/wordpress/

# Get the product sample
wget https://raw.githubusercontent.com/woocommerce/woocommerce/master/sample-data/sample_products.xml

# Import the products
lando wp import \
    sample_products.xml \
    --authors=create \
    --path=/app/wordpress/

# Remove the useless file
rm sample_products.xml

# Add storefront theme and activate
lando wp theme install \
    --activate storefront \
    --path=/app/wordpress/

# Make the folder for plugin, copy files initially and folder
PLUGIN_FOLDER="wordpress/wp-content/plugins/payplug-woocommerce/"
mkdir -p $PLUGIN_FOLDER
cp -R payplug.php $PLUGIN_FOLDER
cp -R composer.json $PLUGIN_FOLDER
cp -R composer.lock $PLUGIN_FOLDER
cp -R src $PLUGIN_FOLDER
cp -R assets $PLUGIN_FOLDER
cp -R languages $PLUGIN_FOLDER
cd $PLUGIN_FOLDER

lando composer install --no-dev --prefer-dist -o

# Activate plugin
cd ../../../
lando wp plugin activate \
    payplug-woocommerce

echo '✅ Everything installed ! ✅'
echo 'You have the commands :
 - `lando test-acceptance` for launching one test
 - `test-acceptance-all` for launching all acceptance tests
 - `test-wpunit` for launching one wordpress unit test
 - `test-wpunit-all` for launching all wordpress unit tests

All the results will be displayed on tests/_output/ if any error occurs.
'
echo 'For all the lando commands just type `lando`'