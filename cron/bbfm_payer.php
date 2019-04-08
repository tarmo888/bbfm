<?php
use Datto\JsonRpc\Http\Client as JsonRpcClient;

// /usr/bin/php /var/www/bbfm_xfiles/bbfm_payer.php
// Should be trigered from cron each minute.
// Watches for incoming transactions and send payment (or error notifications), minus fees, to merchant.
//!!!! Mind to fill your server IP in ENV.ALLOWED_NOTIF_IPS.

$time_in = microtime(true);

$XFILE_PATH = __DIR__ ."/../bbfm_xfiles/";

/*
* check if previous call from cron is still running
*/
$ps_aux = shell_exec( 'ps aux' );
// die($ps_aux);
$postpone_count_alert_level = 10; // will send warning email to admin if cron has been postponed more than times
$cron_postpone_counter_url = __DIR__ .'/../log/cron_postpone_counter.txt';
$running_nb = preg_match_all ( '@bbfm_payer.php@', $ps_aux );
if( $running_nb > 2 ){
	//echo $ps_aux;
	$cron_postpone_counter = file_get_contents( $cron_postpone_counter_url );
	$cron_postpone_counter ++;
	if( $cron_postpone_counter >= $postpone_count_alert_level ){
		cron_return_error( "BBFM cron has been postponed " . $postpone_count_alert_level . " times !? Something hanging ?", true );
	}
	file_put_contents( $cron_postpone_counter_url, $cron_postpone_counter );
	die( "\n" . "cron already running ($running_nb)...exit." );
}
else{
	$cron_postpone_counter = 0;
	file_put_contents( $cron_postpone_counter_url, $cron_postpone_counter );
}

include_once $XFILE_PATH . "bbfm_conf.php";
include_once $XFILE_PATH . "bbfm_function.php";
// Mysql connect ( setup global $mysqli )
include_once $XFILE_PATH . 'mysqli_connect.php';

// Check connection
if ( mysqli_connect_errno() ){
	cron_return_error( "Failed to connect to MySQL: " . mysqli_connect_error(), true );
	die();
}

$error_log = '';

/*
* check wallet synchro
*/
$max_unhandled = 500;
// $max_unhandled = 9999;
$client = new JsonRpcClient('http://127.0.0.1:6332');
$client->query(1, 'getinfo', []);
$Object = jsonrpc_call( $client );
echo print_r( $Object, true );
$count_unhandled = $Object['count_unhandled'];
echo "\ncount_unhandled: $count_unhandled\n";
if ( $count_unhandled >= $max_unhandled ){
	cron_return_error( "important unhandled count : " . $count_unhandled . "\r\n" . "but the BBFM cron will still try to make its work..." , true );
}

/****************************
 * scan transactions
 */

/*
* get list of all wallet transactions
*/
$transactions = listtransactions();
$transactions = $transactions ? $transactions : [];
// echo print_r( $transactions, true );

