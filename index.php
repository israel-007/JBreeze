<?php 

require_once 'vendor/autoload.php';

use json\jbreeze;

$jbreeze = new JBreeze();

$result = $jbreeze->data('data.json')
                    ->select([])
                    ->limit(1)
                    ->run();

echo $result;
