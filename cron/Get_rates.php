<?php

//This script fecthes FIATs, BTC, and GBYTE values from majors marckets sites to provide near real time
//accurate FIAT/GBYTE exchange rate. Fills the "bbfm_currency_rate" table.
//Should be ran each 5' from a cron JOB.
//In case of an error sends an email to ENV.ADMIN_EMAIL.

include_once __DIR__ .'/../bbfm_xfiles/bbfm_conf.php';
include_once __DIR__ . "/../bbfm_xfiles/bbfm_function.php";
include_once __DIR__ .'/../bbfm_xfiles/mysqli_connect.php';

$percentmax = (int)getenv('PERCENTAGE_MAX'); //max change allowed (in percent) in between two consrcutives values for GBYTE
$percentmax = $percentmax ? $percentmax : 30;

$rate_url="https://api.coinpaprika.com/v1/tickers/gbyte-obyte?quotes=BTC";
$json_array= json_decode(make_443_get ($rate_url), true);

$query = "select * from bbfm_currency_rate where code='BYTE' limit 1";
$q = mysqli_query($mysqli, $query);

$old_value = null;
if ( $q && $q->num_rows ) {
	$rep = mysqli_fetch_assoc($q);
	$old_value = $rep[ 'BTC_rate' ];
}

if(empty($json_array["error"]) && !empty($json_array['quotes']['BTC']['price'])){
	$GBYTE_BTC_value = $json_array['quotes']['BTC']['price'];
	$percentChange = $old_value ? ($GBYTE_BTC_value - $old_value) / $old_value * 100 : 0;
	//echo $percentChange;

	if(abs($percentChange) < $percentmax){
		$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%s, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %s, last_update=now();', $GBYTE_BTC_value, 'BYTE', $GBYTE_BTC_value);
		$q = mysqli_query($mysqli, $query);
		if ( !$q ) {
			echo "Problem here...";
			exit;
		}
	}
	else {
		$MailSubject = 'GBYTE/BTC exchange rate error!';
		$MailBody = 'Got new BTC value='.$GBYTE_BTC_value.' old BTC_value='.$old_value.' This is more than my '.$percentmax.'% limit!';
		$ToMail  = getenv('ADMIN_EMAIL');

		if ($ToMail) {
			my_sendmail( $MailBody, $MailSubject, $ToMail );
		}
	}
}
else {
	$MailSubject = 'Error fetching currencies for GBYTE from CoinPaprika';
	$MailBody = $rate_url ."\n";
	$MailBody .= 'Curl error_code is:'. (empty($json_array["error_code"]) ? 'X' : $json_array["error_code"]);
	$ToMail  = getenv('ADMIN_EMAIL');

	if ($ToMail) {
		my_sendmail( $MailBody, $MailSubject, $ToMail );
	}
}

//fetch various currency rates against BTC
$rate_url="https://api.coinpaprika.com/v1/tickers/btc-bitcoin?quotes=ETH,USD,EUR,PLN,KRW,GBP,CAD,JPY,RUB,TRY,NZD,AUD,CHF,UAH,HKD,SGD,NGN,PHP,MXN,BRL,THB,CLP,CNY,CZK,DKK,HUF,IDR,ILS,INR,MYR,NOK,PKR,SEK,TWD,ZAR,VND,BOB,COP,PEN,ARS,ISK";
$json_array= json_decode(make_443_get ($rate_url), true);

if(empty($json_array["error"]) && !empty($json_array['quotes'])){
	foreach ($json_array['quotes'] as $ticker => $quote) {
		if (empty($quote['price'])) continue;

		$BTC_value = 1/$quote['price'];
		$query = sprintf('INSERT INTO bbfm_currency_rate (BTC_rate, last_update, code) VALUES (%s, now(), "%s") ON DUPLICATE KEY UPDATE BTC_rate = %s, last_update=now();', $BTC_value, $ticker, $BTC_value);
		$q = mysqli_query($mysqli, $query);
	}
}
else {
	$MailSubject = 'Error fetching currencies for BTC from CoinPaprika';
	$MailBody = $rate_url ."\n";
	$MailBody .= 'Curl error_code is:'. (empty($json_array["error_code"]) ? 'X' : $json_array["error_code"]);
	$ToMail  = getenv('ADMIN_EMAIL');

	if ($ToMail) {
		my_sendmail( $MailBody, $MailSubject, $ToMail );
	}
}

function make_443_get ($url) {
	$url = $url;
	$timeout = 10;

	sleep(1);
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
		curl_close($ch);
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
