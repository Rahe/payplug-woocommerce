# Howto test and dev

### Introduction

The tests are based on [WP-Browser](https://github.com/lucatume/wp-browser).

You have WPUnit and Acceptance tests.  

All the tests are gathered on the `tests` folder, grouped by type :
* acceptance : the tests runned into the browser
* wp-unit : the tests runned with WordPress loaded

All the scripts and binaries are on the `bin` folder

The file `tests/_data/dump.sql` contains the dump loaded at every tests to reset the database to something clean. 

### Installation

1. Install [Lando](https://docs.devwithlando.io/installation/installing.html)
2. From command line into the project folder execute `./bin/setup-lando.sh`
3. Copy `.env.dist` file to `.env` file and fill the environment variables

The local url will be https://payplug.lndo.site and credentials will be
* user : admin
* password : admin

### Tools
To test the code, just launch :
* For all tests(unit, acceptance) : `lando test`
* For Wpunit tests : `lando test-wpunit`
* For Acceptance tests : `lando test-acceptance`
* For BrowserStack tests : `lando test-acceptance-browserstack`

If you need to test the code on BrowserStack, you need to define two environments variables :
* `BROWSERSTACK_USER` : the username of your browserStack account
* `BROWSERSTACK_KEY` : the key of your browserStack account

/!\ Do not commit theses credentials /!\

### Execute one test
If you want to execute only one test, you can do :
* `lando test-acceptance Payment/PaymentCest:testConfiguredCartCheckoutCancel` for the functionnal ones
* `lando test-wpunit AdminFiltersTest:test_CheckAdminFilter` for the unit ones

## Customization

Need to customize the environment variables ? every codeception file can be overrided bit by bit by creating a new file without the .dist.
So to customize the .env file you'l need to :

* Create a codeception.yml file
* Put into the file :
```
params:
- .env.local
```
* Create a .env.local file and change the desired environment variables like `BROWSERSTACK_KEY`
