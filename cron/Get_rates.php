<?php

//This script fecthes FIATs, BTC, and GBYTE values from majors marckets sites to provide near real time
//accurate FIAT/GBYTE exchange rate. Fills the "bbfm_currency_rate" table.
//Should be ran each 5' from a cron JOB.
//In case of an error sends an email to $admin_email.


$percentmax=30; //max change allowed (in percent) in between two consrcutives values for GBYTE
$admin_email='xxxx@yyyyyy.com'; //where to send alert emails.

include_once '/var/www/bbfm_xfiles/mysqli_connect.php';

	$rate_url="https://api.coinmarketcap.com/v1/ticker/byteball/";
	$json_array= json_decode(make_443_get ($rate_url), true);

	$query = "select * from bbfm_currency_rate where code='BYTE' limit 1";
		$q = mysqli_query($mysqli, $query);
		if ( ! $q ) {
	     echo mysqli_error( $mysqli );
	      exit;
    	} else {
    		  $rep = mysqli_fetch_assoc($q);
    			$old_value=$rep[ 'BTC_rate' ];
    	}

//$old_value=1;

	if(!defined($json_array["error"])){
		$GBYTE_BTC_value=$json_array['0']['price_btc'];

		$percentChange = ( $GBYTE_BTC_value - $old_value ) / $old_value * 100;

		//echo $percentChange;

		if(abs($percentChange) < $percentmax){

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$GBYTE_BTC_value', last_update=now() where 1 and code='BYTE'";

	$q = mysqli_query($mysqli, $query);
	  if ( ! $q ) {

        echo "Problem here...";
        exit;

    }

		} else {

		     $subject = 'ERROR! BBFM BYTES/BTC exchange rate bad!';
		     $message = 'Got new BTC value='.$GBYTE_BTC_value.' old BTC_value='.$old_value.' This is more than my '.$percentmax.'% limit!';
		     $headers = 'From: noreply@byteball-for-merchants.com' . "\r\n" .
		     'Reply-To: noreply@byteball-for-merchants.com' . "\r\n" .
		     'X-Mailer: PHP/' . phpversion();

		     $to      = $admin_email;
		     mail($to, $subject, $message, $headers);
		}
	}

//fetch various FIAT rates against BTC
	$rate_url="https://blockchain.info/fr/ticker";
	$json_array= json_decode(make_443_get ($rate_url), true);
	if(!defined($json_array["error"])){
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

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$USD_BTC_value', last_update=now() where 1 and code='USD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$AUD_BTC_value', last_update=now() where 1 and code='AUD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$BRL_BTC_value', last_update=now() where 1 and code='BRL'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$CAD_BTC_value', last_update=now() where 1 and code='CAD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$CHF_BTC_value', last_update=now() where 1 and code='CHF'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$CLP_BTC_value', last_update=now() where 1 and code='CLP'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$CNY_BTC_value', last_update=now() where 1 and code='CNY'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$DKK_BTC_value', last_update=now() where 1 and code='DKK'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$EUR_BTC_value', last_update=now() where 1 and code='EUR'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$GBP_BTC_value', last_update=now() where 1 and code='GBP'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$HKD_BTC_value', last_update=now() where 1 and code='HKD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$INR_BTC_value', last_update=now() where 1 and code='INR'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$ISK_BTC_value', last_update=now() where 1 and code='ISK'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$JPY_BTC_value', last_update=now() where 1 and code='JPY'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$KRW_BTC_value', last_update=now() where 1 and code='KRW'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$NZD_BTC_value', last_update=now() where 1 and code='NZD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$PLN_BTC_value', last_update=now() where 1 and code='PLN'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$RUB_BTC_value', last_update=now() where 1 and code='RUB'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$SEK_BTC_value', last_update=now() where 1 and code='SEK'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$SGD_BTC_value', last_update=now() where 1 and code='SGD'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$THB_BTC_value', last_update=now() where 1 and code='THB'";
		$q = mysqli_query($mysqli, $query);

		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$TWD_BTC_value', last_update=now() where 1 and code='TWD'";
		$q = mysqli_query($mysqli, $query);
	} else {
				 $subject = 'Error fetching currencies from https://blockchain.info/fr/ticker';
		     $message = 'Curl error_code is:'. $json_array["error_code"];
		     $headers = 'From: noreply@byteball-for-merchants.com' . "\r\n" .
		     'Reply-To: noreply@byteball-for-merchants.com' . "\r\n" .
		     'X-Mailer: PHP/' . phpversion();

		     $to      = $admin_email;
		     mail($to, $subject, $message, $headers);
	}



// except for CZK
	$rate_url="https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=CZK";
	$json_array= json_decode(make_443_get ($rate_url), true);
	if(!defined($json_array["error"])){
		//var_dump($json_array);
		$CZK_BTC_value=1/$json_array[0]['price_czk'];
		$query = "UPDATE bbfm_currency_rate SET BTC_rate='$CZK_BTC_value', last_update=now() where 1 and code='CZK'";
		$q = mysqli_query($mysqli, $query);
		//echo $CZK_BTC_value;
	} else {
				 $subject = 'Error fetching currencies from https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=CZK';
		     $message = 'Curl error_code is:'. $json_array["error_code"];
		     $headers = 'From: noreply@byteball-for-merchants.com' . "\r\n" .
		     'Reply-To: noreply@byteball-for-merchants.com' . "\r\n" .
		     'X-Mailer: PHP/' . phpversion();

		     $to      = $admin_email;
		     mail($to, $subject, $message, $headers);
	}



function make_443_get ($url) {
				$url=$url;
				$timeout = 10;


				// create curl resource
				$ch = curl_init();

				// curl_setopt
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

				} else {

				//echo 'errore here:' . curl_error($ch);

				$buff_code = array('error' => 1, 'error_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE));
				curl_close($ch);
				return json_encode($buff_code); //426

				}

				// close curl resource to free up system resources



}
?>
