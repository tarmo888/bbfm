<?php
file_put_contents(__DIR__ ."/../../log/callback_test.txt", json_encode($_REQUEST, JSON_PRETTY_PRINT) ."\n\n", FILE_APPEND);