<?php
$XFILE_PATH = __DIR__ ."/../bbfm_xfiles/";

include_once $XFILE_PATH . "bbfm_conf.php";
// Mysql connect ( setup global $mysqli )
include_once $XFILE_PATH . 'mysqli_connect.php';

// Check connection
if ( mysqli_connect_errno() ){
	die("Failed to connect to MySQL: " . mysqli_connect_error());
}

$q = mysqli_query($mysqli, 'SELECT DISTINCT url_notif, get_or_post_notif FROM bbfm JOIN bbfm_merchant ON ref_merchant = bbfm_merchant.id WHERE merchant_id = "woocommerce";');
if( ! $q ){
	die("\nMySQL error in query : $query" . "\n" . mysqli_error( $mysqli ));
}

$data = [];
$data[ 'allowed_notif_IPs' ] = array(
	'127.0.0.1', // our new IP
);
while( $row = $q->fetch_array(MYSQLI_ASSOC) ){
	$url_parts = parse_url($row[ 'url_notif' ]);
	if (!$url_parts) continue;
	if (endswith($url_parts['host'], 'localhost')) continue;
	if (endswith($url_parts['host'], 'example.com')) continue;

	notify_curl_request( $row[ 'url_notif' ], $data, $row[ 'get_or_post_notif' ] );
}

function endswith($string, $test) {
	$strlen = strlen($string);
	$testlen = strlen($test);
	if ($testlen > $strlen) return false;
	return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function notify_curl_request( $CURLOPT_URL, $data, $mode = 'post' ){
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

	/*
	* execute curl request
	*/
	$curl = curl_init();
	curl_setopt_array($curl, $setopt_array );
	$response = curl_exec($curl);
	if( curl_error($curl) ){
		echo 'Curl Error for notif bbfm on ' . $CURLOPT_URL . ' : ' . curl_error($curl) . ' - Code: ' . curl_errno($curl) ."\n";
	}
	else{
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if( $httpcode == '200' || $httpcode == '401' ){
			echo 'ok - ' . $CURLOPT_URL ."\n";
		}
		else{
			echo 'Notif bbfm on ' . $CURLOPT_URL . ' returned error code : ' . $httpcode."\n";
		}
	}
}