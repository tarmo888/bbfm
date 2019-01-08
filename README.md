This is the repo for the "byteball for web merchant payment gateway" back office. Requires php 7+ and Mysql 5+

# *Public website*
bbfm_www directory

# *included files*

*/var/www/bbfm_xfiles/bbfm_conf.php* : configuration file

*/var/www/bbfm_xfiles/bbfm_function.php* : common functions

*/var/www/bbfm_xfiles/mysqli_connect.php* : connects to mysqli database and setup global $mysqli var


# *public api*

*/var/www/bbfm_www/api/ask_payment.php* : 
 - generates and registers new (pending) payment in bbfm database (ajax called by payment-button.js)
 - (ajax) tells to payment button (payment-button.js) current status of an existing payment

*/var/www/bbfm_www/api/payment-button.css* : payment button css style sheet

*/var/www/bbfm_www/api/payment-button.js* : payment button javascript (called from merchant payment page)

*/var/www/bbfm_www/api/icon/* : self speaking

*/var/www/bbfm_www/api/img/* : self speaking


# *cron*

*bbfm_payer.php* : watches incoming transactions and send payment (or error notifications), minus fees, to merchant.

*Get_rates.php* : fetches FIAT/Gbytes rates

# *sql*
Mysql schemas
