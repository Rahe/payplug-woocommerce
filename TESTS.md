# Howto test and dev

* Install lando on https://docs.devwithlando.io/
* Go to the plugin folder and run `lando start`
* `lando setup-tests`
* In a second console tab make `lando launch-chromedriver`. This will launch a chromedriver instance
* You can now make 
    * `lando test-acceptance-all` to launch all acceptance tests one by one
    * `lando test-acceptance PaymentCest` to launch all acceptance test at a time
* URL is https://payplug.lando.site
* Create a copy of `.env.dist` into `.env` with the local environment variables

# Environement variables to set
* PayPlug
    * `PAYPLUG_TEST_EMAIL` : the email for the login to payplug
    * `PAYPLUG_TEST_PASSWORD` : the password for the login to payplug
* Payplug certified
    * `PAYPLUG_TEST_EMAIL_CERTIFIED` : the email for the certified payplug account
    * `PAYPLUG_TEST_PASSWORD_CERTIFIED` : the password for the certified payplug account
* BrowserStack
    * `BROWSERSTACK_USER` : the user for the acceptance tests if you want to tests them on browserStack
    * `BROWSERSTACK_KEY` : the key for the acceptance tests if you want to tests them on browserStack
    