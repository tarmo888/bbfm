<?php
use Datto\JsonRpc\Http\Client as JsonRpcClient;
use Datto\JsonRpc\Response as JsonRpcResponse;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function get_input( $input_var_name, $is_mandatory = false , $regex_pattern = false, $check_fonction = false, $default = '' ){

    if( isset( $_GET[ $input_var_name ] ) ){
        $is_set = true;
        $result = $_GET[ $input_var_name ];
    }else if( isset( $_POST[ $input_var_name ] ) ){
        $is_set = true;
        $result = $_POST[ $input_var_name ];
    }else{
        $is_set = false;
        $result = false;
    }
    // handle case of empty inputs
    if( $is_set and strlen( $result ) == 0 ){
        $is_set = false;
        $result = false;
    }

    /*
     * special cases
     */
    if( $input_var_name == 'mode_notif' and $is_set ){
        $result = strtolower( $result );
    }


    /*
     * mandatory check
     */
    if( $is_mandatory ){
        if( ! $is_set ){
            return_error( $input_var_name . ' not set' );
        }
    }

    /*
     * default value
     */
     if( ! $result and strlen( $default ) > 0 ){
        $result = $default;
        return $result;
    }

    /*
     * regex check
     */
    if( $regex_pattern and $is_set ){
        if( ! preg_match( $regex_pattern, $result ) ){
            return_error( $input_var_name . ' invalid format' );
        }
    }

    /*
     * custom validation function check
     */
    if( $check_fonction and $is_set ){
        if( ! $check_fonction( $result ) ){
            return_error( $result. ' ' . 'not valid ' . $input_var_name );
        }
    }
    return $result;
}

function check_email( $email ){
    // error_log("email : " . $email );
    // return filter_var($email, FILTER_VALIDATE_EMAIL);
    /*
     * copied from wordpress is_email() function
     */
    // Test for the minimum length the email can be
    if ( strlen( $email ) < 6 ) return false;
    // Test for an @ character after the first position
    if ( strpos( $email, '@', 1 ) === false ) return false;
    // Split out the local and domain parts
	list( $local, $domain ) = explode( '@', $email, 2 );
	// error_log("local : " . $local );
	// LOCAL PART
	// Test for invalid characters
	if ( !preg_match( '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]+$/', $local ) ) {
		return false;
	}
	// DOMAIN PART
	// Test for sequences of periods
	if ( preg_match( '/\.{2,}/', $domain ) ) {
		return false;
	}
	// Test for leading and trailing periods and whitespace
	if ( trim( $domain, " \t\n\r\0\x0B." ) !== $domain ) {
		return false;
	}
	// Split the domain into subs
	$subs = explode( '.', $domain );
	// Assume the domain will have at least two subs
	if ( 2 > count( $subs ) ) {
		return false;
	}
	// Loop through each sub
	foreach ( $subs as $sub ) {
		// Test for leading and trailing hyphens and whitespace
		if ( trim( $sub, " \t\n\r\0\x0B-" ) !== $sub ) {
			return false;
		}
		// Test for invalid characters
		if ( !preg_match('/^[a-z0-9-]+$/i', $sub ) ) {
			return false;
		}
	}
	// Congratulations your email made it!
	/** This filter is documented in wp-includes/formatting.php */
	return true;
}

function check_url( $url ){
    return filter_var($url, FILTER_VALIDATE_URL);
}

function check_currency( $currency ){
    global $_currencies;
    if( array_key_exists ( $currency , $_currencies ) ){
        return true;
    }else{
        return false;
    }
}

function check_BB_address( $address ){
    if( preg_match( "@^[0-9A-Z]{32}$@", $address ) ){
        return true;
    }else{
        return false;
    }
}

function check_cashback_address( $address ){
    if( check_BB_address( $address ) ){
        return true;
    }else{
        if( check_email( $address ) ){
            return true;
        }else{
            return false;
        }
    }
}

function return_error( $msg ){
    $return = array(
        'result' => 'nok',
        'error_msg' => $msg,
    );
    die( json_encode($return) );
}

function BB_conversion( $amount, $currency ){
    $BB_rates = get_BB_rates();
    if( ! isset( $BB_rates[ $currency ] ) ){
        return_error( "\nerror in fetching $currency change rate.");
    }
    $conversion_rate = $BB_rates[ $currency ][ 'rate' ];
    $BB_amount = round( $amount * $conversion_rate );
    return array(
        'BB_amount' => $BB_amount,
        'conversion_rate' => $conversion_rate,
        'age' => $BB_rates[ $currency ][ 'age' ]
    );
}

