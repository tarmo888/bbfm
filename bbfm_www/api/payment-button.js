

var BBFM_Host = 'https://byteball-for-merchants.com';

var ss = document.createElement("link");
ss.type = "text/css";
ss.rel = "stylesheet";
ss.href = BBFM_Host + "/api/payment-button.css";
document.getElementsByTagName("head")[0].appendChild(ss);

var BBFM_return = new Object();


window.onload=function(){

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_button"><input type="image" src="' + BBFM_Host + '/api/img/pay-with-bytes.png" alt="Pay with Bytes" title="Pay with Bytes" width="200px"/></div>';

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_test_notif"></div>';

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_error"></div>';

//     document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_payment_link"></div>';

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_payment_qrcode"></div>';

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_processing"></div>';

    document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_completed"></div>';

    if (typeof bbfm_params['display_powered_by'] == "undefined"){
        bbfm_params['display_powered_by'] = 0;
    }

    if (typeof bbfm_params['callback_secret'] == "undefined"){
        bbfm_params['display_powered_by'] = 1;
    }

    if( bbfm_params['display_powered_by'] == 1 ){
        document.getElementById("bbfm_container").innerHTML += '<div id="bbfm_button_footer"><a href="' + BBFM_Host + '" title="pay with byteball" target="_blank">Powered by Byteball-for-Merchants.com</div>';
    }

    bbfm_askPayment();

};


function bbfm_askPayment(){

    var data_file = BBFM_Host + '/api/ask_payment.php?';

    for(var key in bbfm_params) {
//                console.log(key + ' : '  + bbfm_params[key]);
       data_file += '&' + key + '=' + encodeURIComponent(bbfm_params[key]);
    }

    console.log( 'datafile : ' + data_file );


    if( bbfm_params['mode'] == 'test' ){

        document.getElementById("bbfm_test_notif").classList.add('bbfm_test_notif');
        document.getElementById("bbfm_test_notif").innerHTML = '[ test mode ]';

    }



    try{
       // Opera 8.0+, Firefox, Chrome, Safari
       http_request = new XMLHttpRequest();

    }catch (e){
       // Internet Explorer Browsers
       try{
          http_request = new ActiveXObject("Msxml2.XMLHTTP");

       }catch (e) {

          try{
             http_request = new ActiveXObject("Microsoft.XMLHTTP");
          }catch (e){
             // Something went wrong
             alert("Your browser broke!");
             return false;
          }

       }
    }

    http_request.onreadystatechange = function(){

       if (http_request.readyState == 4  ){
          // Javascript function JSON.parse to parse JSON data

          try {
              jsonObj = JSON.parse( http_request.responseText );
            } catch (e) {
              bbfm_return_error("Error returned while trying to parse result : " + e);
              return;
            }

            BBFM_return = jsonObj;

            console.log( BBFM_return );

            if( ! BBFM_return.qrcode ){

                BBFM_return.qrcode = 'https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl={text}&chld=H';

            }


            if( jsonObj.result == 'nok' ){

                bbfm_return_error( jsonObj.error_msg );

            }else if( jsonObj.result == 'completed' ){

                bbfm_return_completed();

            }else if( jsonObj.result == 'processing' ){

                bbfm_return_processing();

                // auto-refresh
                setTimeout(bbfm_askPayment, 5000  );

            }else{

                bbfm_display_payment_link();

                // auto-refresh
                setTimeout(bbfm_askPayment, 5000  );

            }



       }

    }

    http_request.open("GET", data_file, true);
    http_request.send();

}


function bbfm_return_processing(){

//     document.getElementById("bbfm_payment_qrcode").innerHTML = '<img src="https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl=processing received payment&chld=H" >';

    document.getElementById("bbfm_payment_qrcode").innerHTML = '<img src="' + bbfm_qrcode( 'processing received payment' ) + '" >';

    document.getElementById("bbfm_processing").classList.add('bbfm_processing');

    document.getElementById("bbfm_processing").innerHTML = 'Processing received payment...';

}


function bbfm_return_completed(){

//     document.getElementById("bbfm_payment_qrcode").innerHTML = '<img src="https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl=this order has already been paid !&chld=H" >';

    document.getElementById("bbfm_processing").innerHTML = '';
    document.getElementById("bbfm_processing").classList.remove('bbfm_processing');

    document.getElementById("bbfm_payment_qrcode").innerHTML = '<img src="' + bbfm_qrcode( 'this order has already been paid !' ) + '" >';

    document.getElementById("bbfm_completed").classList.add('bbfm_completed');

    document.getElementById("bbfm_completed").innerHTML = 'Payment received !';

}


function bbfm_return_error( msg ){

    document.getElementById("bbfm_processing").innerHTML = '';
    document.getElementById("bbfm_processing").classList.remove('bbfm_processing');

    document.getElementById("bbfm_error").classList.add('bbfm_error');
    document.getElementById("bbfm_error").innerHTML = msg;

}


function bbfm_display_payment_link(){

    var BBaddress = BBFM_return.BBaddress;
    var amount_BB_asked = BBFM_return.amount_BB_asked;


    var payment_url = 'byteball:' + BBaddress + '?amount=' + amount_BB_asked;

    var innerButton =  document.getElementById("bbfm_button").innerHTML;

    if( bbfm_params['mode'] == 'live' ){

        document.getElementById("bbfm_button").innerHTML = '<a href="' + payment_url + '">' + innerButton + '</a>';

//         document.getElementById("bbfm_payment_qrcode").innerHTML = '<a href="' + payment_url + '">' + '<img src="https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl=' + payment_url + '&chld=H" >' + '</a>';

//         document.getElementById("bbfm_payment_qrcode").innerHTML = '<a href="' + payment_url + '">' + '<img src="' + qrcode.replace( '{text}', payment_url ) + '" >' + '</a>';

        document.getElementById("bbfm_payment_qrcode").innerHTML = '<a href="' + payment_url + '">' + '<img src="' + bbfm_qrcode( payment_url ) + '" >' + '</a>';

    }else{

        document.getElementById("bbfm_button").innerHTML = "<a href=\"javascript:alert('You are now in *test* mode.\\n\\nWait a minute to receive test payment auto-notification');\">" + innerButton + '</a>';

//         document.getElementById("bbfm_payment_qrcode").innerHTML = "<a href=\"javascript:alert('You are now in *test* mode.\\n\\nWait a minute to receive test payment auto-notification');\">" + '<img src="https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl=you are now on test mode&chld=H" >' + '</a>';

        document.getElementById("bbfm_payment_qrcode").innerHTML = "<a href=\"javascript:alert('You are now in *test* mode.\\n\\nWait a minute to receive test payment auto-notification');\">" + '<img src="' + bbfm_qrcode( 'you are now on test mode' ) + '" >' + '</a>';

    }


}


function bbfm_qrcode( text ){

    var qrcode_api_format = BBFM_return.qrcode;

    var qr_code = qrcode_api_format.replace( '{text}', text );

    return qr_code;

}