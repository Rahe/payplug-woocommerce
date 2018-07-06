# PayPlug for WooCommerce

WIP - PayPlug gateway for WooCommerce.

# Howto test and dev

* Install lando on https://docs.devwithlando.io/
* Go to the plugin folder and `run lando start`
* `lando setup-tests`
* In a second console tab make `lando launch-chromedriver`. This will launch a phantomJS driver
* In a third console tab make `lando share -u http://localhost:port-for-https`. This will create a sharable link in the console. Do no press any button or this will be closed.
* You can now make 
    * `lando test-acceptance-all` to launch all acceptance tests one by one
    * `lando test-acceptance PaymentCest` to launch all acceptance test at a time
* URL is https://payplug.localhost
* URL from tunnel must be https://payplug.localtunnel.me
* Create a copy of `.env` into `.env.local` with the local environment variables
* Create a `codeception.yml file and add params : - .env.local
* Create a `tests/acceptance.suite.yml` with the local environment variables

# Environement variables to set
* PayPlug
    * `PAYPLUG_TEST_EMAIL` : the email for the login to payplug
    * `PAYPLUG_TEST_PASSWORD` : the password for the login to payplug
* Payplug certified
    * `PAYPLUG_TEST_EMAIL_CERTIFIED` : the email for the certified payplug account
    * `PAYPLUG_TEST_PASSWORD_CERTIFIED` : the password for the certified payplug account
* BrowserStack
    * `BROWSERSTACK_USER` : the user for the acceptance tets if you want to tests them on browserStack
    * `BROWSERSTACK_KEY` : the key for the acceptance tets if you want to tests them on browserStack