function get_BB_rates(){
    global $mysqli;
    // factor from X to BB
    $BB_rates = array(
        'B' => array(
            'rate' => 1,
            'age' => 0,
        ),
        'KB' => array(
            'rate' => 1000,
            'age' => 0,
        ),
        'MB' => array(
            'rate' => 1000000,
            'age' => 0,
        ),
        'GB' => array(
            'rate' => 1000000000,
            'age' => 0,
        ),
    );

    /*
     * get BTC_rates from DB
     */
    // factor from X to BTC
    $BTC_rates = array();
    $q = mysqli_query($mysqli, "select *, ( unix_timestamp( now() ) - unix_timestamp( last_update ) ) as age from bbfm_currency_rate where 1");
    while ( $row = $q->fetch_array(MYSQLI_ASSOC) ){
        $BTC_rates[ $row[ 'code' ] ] = array(
            'rate' => $row[ 'BTC_rate' ],
            'age' => $row[ 'age' ],
        );
    }
    // first we set BTC_B rate (needed for other B rates)
    $BB_rates[ 'BTC' ] = array(
                            'rate' => 1 / $BTC_rates[ 'BYTE' ][ 'rate' ] * 1000000000,
                            'age' => $BTC_rates[ 'BYTE' ][ 'age' ],
                        );

    // then we set other rates
    foreach( $BTC_rates as $code => $data ){
        if( $code == 'BYTE' ) continue;

        $BB_rates[ $code ] = array(
                                'rate' => $BTC_rates[ $code ][ 'rate' ] * $BB_rates[ 'BTC' ][ 'rate' ],
                                'age' => $BTC_rates[ $code ][ 'age' ],
                            );
    }
//     error_log( "\nBB_rates : " . print_r( $BB_rates, true ) );
    return $BB_rates;
}

function fee_bbfm( $amount ){
    $fee = 1000 + round( $amount * 0.009 ) ;
    return $fee;
}

function getnewaddressFromWallet(){
	$client = new JsonRpcClient('http://127.0.0.1:6332');
	$client->query(1, 'getnewaddress', []);
    return jsonrpc_call( $client );
}

function jsonrpc_call( $client ){
	try {
		return jsonrpc_success($client->send());
	}
	catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}

function jsonrpc_success($responses) {
	/**
	 * @var JsonRpcResponse[] $responses
	 */
	foreach ($responses as $response) {
		$id = $response->getId();
		if ($response->isError()) {
			$error = $response->getError();
			$code = $error->getCode();
			$message = $error->getMessage();
			$data = $error->getData();
			echo " * error (as expected):\n",
			"    * code: ", json_encode($code), "\n",
			"    * message: ", json_encode($message), "\n",
			"    * data (if any): ", json_encode($data), "\n";
		}
		else {
			return $response->getResult();
		}
	}
}


function my_sendmail( $MailBody, $MailSubject, $ToMail ){
	echo "\nmy_sendmail( $MailSubject, $ToMail ) \n";

	// Instantiation and passing `true` enables exceptions
	$mail = new PHPMailer(true);

	try {
		//Server settings
		//$mail->SMTPDebug = 2;
		if (getenv('MAILER_MODE') === 'smtp') {
			$mail->isSMTP();                                   // Set mailer to use SMTP
			$mail->Host         = getenv('MAILER_HOST');       // Specify main and backup SMTP servers
			if (getenv('MAILER_USERNAME') && getenv('MAILER_PASSWORD')) {
				$mail->SMTPAuth = true;                        // Enable SMTP authentication
				$mail->Username = getenv('MAILER_USERNAME');   // SMTP username
				$mail->Password = getenv('MAILER_PASSWORD');   // SMTP password
			}
			$mail->SMTPSecure   = getenv('MAILER_ENCRYPTION'); // Enable TLS encryption, `ssl` also accepted
			$mail->Port         = getenv('MAILER_PORT');       // TCP port to connect to
		}

		//Recipients
		$mail->setFrom('bbfm@obyte.org', 'Obyte for Merchants');
		$mail->addAddress($ToMail);
		$mail->addReplyTo('tarmo888@gmail.com', 'Obyte for Merchants');

		// Content
		$mail->isHTML(false);
		$mail->Subject = $MailSubject;
		$mail->Body    = $MailBody;
		//$mail->AltBody = '';

		if (getenv('MAILER_MODE')) {
			$mail->send();
			return true;
		}
	}
	catch (PHPMailerException $e) {
		echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	}
}