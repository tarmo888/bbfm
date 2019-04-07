<?php
header('Content-type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
# cross-site
if (array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER)) {
    header('Access-Control-Allow-Headers: '. $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
} else {
    header('Access-Control-Allow-Headers: *');
}
if("OPTIONS" == $_SERVER['REQUEST_METHOD']) {
    exit(0);
}

/*
**** todo ****

- usage limit (?)

*/

include_once __DIR__ . '/../../bbfm_xfiles/bbfm_conf.php';
include_once __DIR__ . '/../../bbfm_xfiles/bbfm_function.php';

$debug_msg = '';
// return_error( "cashback_address : " . $_POST[ 'cashback_address' ]);
// return_error( "_GET : " . print_r( $_GET, true ) );

/****************************
 * get secured inputs
 ****/

$mode = get_input( 'mode', true, '/^(live|test)$/' );
$merchant_order_UID = get_input( 'order_UID', true, '/^[a-zA-Z0-9-]{1,20}$/' );
$url_notif = get_input( 'merchant_return_url', false, false, 'check_url' );
$get_or_post_notif = get_input( 'mode_notif', false, '/^(get|post)$/', false, 'get' );
$currency = get_input( 'currency', false, false, 'check_currency', 'B' );
$email_notif = get_input( 'merchant_email', false, false, 'check_email' );
$amount_asked_in_currency = get_input( 'amount', true, '/^[0-9]+\.?[0-9]*$/' );
$address_merchant = get_input( 'byteball_merchant_address', true, false, 'check_BB_address' );
$merchant_id = get_input( 'merchant_id', false, '/^[a-zA-Z0-9_-]{5,20}$/' );
$attach = get_input( 'attach', false, '/^[a-zA-Z0-9_.@,=?& -]{0,255}$/' );
$qrcode = get_input( 'qrcode', false, '/^[a-zA-Z0-9_.=?&{}:\/-]{0,255}$/' );

/*
 * woocommerce fields
 */
$partner = get_input( 'partner', false, '/^[a-zA-Z0-9_-]{5,100}$/' );
$partner_key = get_input( 'partner_key', false, '/^[a-zA-Z0-9_-]{5,100}$/' );
$partner_cashback_percentage = get_input( 'partner_cashback_percentage', false, '/^[0-9]{0,2}$/', null, '0');
$customer = get_input( 'customer', false, '/^[a-zA-Z0-9_-]{1,30}$/' );
$description = get_input( 'description', false, '/^[a-zA-Z0-9_ -]{1,100}$/' );
$callback_secret = get_input( 'callback_secret', false, '/^[a-z0-9]{40}$/' );
$cashback_address = get_input( 'cashback_address', false, false, 'check_cashback_address' );
$display_powered_by = get_input( 'display_powered_by', false, '/^(0|1|true|false){0,1}$/', null, 0 );
$WC_BBFM_VERSION = get_input( 'WC_BBFM_VERSION', false, '/^[a-zA-Z0-9.]{1,10}$/' );

// because the 0 default value does not work
if( empty( $partner_cashback_percentage ) ) $partner_cashback_percentage=0;

// debug
$debug_msg .= "<br>merchant_order_UID: $merchant_order_UID";
$debug_msg .=  "<br>url_notif: $url_notif";
$debug_msg .=  "<br>get_or_post_notif: $get_or_post_notif";
$debug_msg .=  "<br>currency: $currency";
$debug_msg .=  "<br>email_notif: $email_notif";
$debug_msg .=  "<br>amount_asked_in_currency: $amount_asked_in_currency";
$debug_msg .=  "<br>address_merchant: $address_merchant";

/****
 * end secured inputs
 ****************************/

// Mysql connect
include_once __DIR__ . '/../../bbfm_xfiles/mysqli_connect.php';


/*
* force at least one notif canal
*/
if( ! $url_notif and ! $email_notif ){
    return_error( "You must define at least one notification canal ( 'merchant_return_url' or 'merchant_email'.");
}

/*
* force $merchant_id on live mode if no callback_secret
*/
if( ! $merchant_id and $mode == 'live' and ! $callback_secret ){
    return_error( "merchant_id is mandatory on live mode.");
}

/*
* block test $merchant_id on live mode
*/
if( $merchant_id == 'test_merchant'  and $mode == 'live' ){
    return_error( "test merchant_id cannot be used on live mode.");
}

/*
 * check amount decimal
 */

// with a round more
if( round( $amount_asked_in_currency * pow( 10, $_currencies[ $currency ][ 'decimal' ] ) ) != round( $amount_asked_in_currency * pow( 10, $_currencies[ $currency ][ 'decimal' ] ), 5) ){
    // prod
    return_error( "Number of decimals for " . $currency . " amount cannot be greater than " . $_currencies[ $currency ][ 'decimal' ] );
    // debug
//     return_error( "Number of decimals for " . $currency . " amount cannot be greater than " . $_currencies[ $currency ][ 'decimal' ] . ' (' . round( $amount_asked_in_currency * pow( 10, $_currencies[ $currency ][ 'decimal' ] ) ) . ' <> ' . $amount_asked_in_currency * pow( 10, $_currencies[ $currency ][ 'decimal' ] ) . ')' );
}

 /*
  * replace (float) amount_asked_in_currency by (int) int_amount_asked_in_currency
  */
$int_amount_asked_in_currency = $amount_asked_in_currency * pow( 10, $_currencies[ $currency ][ 'decimal' ] );

/*
 * set $ref_merchant and $is_woocommerce
 */
$ref_merchant = 0;
$is_woocommerce = false;
if( $callback_secret ){ // woocommerce
    $is_woocommerce = true;
    $q = mysqli_query($mysqli, "select id from bbfm_merchant where merchant_id = 'woocommerce' and secret_key = '$callback_secret' ");
    if( mysqli_num_rows( $q ) == 0 ){
        $insert_query = "insert into bbfm_merchant set creation = now(), merchant_id = 'woocommerce', secret_key = '$callback_secret' ";
        $q_insert = mysqli_query($mysqli, $insert_query);
        if( ! $q_insert ){
//             return_error( "\nerror in bbfm insert query : $insert_query" . "\n" . mysqli_error( $mysqli ) );// dev
            return_error( "\nerror returned when registering callback_secret." );// prod
        }
        $ref_merchant = mysqli_insert_id ( $mysqli );
    }else{
        $row = $q->fetch_array(MYSQLI_ASSOC);
        $ref_merchant = $row[ 'id' ];
    }

}else if( $merchant_id ){
    $q = mysqli_query($mysqli, "select id from bbfm_merchant where merchant_id = '$merchant_id' ");
    if( mysqli_num_rows( $q ) == 0 ){
        return_error( "unknown merchant_id" );
    }else{
        $row = $q->fetch_array(MYSQLI_ASSOC);
        $ref_merchant = $row[ 'id' ];
    }
}

/*
 * set $ref_partner
 */
$ref_partner = 0;

if( $partner ){
    $q = mysqli_query($mysqli, "select id from bbfm_partner where partner = '$partner' and partner_key = '$partner_key' ");
    if( mysqli_num_rows( $q ) == 0 ){
        mysqli_query($mysqli, "insert into bbfm_partner set partner = '$partner', partner_key = '$partner_key' ");
        $ref_partner = mysqli_insert_id ( $mysqli );
    }else{
        $row = $q->fetch_array(MYSQLI_ASSOC);
        $ref_partner = $row[ 'id' ];
    }
}

 /*
  * search if is exiting pending payment ( $bbfm_id )
  */
$bbfm_id = 0;
$query = 'select *';
$query .= " from bbfm";
$query .= " where 1";
// $query .= " and remote_addr = '" . $_SERVER[ 'REMOTE_ADDR' ] . "'";
$query .= " and mode = '" . $mode . "'";
$query .= " and merchant_order_UID = '" . $merchant_order_UID . "'";
$query .= " and amount_asked_in_currency = '" . $int_amount_asked_in_currency . "'";
$query .= " and currency = '" . $currency . "'";
$query .= " and address_merchant = '" . $address_merchant . "'";
$query .= " and ref_merchant = '" . $ref_merchant . "'";
// $query .= " and global_status = 'pending'";
$q = mysqli_query($mysqli, $query);

if( ! $q ){
// debug
//     return_error( "\nerror in bbfm select query : $query" . "\n" . mysqli_error( $mysqli ) );
// prod
    return_error( "\nerror in DB insert query : please contact support." );
}

/*
* return pending payment infos
*/
if( mysqli_num_rows( $q ) > 0 ){
    $row = $q->fetch_array(MYSQLI_ASSOC);
    $hide_powered_by = set_hide_powered_by( $row['display_powered_by'], $is_woocommerce );

    /*
     * already completed payment
     */
    if( $row [ 'global_status' ] == 'completed' ){
        $return = array(
            'result' => 'completed',
            'hide_powered_by' => $hide_powered_by,
            'qrcode' => $qrcode,
        );
        die( json_encode($return) );
    }

    /*
     * error on payment
     */
    if( $row [ 'global_status' ] == 'error' ){
        return_error( "Error while processing payment : " . $row [ 'error_msg' ] );
    }

    /*
     * processing payement...
     */
    if( $row [ 'receive_unit' ] ){
        $return = array(
            'result' => 'processing',
            'hide_powered_by' => $hide_powered_by,
            'qrcode' => $qrcode,
        );
        die( json_encode($return) );
    }

    /*
     * normal pending order
     */
    $BBaddress = $row [ 'address_bbfm' ];
    $amount_BB_asked = $row [ 'amount_BB_asked' ];
     /*
      * return payment infos
      */
    $return = array(
        'result' => 'ok',
        'BBaddress' => $BBaddress,
        'amount_BB_asked' => $amount_BB_asked,
        'hide_powered_by' => $hide_powered_by,
        'qrcode' => $qrcode,
    );
    die( json_encode($return) );
}

/*
* set $hide_powered_by
*/
$hide_powered_by = set_hide_powered_by( $display_powered_by, $is_woocommerce );

/*
* set $amount_BB_asked
*/
$BB_conversion = BB_conversion( $amount_asked_in_currency, $currency );
$rate_age = $BB_conversion[ 'age' ];

// error if rate is more than 1h old
if( $rate_age > 3600 ){
    return_error("We could not get a recent GBYTE change rate for the $currency currency.");
}
$amount_BB_asked = $BB_conversion[ 'BB_amount' ];
$currency_BB_rate = $BB_conversion[ 'conversion_rate' ];

/*
* check $amount_BB_asked < $max_amount_BB_asked
*/
if( $amount_BB_asked > $max_amount_BB_asked ){
    return_error( "Obyte mount of payment cannot be greater than $max_amount_BB_asked Bytes" );
}

/*
* check $amount_BB_asked >= $min_amount_BB_asked
*/
if( $amount_BB_asked < $min_amount_BB_asked ){
    return_error( "Obyte amount of payment cannot be lower than $min_amount_BB_asked Bytes" );
}

/*
* set $fee_bbfm
*/
$fee_bbfm = fee_bbfm( $amount_BB_asked );

/*
* check $amount_BB_asked > $fee_bbfm
*/
if( $amount_BB_asked < $fee_bbfm ){
    return_error( "Obyte payment amount ( $amount_BB_asked ) must be greater than the fees ( $fee_bbfm )" );
}

 /*
  * insert in DB
  */
$query = 'insert into bbfm set date_creation = now()';
$query .= ", remote_addr = '" . $_SERVER[ 'REMOTE_ADDR' ] . "'";
$query .= ", mode = '" . $mode . "'";
$query .= ", merchant_order_UID = '" . $merchant_order_UID . "'";
$query .= ", url_notif  = '" . $url_notif  . "'";
$query .= ", get_or_post_notif   = '" . $get_or_post_notif . "'";
$query .= ", email_notif   = '" . $email_notif . "'";
$query .= ", currency  = '" . $currency  . "'";
$query .= ", amount_asked_in_currency   = '" . $int_amount_asked_in_currency . "'";
$query .= ", amount_BB_asked   = '" . $amount_BB_asked . "'";
$query .= ", currency_BB_rate   = '" . $currency_BB_rate . "'";
$query .= ", fee_bbfm   = '" . $fee_bbfm . "'"; // can be modified when effective amount received
$query .= ", address_merchant   = '" . $address_merchant . "'";
$query .= ", ref_merchant   = '" . $ref_merchant . "'";
$query .= ", attach   = '" . addslashes( $attach ) . "'";
$query .= ", qrcode   = '" . addslashes( $qrcode ) . "'";

// woocommerce fields
$query .= ", ref_partner   = '" . $ref_partner . "'";
$query .= ", partner_cashback_percentage   = '" . $partner_cashback_percentage . "'";
$query .= ", customer   = '" . $customer . "'";
$query .= ", description   = '" . $description . "'";
$query .= ", cashback_address   = '" . $cashback_address . "'";
$query .= ", display_powered_by   = '" . $display_powered_by . "'";
$query .= ", WC_BBFM_VERSION   = '" . $WC_BBFM_VERSION . "'";

$q = mysqli_query($mysqli, $query);
if( ! $q ){
    // debug
//     return_error( "\nerror in bbfm insert query : $query" . "\n" . mysqli_error( $mysqli ) );
    // prod
    return_error( "\nerror in DB insert query : please contact support." );// prod
}
$bbfm_id = mysqli_insert_id ( $mysqli );

 /*
  * set unique payment address
  */
if( $mode == 'live' ){
    $BBaddress = getnewaddressFromWallet();
}else{
    $BBaddress = 'NO-SENDING-ADDRESS-ON-TEST-MODE';
}
if( ! check_BB_address( $BBaddress ) and $mode == 'live' ){
    return_error( 'Error on generating payment address. Please contact support.' );
}

$debug_msg .=  "<br>" . "BBaddress : " . $BBaddress;

 /*
  * insert address_bbfm in database
  */
$query = "update bbfm set address_bbfm = '" . $BBaddress ."'";
$query .= " where id = '" . $bbfm_id . "'";
$q = mysqli_query($mysqli, $query);
if( ! $q ){
    // debug
    return_error( "\nerror in bbfm insert query 2 : $query" . "\n" . mysqli_error( $mysqli ) );
    // prod
    return_error( "\nerror in DB insert query 2 : please contact support." );
}

 /*
  * return payment infos
  */
$return = array(
    'result' => 'ok',
    'BBaddress' => $BBaddress,
    'amount_BB_asked' => $amount_BB_asked,
    'hide_powered_by' => $hide_powered_by,
    'qrcode' => $qrcode,
);
die( json_encode($return) );

function set_hide_powered_by( $display_powered_by, $is_woocommerce ){
    if( $is_woocommerce ){
        $hide_powered_by = ! $display_powered_by;
    }else{
        $hide_powered_by = false;// force display_powered_by on non woocommerce paybuttons
    }
    return $display_powered_by;
}