$debug_last_trans_nb = 10;
$transaction_num = 0;
foreach( $transactions as $transaction ){
	/*
	* debug : show last transactions
	*/
	$transaction_num ++;
	if( $transaction_num <= $debug_last_trans_nb ){
		echo "\nlast transaction $transaction_num : " . print_r( $transaction, true );
	}


	/*
	* received transactions
	*/
	if( $transaction['action'] == 'received' ){

		/*
		* ignoring "strange" incoming transactions (airdrop ?)
		*/
		// anormally small amount
		if( $transaction['amount'] < 60000 ){
			/*
			* check in table bbfm_ignored_received_unit
			*/
			$query_select = "select * from bbfm_ignored_received_unit where unit = '" . $transaction['unit'] . "' ";
			$q_select = mysqli_query($mysqli, $query_select);
			if( mysqli_num_rows( $q_select ) == 0 ){
				// no notif if it is (again) the 'LJ2' address (seems to be a wallet bug ?)
				if( $transaction['my_address'] != 'LJ2YJOD6IXCII2RHV6V7A3HJSMLQZM6Y' ){
					cron_return_error( "\nincoming unit " . $transaction['unit'] . " amount is too small ( " . $transaction['amount'] . " bytes ) and will be ignored -> bbfm will register the unit in table bbfm_ignored_received_unit.", true  );
				}
				mysqli_query($mysqli, "insert into bbfm_ignored_received_unit set creation=now(), unit = '" . $transaction['unit'] . "', amount = '" . $transaction['amount'] . "' ");
			}
			continue;
		}

		$query = "select * from bbfm";
		$query .= " where address_bbfm = '" . $transaction['my_address'] . "'";
//         echo "\nquery : $query";
		$q = mysqli_query($mysqli, $query);
//         echo "\nmysqli_num_rows: " . mysqli_num_rows( $q );
		if( ! $q ){
			cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
			continue;
		}
		if( mysqli_num_rows( $q ) == 0 ){
			/*
			* check in table bbfm_unknown_receiving_address
			*/
			$query_select = "select * from bbfm_unknown_receiving_address where address = '" . $transaction['my_address'] . "' and unit = '" . $transaction['unit'] . "' ";
			$q_select = mysqli_query($mysqli, $query_select);
			if( mysqli_num_rows( $q_select ) == 0 ){
				// no notif if it is (again) the 'LJ2' address (seems to be a wallet bug ?)
				if( $transaction['my_address'] != 'LJ2YJOD6IXCII2RHV6V7A3HJSMLQZM6Y' ){
					cron_return_error( "\naddress " . $transaction['my_address'] . " in unit " . $transaction['unit'] . " of received transaction not found in database -> bbfm will register the address in table bbfm_unknown_receiving_address.", true  );
				}

				mysqli_query($mysqli, "insert into bbfm_unknown_receiving_address set creation=now(), address = '" . $transaction['my_address'] . "', unit = '" . $transaction['unit'] . "', amount = '" . $transaction['amount'] . "' ");
			}
			continue;
		}

		$row = $q->fetch_array(MYSQLI_ASSOC);
		/*
		* new transaction
		*/
		if( ! $row[ 'receive_unit' ] ){
			$query = "update bbfm set receive_unit = '" . $transaction['unit'] . "'";
			$query .= ", receive_unit_date = now()";
			$query .= ", receive_unit_confirmed = '" . $transaction['confirmations'] . "'";
			$query .= ", received_amount  = '" . $transaction['amount'] . "'";
			$query .= " where id = '" . $row[ 'id' ] . "'";
//             echo "\nquery : $query";
			$q_receive = mysqli_query($mysqli, $query);
			if( ! $q_receive ){
				cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
				break;
			}
		}
		else{
			/*************************
			 * existing transaction
			 */

			/*
			* double payment (ie: different receive_unit )
			*/
			if( $transaction['unit'] !==  $row[ 'receive_unit' ] ){
				/*
				* check in table bbfm_payment_duplicate
				*/
				$query_select = "select * from bbfm_payment_duplicate where unit = '" . $transaction['unit'] . "' ";
				$q_select = mysqli_query($mysqli, $query_select);
				if( mysqli_num_rows( $q_select ) == 0 ){
					/*
					* new duplicate
					*/
					cron_return_error( "\nreceived " . $transaction['amount'] . " bytes in unit " . $transaction['unit'] . " which is duplicate payement of bbfm id " . $row[ 'id' ] . " -> bbfm will register the unit in table bbfm_payment_duplicate.", true  );
					$query = "insert into bbfm_payment_duplicate set creation=now(), unit = '" . $transaction['unit'] . "',  amount = '" . $transaction['amount'] . "',  bbfm_id = '" . $row[ 'id' ] . "' ";
					$q_duplicate = mysqli_query($mysqli, $query );
					if( ! $q_duplicate ){
						cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
						break;
					}
					/*
					* mail notify merchant
					*/
					if( $row[ 'email_notif' ] ){
						$MailBody = "Hello,
A duplicate payement of " . $transaction['amount'] . " bytes has been detected for your order UID " . $row[ 'merchant_order_UID' ] . "
Please contact us via our Discord for further information :
https://obyte.org/discord
Best Regards.
The Obyte For Merchants Team
obyte-for-merchants.com
";
						$MailSubject = "Obyte duplicate payment notification";
						$ToMail = $row[ 'email_notif' ];
						my_sendmail( $MailBody, $MailSubject, $ToMail );
					}
				}


			/*
			* update receive_unit_confirmed
			*/
			}elseif( $transaction['confirmations'] and ! $row[ 'receive_unit_confirmed' ] ){
				$query = "update bbfm set receive_unit_confirmed = '" . $transaction['confirmations'] . "'";
				$query .= " where id = '" . $row[ 'id' ] . "'";
				$q_receive = mysqli_query($mysqli, $query);
				if( ! $q_receive ){
					cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
					break;
				}
			}
		}
	}

	/*
	* sent transactions
	*/
	if( $transaction['action'] == 'sent' ){
		$query = "select * from bbfm";
		$query .= " where sent_unit = '" . $transaction['unit'] . "'";
//         $query .= " and global_status = 'sent' ";
//         echo "\nquery : $query";
		$q = mysqli_query($mysqli, $query);
//         echo "\nmysqli_num_rows: " . mysqli_num_rows( $q );
		if( ! $q ){
			cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
			break;
		}
		if( mysqli_num_rows( $q ) == 0 ){
//             cron_return_error( "\nunit " . $transaction['unit'] . " of sent transaction not found in database", true  );
//             continue;

				/*
				* check in table bbfm_unknown_sent_unit
				*/
				$query_select = "select * from bbfm_unknown_sent_unit where unit = '" . $transaction['unit'] . "' ";
				$q_select = mysqli_query($mysqli, $query_select);
				if( mysqli_num_rows( $q_select ) == 0 ){
					cron_return_error( "\nunit " . $transaction['unit'] . " of sent transaction not found in database  -> bbfm will register the unit in table bbfm_unknown_sent_unit.", true  );

					mysqli_query($mysqli, "insert into bbfm_unknown_sent_unit set creation=now(), unit = '" . $transaction['unit'] . "', amount = '" . $transaction['amount'] . "' ");
				}
				continue;

		}
		$row = $q->fetch_array(MYSQLI_ASSOC);

		/*
		* update sent_unit_confirmed
		*/
		if( $row[ 'global_status' ] == 'sent' ){
			if( $transaction['confirmations'] and ! $row[ 'sent_unit_confirmed' ] ){
				$query = "update bbfm set sent_unit_confirmed  = '" . $transaction['confirmations'] . "'";
				$query .= ", global_status = 'completed'";
				$query .= " where id = '" . $row[ 'id' ] . "'";
				$q_send = mysqli_query($mysqli, $query);
				if( ! $q_send ){
					cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
					break;
				}
			}
		}

	}

}

