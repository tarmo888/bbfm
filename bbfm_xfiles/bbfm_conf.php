<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
	die("Run: <i>composer install</i>");
}
require_once $autoload;

if (file_exists(__DIR__ . '/../.env')) {
	$dotenv = Dotenv\Dotenv::create(__DIR__ .'/../');
	$dotenv->load();
}

$_currencies = array(
    'USD' => array(
        'decimal' => 2,
    ),
    'BTC' => array(
        'decimal' => 9,
    ),
    'EUR' => array(
        'decimal' => 2,
    ),
    'B' => array(
        'decimal' => 0,
    ),
    'KB' => array(
        'decimal' => 0,
    ),
    'MB' => array(
        'decimal' => 2,
    ),
    'GB' => array(
        'decimal' => 2,
    ),
    'AUD' => array(
        'decimal' => 2,
    ),
    'BRL' => array(
        'decimal' => 2,
    ),
    'CAD' => array(
        'decimal' => 2,
    ),
    'CHF' => array(
        'decimal' => 2,
    ),
    'CLP' => array(
        'decimal' => 2,
    ),
    'CNY' => array(
        'decimal' => 2,
    ),
    'DKK' => array(
        'decimal' => 2,
    ),
    'GBP' => array(
        'decimal' => 2,
    ),
    'HKD' => array(
        'decimal' => 2,
    ),
    'INR' => array(
        'decimal' => 2,
    ),
    'ISK' => array(
        'decimal' => 2,
    ),
    'JPY' => array(
        'decimal' => 2,
    ),
    'KRW' => array(
        'decimal' => 2,
    ),
    'NZD' => array(
        'decimal' => 2,
    ),
    'PLN' => array(
        'decimal' => 2,
    ),
    'RUB' => array(
        'decimal' => 2,
    ),
    'SEK' => array(
        'decimal' => 2,
    ),
    'SGD' => array(
        'decimal' => 2,
    ),
    'THB' => array(
        'decimal' => 2,
    ),
    'TWD' => array(
        'decimal' => 2,
    ),
    'CZK' => array(
        'decimal' => 2,
    ),
);

$max_amount_BB_asked = 1000000000000;
$min_amount_BB_asked = 1500;
