<?php

$config = parse_url(getenv('DATABASE_URL'));
$mysqli = mysqli_connect($config['host'], $config['user'], !empty($config['pass']) ? $config['pass'] : '', str_replace('/', '', $config['path']), !empty($config['port']) ? $config['port'] : '');