/****************************
 * send payments to merchants
 */
$query = "select * from bbfm";
$query .= " where receive_unit_confirmed  = '1'";
$query .= " and sent_amount is NULL";
$query .= " and global_status = 'pending'";
$q = mysqli_query($mysqli, $query);
if( ! $q ){
	cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
	exit;
}

while( $row = $q->fetch_array(MYSQLI_ASSOC) ){
    // // check amount
    // if( $row[ 'received_amount' ] !=  $row[ 'amount_BB_asked' ] ){
    //     $query = "update bbfm set global_status = 'error'";
    //     $query .= ", error_msg  = 'asked and received amounts do not match'";
    //     $query .= " where id = '" . $row[ 'id' ] . "'";
    //     mysqli_query($mysqli, $query);
    //     cron_return_error( "\nasked and received amounts do not match for payment " . $row[ 'id' ], true );
    //     continue;
    // }

	// check received_amount > fees
	if( $row[ 'received_amount' ] < fee_bbfm( $row[ 'received_amount' ] )  ){
		$query = "update bbfm set global_status = 'error'";
		$query .= ", error_msg  = 'no bytes sent : received amount does not cover the outgoing fees'";
		$query .= " where id = '" . $row[ 'id' ] . "'";
		mysqli_query($mysqli, $query);
		cron_return_error( "\nno bytes sent : received amount does not cover the outgoing fees for payment id " . $row[ 'id' ], true );
		continue;
	}

	/*
	* set amount to sent
	*/
	// special fee for binaryballs
	if( $row[ 'ref_merchant' ] == 13 ){
		$sent_amount = $row[ 'received_amount' ] - 1000;
	}
	else{
		$sent_amount = $row[ 'received_amount' ] - fee_bbfm( $row[ 'received_amount' ] );
	}

	/*
	* send Gbytes !
	*/
	$client = new JsonRpcClient('http://127.0.0.1:6332');
	$client->query(1, 'sendtoaddress', [$row[ 'address_merchant' ], $sent_amount]);
	echo "\nshell_string : sendtoaddress " . $row[ 'address_merchant' ] .' '. $sent_amount;
	$sendtoaddress = jsonrpc_call( $client );
	echo "\n" . print_r( $sendtoaddress );

	if( isset( $sendtoaddress['error'] ) ){
		$cron_message = "\norder " . $row[ 'id' ] . ".\nError when trying to send $sent_amount bytes to " . $row[ 'address_merchant' ] . "\n" . $sendtoaddress['error']['code'] . " : " . $sendtoaddress['error']['message'];

		// not enough spendable funds : we let status at 'pending' to resubmit
		if( $sendtoaddress['error']['code'] == -32603 ){
			$query = "update bbfm set global_status = 'pending'";
			$cron_message .= "\nNothing special to do cause BBFM will try again to send payment in one minute...";

		}
		else{
			$query = "update bbfm set global_status = 'error'";
			$cron_message .= "\nIntervention needed cause payment has been cancelled with 'error' status !";
		}
		$query .= ", error_msg  = CONCAT( error_msg, '-', '" . $sendtoaddress['error']['code'] . " : " . $sendtoaddress['error']['message'] . "' )";
		$query .= " where id = '" . $row[ 'id' ] . "'";
		$q_log = mysqli_query($mysqli, $query);
		if( ! $q_log ){
			cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
		}
		cron_return_error( $cron_message, true );
		continue;
	}

	/*
	* register in database
	*/
	$query = "update bbfm set global_status = 'sent'";
	$query .= ", sent_amount  = '$sent_amount'";
	$query .= ", fee_bbfm  = '" . fee_bbfm( $row[ 'received_amount' ] ) . "'";
	$query .= ", sent_unit_date  = now()";
	$query .= ", sent_unit  = '" . $sendtoaddress . "'";
	$query .= " where id = '" . $row[ 'id' ] . "'";
	$q_send = mysqli_query($mysqli, $query);
	if( ! $q_send ){
		cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
		continue;
	}

}

