This is the repo for the "Obyte for web merchant payment gateway" back office and public website.

Requires php 7+, Mysql 5+ and any web server (apache, nginx...)

Run `composer install` to install dependencies.

Run `cp .env-example .env` to copy configuration file.

Run `nano .env` to edit the configuration variables.

## included files

`bbfm_xfiles/bbfm_conf.php` : configuration file

`bbfm_xfiles/bbfm_function.php` : common functions

`bbfm_xfiles/mysqli_connect.php` : connects to mysqli database and setup global $mysqli var

## public website and api

`bbfm_www`: website

`bbfm_www/api/ask_payment.php` : 
 - generates and registers new (pending) payment in bbfm database (ajax called by payment-button.js)
 - (ajax) tells to payment button (payment-button.js) current status of an existing payment

`bbfm_www/api/payment-button.css` : payment button css style sheet

`bbfm_www/api/payment-button.js` : payment button javascript (called from merchant payment page)

`bbfm_www/api/icon/` : self speaking

`bbfm_www/api/img/` : self speaking

## scheduled tasks

`cron/bbfm_payer.php` : watches incoming transactions and send payment (or error notifications), minus fees, to merchant.

`cron/Get_rates.php` : fetches FIAT/Gbytes rates

## sql folder
Mysql schemas
