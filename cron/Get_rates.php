<?php

//This script fecthes FIATs, BTC, and GBYTE values from majors marckets sites to provide near real time
//accurate FIAT/GBYTE exchange rate. Fills the "bbfm_currency_rate" table.
//Should be ran each 5' from a cron JOB.
//In case of an error sends an email to ENV.ADMIN_EMAIL.

include_once __DIR__ .'/../bbfm_xfiles/bbfm_conf.php';
include_once __DIR__ .'/../bbfm_xfiles/mysqli_connect.php';

$percentmax = (int)getenv('PERCENTAGE_MAX'); //max change allowed (in percent) in between two consrcutives values for GBYTE
$percentmax = $percentmax ? $percentmax : 30;

$rate_url="https://api.coinmarketcap.com/v1/ticker/obyte/";
$json_array= json_decode(make_443_get ($rate_url), true);

$query = "select * from bbfm_currency_rate where code='BYTE' limit 1";
$q = mysqli_query($mysqli, $query);

$old_value = null;
if ( $q && $q->num_rows ) {
	$rep = mysqli_fetch_assoc($q);
	$old_value = $rep[ 'BTC_rate' ];
}

if(empty($json_array["error"])){
	$GBYTE_BTC_value = $json_array['0']['price_btc'];

	$percentChange = $old_value ? ($GBYTE_BTC_value - $old_value) / $old_value * 100 : 0;

	//echo $percentChange;

	if(abs($percentChange) < $percentmax){
		$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $GBYTE_BTC_value, 'BYTE', $GBYTE_BTC_value);
		$q = mysqli_query($mysqli, $query);
		if ( !$q ) {
			echo "Problem here...";
			exit;
		}
	}
	else {
		$subject = 'ERROR! BBFM BYTES/BTC exchange rate bad!';
		$message = 'Got new BTC value='.$GBYTE_BTC_value.' old BTC_value='.$old_value.' This is more than my '.$percentmax.'% limit!';
		$headers = 'From: noreply@obyte-for-merchants.com' . "\r\n" .
		'Reply-To: noreply@obyte-for-merchants.com' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();

		$to      = getenv('ADMIN_EMAIL');
		if ($to) {
			mail($to, $subject, $message, $headers);
		}
	}
}

//fetch various FIAT rates against BTC
$rate_url="https://blockchain.info/fr/ticker";
$json_array= json_decode(make_443_get ($rate_url), true);

if(empty($json_array["error"])){
	$USD_BTC_value=1/$json_array['USD']['15m'];
	$AUD_BTC_value=1/$json_array['AUD']['15m'];
	$BRL_BTC_value=1/$json_array['BRL']['15m'];
	$CAD_BTC_value=1/$json_array['CAD']['15m'];
	$CHF_BTC_value=1/$json_array['CHF']['15m'];
	$CLP_BTC_value=1/$json_array['CLP']['15m'];
	$CNY_BTC_value=1/$json_array['CNY']['15m'];
	$DKK_BTC_value=1/$json_array['DKK']['15m'];
	$EUR_BTC_value=1/$json_array['EUR']['15m'];
	$GBP_BTC_value=1/$json_array['GBP']['15m'];
	$HKD_BTC_value=1/$json_array['HKD']['15m'];
	$INR_BTC_value=1/$json_array['INR']['15m'];
	$ISK_BTC_value=1/$json_array['ISK']['15m'];
	$JPY_BTC_value=1/$json_array['JPY']['15m'];
	$KRW_BTC_value=1/$json_array['KRW']['15m'];
	$NZD_BTC_value=1/$json_array['NZD']['15m'];
	$PLN_BTC_value=1/$json_array['PLN']['15m'];
	$RUB_BTC_value=1/$json_array['RUB']['15m'];
	$SEK_BTC_value=1/$json_array['SEK']['15m'];
	$SGD_BTC_value=1/$json_array['SGD']['15m'];
	$THB_BTC_value=1/$json_array['THB']['15m'];
	$TWD_BTC_value=1/$json_array['TWD']['15m'];

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $USD_BTC_value, 'USD', $USD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $AUD_BTC_value, 'AUD', $AUD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $BRL_BTC_value, 'BRL', $BRL_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $CAD_BTC_value, 'CAD', $CAD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $CHF_BTC_value, 'CHF', $CHF_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $CLP_BTC_value, 'CLP', $CLP_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $CNY_BTC_value, 'CNY', $CNY_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $DKK_BTC_value, 'DKK', $DKK_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $EUR_BTC_value, 'EUR', $EUR_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $GBP_BTC_value, 'GBP', $GBP_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $HKD_BTC_value, 'HKD', $HKD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $INR_BTC_value, 'INR', $INR_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $ISK_BTC_value, 'ISK', $ISK_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $JPY_BTC_value, 'JPY', $JPY_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $KRW_BTC_value, 'KRW', $KRW_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $NZD_BTC_value, 'NZD', $NZD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $PLN_BTC_value, 'PLN', $PLN_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $RUB_BTC_value, 'RUB', $RUB_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $SEK_BTC_value, 'SEK', $SEK_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $SGD_BTC_value, 'SGD', $SGD_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $THB_BTC_value, 'THB', $THB_BTC_value);
	$q = mysqli_query($mysqli, $query);

	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $TWD_BTC_value, 'TWD', $TWD_BTC_value);
	$q = mysqli_query($mysqli, $query);
}
else {
	$subject = 'Error fetching currencies from https://blockchain.info/fr/ticker';
	$message = 'Curl error_code is:'. $json_array["error_code"];
	$headers = 'From: noreply@obyte-for-merchants.com' . "\r\n" .
		'Reply-To: noreply@obyte-for-merchants.com' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();
	$to      = getenv('ADMIN_EMAIL');
	if ($to) {
		mail($to, $subject, $message, $headers);
	}
}

// except for CZK
$rate_url="https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=CZK";
$json_array= json_decode(make_443_get ($rate_url), true);
if(empty($json_array["error"])){
	//var_dump($json_array);
	$CZK_BTC_value=1/$json_array[0]['price_czk'];
	$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%f, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %f, last_update=now();', $CZK_BTC_value, 'CZK', $CZK_BTC_value);
	$q = mysqli_query($mysqli, $query);
	//echo $CZK_BTC_value;
}
else {
	$subject = 'Error fetching currencies from https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=CZK';
	$message = 'Curl error_code is:'. $json_array["error_code"];
	$headers = 'From: noreply@obyte-for-merchants.com' . "\r\n" .
		'Reply-To: noreply@obyte-for-merchants.com' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();

	$to      = getenv('ADMIN_EMAIL');
	if ($to) {
		mail($to, $subject, $message, $headers);
	}
}

function make_443_get ($url) {
	$url = $url;
	$timeout = 10;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_PORT, 443);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_FAILONERROR,true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	if($output = curl_exec($ch)){
		return $output;
	}
	else {
		//echo 'errore here:' . curl_error($ch);
		$buff_code = array('error' => 1, 'error_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		return json_encode($buff_code); //426
	}
	// close curl resource to free up system resources
}