/*
* set 'sent' test mode payment to 'completed'
*/
$query = "update bbfm set global_status='completed'";
$query .= " where global_status = 'sent'";
$query .= " and mode='test'";
$q = mysqli_query($mysqli, $query);

/*
* set 'pending' test mode payment to 'sent'
*/
$query = "update bbfm set global_status='sent'";
$query .= " where global_status = 'pending'";
$query .= " and receive_unit is not NULL";
$query .= " and mode='test'";
$q = mysqli_query($mysqli, $query);

/*
* set 'pending' test mode payment to 'incoming' state
*/
$query = "update bbfm set receive_unit='test_mode_receive_unit'";
$query .= " where global_status = 'pending'";
$query .= " and receive_unit is NULL";
$query .= " and mode='test'";
$q = mysqli_query($mysqli, $query);


/****************************
 * notify merchant
 */
$query = "select bbfm.* ";
$query .= ", bbfm_merchant.merchant_id";
$query .= ", bbfm_merchant.secret_key";
$query .= ", bbfm_partner.partner";
$query .= ", bbfm_partner.partner_key";
$query .= " from bbfm";
$query .= " left join bbfm_merchant on bbfm_merchant.id = bbfm.ref_merchant";
$query .= " left join bbfm_partner on bbfm_partner.id = bbfm.ref_partner";

