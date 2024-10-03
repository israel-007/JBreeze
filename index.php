<?php 

require_once 'vendor/autoload.php';

use json\jbreeze;

$jbreeze = new JBreeze();

$result = $jbreeze->data('data.json')
                    ->limit(2)
                    ->run();

echo $result;