// final notification
$query .= " where ( ( global_status = 'completed' or global_status = 'error' ) and  ( url_notified is NULL or email_notified is NULL ) ) ";
$query .= " or ";
// receiving notification
$query .= " ( receiving_url_notified is NULL and receive_unit is not NULL )";
$query .= " or ";
// intermediate notification
$query .= " ( global_status = 'sent' and ( sent_url_notified is NULL or sent_email_notified is NULL ) )";
//         echo "\nquery : $query";
$q = mysqli_query($mysqli, $query);
//         echo "\nmysqli_num_rows: " . mysqli_num_rows( $q );
if( ! $q ){
	cron_return_error( "\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ), true );
	exit;
}

while( $row = $q->fetch_array(MYSQLI_ASSOC) ){
	/*
	* mode test
	*/
	if( $row[ 'mode' ] == 'test' ){
		$row = set_to_test_mode( $row );
	}

	/*
	* receiving notification
	*/
	if( ! $row[ 'receiving_url_notified' ] and $row[ 'receive_unit' ] and $row[ 'url_notif' ] ){

		$url_notify = url_notify( $row );
		// register result in database
		$query = "update bbfm set receiving_url_notified = '" . $url_notify[ 'result' ] . "'";
		$query .= " where id = '" . $row[ 'id' ] . "'";
//         error_log("\n" . 'query: ' . $query);
		mysqli_query($mysqli, $query);

	}

	/*
	* intermediate notification
	*/

	if( $row[ 'global_status' ] == 'sent' ){

		/*
		* email notification
		*/
		if( ! $row[ 'sent_email_notified' ] and $row[ 'email_notif' ] ){
			$email_notify = email_notify( $row );
			// register result in database
			$query = "update bbfm set sent_email_notified = '" . $email_notify . "'";
			$query .= " where id = '" . $row[ 'id' ] . "'";
			mysqli_query($mysqli, $query);
		}

		/*
		* url notification
		*/
		if( ! $row[ 'sent_url_notified' ] and $row[ 'url_notif' ] ){
			$url_notify = url_notify( $row );

			/*
			* email notify error
			*/
			if( $url_notify[ 'result' ] == 'nok' ){
				if ( $row[ 'email_notif' ] ){
					// email notif url notification error
					email_notif_url_notification_error( $row, $url_notify );
				}
			}
			// register result in database
			$query = "update bbfm set sent_url_notified = '" . $url_notify[ 'result' ] . "'";
			$query .= " where id = '" . $row[ 'id' ] . "'";
	//         error_log("\n" . 'query: ' . $query);
			mysqli_query($mysqli, $query);
		}
	}

	/*
	* final notification
	*/

	if( $row[ 'global_status' ] == 'completed' || $row[ 'global_status' ] == 'error' ){

		/*
		* email notification
		*/
		if( ! $row[ 'email_notified' ] and $row[ 'email_notif' ] ){
			$email_notify = email_notify( $row );
			// register result in database
			$query = "update bbfm set email_notified = '" . $email_notify . "'";
			$query .= " where id = '" . $row[ 'id' ] . "'";
			$email_notify = mysqli_query($mysqli, $query);
		}

		/*
		* url notification
		*/
		if( ! $row[ 'url_notified' ] and $row[ 'url_notif' ] ){
			$url_notify = url_notify( $row );

			/*
			* email notify error
			*/
			if( $url_notify[ 'result' ] == 'nok' ){
				if ( $row[ 'email_notif' ] ){
					// email notif url notification error
					email_notif_url_notification_error( $row, $url_notify );
				}
			}
			// register result in database
			$query = "update bbfm set url_notified = '" . $url_notify[ 'result' ] . "'";
			$query .= " where id = '" . $row[ 'id' ] . "'";
	//         error_log("\n" . 'query: ' . $query);
			$email_notify = mysqli_query($mysqli, $query);
		}
	}
}

$time_out = microtime(true);
$exec_time = round( $time_out - $time_in, 2 );
echo "\ndone in " . $exec_time . " sec.\n";

/****************************
 * functions
 */

function email_notif_url_notification_error( $row, $url_notify ){
	$MailSubject ='*** Error on your Obyte payment url notification for your sale ' . $row[ 'merchant_order_UID' ] . ' ***';
	$MailBody = "An error was encountered while trying to notify your Obyte payment to your 'merchant_return_url'.

mode: $row[mode]
notification URL: $url_notify[CURLOPT_URL]
method: $row[get_or_post_notif]";
	if( isset( $url_notify[ 'error_message' ] ) ){
		$MailBody .= "
error message: $url_notify[error_message]";
	}
	$MailBody .= "
sha256_digest: " . build_checkhash( $row );

	$ToMail = $row[ 'email_notif' ];
	my_sendmail( $MailBody, $MailSubject, $ToMail );
}

function email_notify( $row ){
	global $_currencies;
	/*
	* build mail
	*/

	/*
	* error
	*/
	if( $row[ 'global_status' ] == 'error' ){
		$mail_notif_result = 'nok';
		$MailSubject ='*** Error on your Obyte payment for your sale ' . $row[ 'merchant_order_UID' ] . ' ***';
		$MailBody = "An error was encountered while processing your Obyte payment.
error_msg: " . $row[ 'error_msg' ] ."
";

	/*
	* completed
	*/
	}
	else if( $row[ 'global_status' ] == 'completed' ){
		$mail_notif_result = 'ok';
		$MailSubject ='[confirmed] Obyte payment sent for your sale ' . $row[ 'merchant_order_UID' ];
		$MailBody = "Hello,
The payment for the above mentioned order is now confirmed by the network.";

	/*
	* sent
	*/
	}
	else if( $row[ 'global_status' ] == 'sent' ){
		$mail_notif_result = 'unconfirmed';
		$MailSubject ='[unconfirmed] Obyte payment sent for your sale ' . $row[ 'merchant_order_UID' ];
		$MailBody = "Hello,
You just received from us a Obyte payment for the above mentioned order! Confirmation is still required from the network.";

	}
	else{
		cron_return_error('unknown global_status (' . $row[ 'global_status' ] . ') in email_notify fonction', true);
		return 'nok';
	}

	/*
	* common mail body
	*/
	$MailBody .= "

Find all the details below :
***
result: " . $mail_notif_result ."
mode: " . $row[ 'mode' ] ."
merchant_id: " . $row[ 'merchant_id' ] ."
order_UID: " . $row[ 'merchant_order_UID' ] ."
attach: " . $row[ 'attach' ] ."
byteball_merchant_address: " . $row[ 'address_merchant' ] ."
currency: " . $row[ 'currency' ];

		if ( $row[ 'currency' ] != 'B' ){
			$MailBody .= "
amount_asked_in_currency: " . $row[ 'amount_asked_in_currency' ] / pow( 10, $_currencies[ $row[ 'currency' ] ][ 'decimal' ] ) . " (in " . $row[ 'currency' ] . ")
currency_B_rate: " . $row[ 'currency_BB_rate' ];
		}

		$MailBody .= "
amount_asked_in_B: " . $row[ 'amount_BB_asked' ] ." (in bytes)
receive_unit: " . $row[ 'receive_unit' ] ."
received_amount: " . $row[ 'received_amount' ] ." (in bytes)
fee: " . $row[ 'fee_bbfm' ] ."
amount_sent: " . $row[ 'sent_amount' ] . " (in bytes)
unit: " . $row[ 'sent_unit' ] ."
sha256_digest: " . build_checkhash( $row ) ."
***
Best Regards.
The Obyte For Merchants Team
obyte-for-merchants.com
		";

	$ToMail = $row[ 'email_notif' ];

	/*
	* send mail
	*/

	if( my_sendmail( $MailBody, $MailSubject, $ToMail ) ){
		return 'ok';
	}
	else{
		cron_return_error("Failed on sending notif mail to '$ToMail'", true);
		return 'nok';
	}
}

function url_notify( $row ){
	global $_currencies;

	/*
	* set $is_woocommerce
	*/
	if( $row[ 'merchant_id' ] == 'woocommerce' ){
		$is_woocommerce = true;
	}
	else{
		$is_woocommerce = false;
	}

	/*
	* set $is_bballs
	*/
	if( $row[ 'merchant_id' ] == 'binaryballs' ){
		$is_bballs = true;
	}
	else{
		$is_bballs = false;
	}

	/*
	* build data array
	*/
	$data = array(
		'mode'  => $row[ 'mode' ],
		'order_UID'  => $row[ 'merchant_order_UID' ],
		'attach'  => $row[ 'attach' ],
		'byteball_merchant_address'  => $row[ 'address_merchant' ],
		'currency'  => $row[ 'currency' ],
		'sha256_digest' => build_checkhash( $row ),
	);

	if ( $row[ 'currency' ] != 'B' ){
		$data[ 'amount_asked_in_currency' ] = $row[ 'amount_asked_in_currency' ] / pow( 10, $_currencies[ $row[ 'currency' ] ][ 'decimal' ] );
		$data[ 'currency_B_rate' ] = $row[ 'currency_BB_rate' ];
	}
	$data[ 'amount_asked_in_B' ] = $row[ 'amount_BB_asked' ];

//     if ( $row[ 'receive_unit' ] ){
		$data[ 'receive_unit' ] = $row[ 'receive_unit' ];
//     }
	$data[ 'received_amount' ] = $row[ 'received_amount' ];

//     if ( $row[ 'sent_unit' ] ){
		$data[ 'fee' ] = $row[ 'fee_bbfm' ];
		$data[ 'amount_sent' ] = $row[ 'sent_amount' ];
		$data[ 'unit' ] = $row[ 'sent_unit' ];
//     }

	// add data for woocommerce and bballs
	if( $is_woocommerce || $is_bballs){
		$data[ 'secret_key' ] = $row[ 'secret_key' ];
		$data[ 'receive_unit_date' ] = $row[ 'receive_unit_date' ];
	}

	// add allowed_notif_IPs for woocommerce (to be completed with new server IP before any server migration)
	if( $is_woocommerce ){
		$data[ 'allowed_notif_IPs' ] = array(
			getenv('ALLOWED_NOTIF_IPS'),// our IP
		);
	}

	if( $row[ 'global_status' ] == 'sent' ){
		$data[ 'result' ] = 'unconfirmed';
	}
	else if( $row[ 'global_status' ] == 'completed' ){
		$data[ 'result' ] = 'ok';
	}
	else if( $row[ 'global_status' ] == 'error' ){
		$data[ 'result' ] = 'nok';
		$data[ 'error_msg' ] = $row[ 'error_msg' ];
//     }else if( ! $row[ 'receiving_url_notified' ] and $row[ 'receive_unit' ] ){
	}
	else if( $row[ 'receive_unit' ] ){
		// woobytes and bballs have a special notif name, for historical reasons...
		if( $is_woocommerce || $is_bballs){
			$data[ 'result' ] = 'receiving';
		}
		else{
			$data[ 'result' ] = 'incoming';
		}

	}

	if( $is_bballs ){
		$CURLOPT_USERPWD = null;
	}
	else{
		$CURLOPT_USERPWD = null;
	}

	$process_curl_request = process_curl_request( $row[ 'url_notif' ], $data, $row[ 'get_or_post_notif' ], $CURLOPT_USERPWD );
	return $process_curl_request;
}

function set_to_test_mode( $row ){
	$row[ 'receive_unit' ] = 'test_mode_receive_unit';
	$row[ 'received_amount' ] = $row[ 'amount_BB_asked' ];
	$row[ 'sent_unit' ] = 'test_mode_sent_unit';
	$row[ 'sent_amount' ] = $row[ 'amount_BB_asked' ] - $row[ 'fee_bbfm' ];
	return $row;
}

function listtransactions(){
	$client = new JsonRpcClient('http://127.0.0.1:6332');
	$client->query(1, 'listtransactions', []);
	$Object = jsonrpc_call( $client );
    // echo print_r( $Object, true );
	return $Object;
}

function cron_return_error( $msg, $notif_mail = false ){
	echo "\n" . date("Y-m-d H:i:s") . " : BBFM : " .$msg;
	if( $notif_mail ){
		$MailBody = $msg;
		$MailSubject = "BBFM cron error";
		$ToMail = getenv('ADMIN_EMAIL');
		if ($ToMail) {
			my_sendmail( $MailBody, $MailSubject, $ToMail );
		}
	}
}

function build_checkhash( $row ){
	if( ! $row[ 'merchant_id' ] ) return '';
	$hashed_string = $row[ 'secret_key' ] . $row[ 'merchant_order_UID' ] . $row[ 'url_notif' ] . $row[ 'email_notif' ];
	$hash = hash ( 'sha256' , $hashed_string );
	return $hash;
}

function process_curl_request( $CURLOPT_URL, $data, $mode = 'post', $CURLOPT_USERPWD = null ){
	/*
	* prepare GET notif
	*/
	if( $mode == 'get' ){
		$CURLOPT_URL = $CURLOPT_URL . '?' . http_build_query( $data );
		$setopt_array = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_HEADER => false,
			CURLOPT_NOBODY => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_URL => $CURLOPT_URL . '?' . http_build_query( $data ),
			CURLOPT_USERAGENT => 'Obyte for Merchants',
		);

	/*
	* prepare POST notif
	*/
	}
	else{
		$setopt_array = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HEADER => false,
			// CURLOPT_NOBODY => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_URL => $CURLOPT_URL,
			CURLOPT_USERAGENT => 'Obyte for Merchants',
			// CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => http_build_query( $data ),
			// CURLOPT_HTTPHEADER => array('Transfer-Encoding: gzip'),
			// CURLOPT_VERBOSE => true,
			// CURLOPT_SSLVERSION => 5,
			// CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		);
	}
	// add optional CURLOPT_USERPWD
	if( $CURLOPT_USERPWD ){
		$setopt_array[ CURLOPT_USERPWD ] = $CURLOPT_USERPWD;
	}

	/*
	* execute curl request
	*/
	echo "\nmake $mode request to $CURLOPT_URL : " . print_r( $data, true );
	$curl = curl_init();
	curl_setopt_array($curl, $setopt_array );
	$response = curl_exec($curl);
	if( curl_error($curl) ){
		cron_return_error( 'Curl Error for notif bbfm on ' . $CURLOPT_URL . ' : ' . curl_error($curl) . ' - Code: ' . curl_errno($curl), true );
		return array(
			'result' => 'nok',
			'error_message' => curl_error($curl),
			'body' => $response,
		);
	}
	else{
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if( $httpcode == '200' ){
			return array(
				'result' => 'ok',
				'body' => $response,
			);
		}
		else{
			cron_return_error( 'Notif bbfm on ' . $CURLOPT_URL . ' returned non 200 HTTP code : ' . $httpcode . ' - data : ' . print_r($data, true), true );
			return array(
				'result' => 'nok',
				'error_message' => ' returned non 200 HTTP code : ' . $httpcode,
				'CURLOPT_URL' => $CURLOPT_URL,// used for failure email notification
				'body' => $response,
			);
		}

	}

}

/*
transaction Array
(
	[0] => stdClass Object
		(
			[action] => sent
			[amount] => 400
			[addressTo] => DMMLLOAG7CM7KFKGF5VUDLYUM24E6Z5C
			[confirmations] => 0
			[unit] => k+d8NTK08Yr3vTe5peGuxE1x8Q9wowWdHkwOtDHBnF4=
			[fee] => 588
			[time] => 1499276263
			[level] => 696397
			[mci] => 693686
		)
	[1] => stdClass Object
		(
			[action] => received
			[amount] => 1000
			[my_address] => 4ZRMFHJ3MF4HYB5PISRCAXE3TYYCCWHU
			[arrPayerAddresses] => Array
				(
					[0] => AYLQNWRRXD77VEF2MZJLRA6L2LUPWSRD
				)
			[confirmations] => 1
			[unit] => nWfYJU/G6anGmrSa+X5RKemY4ILE2Za5DxxrejjsINU=
			[fee] => 651
			[time] => 1499273592
			[level] => 696308
			[mci] => 693602
		)
	[2] => stdClass Object
		(
			[action] => received
			[amount] => 1
			[my_address] => 5XXQYF7MXXVES7JUBUXGDRGTRV6BCXZQ
			[arrPayerAddresses] => Array
				(
					[0] => DMMLLOAG7CM7KFKGF5VUDLYUM24E6Z5C
				)
			[confirmations] => 1
			[unit] => p258Uktf7KiW1/VT+FCbZJwj0YAAjqSiLscAMuG7Rqs=
			[fee] => 541
			[time] => 1499268366
			[level] => 696081
			[mci] => 693377
		)
)
*/
