<?php 

require_once 'vendor/autoload.php';

use jbreeze\jbreeze;

$jbreeze = new JBreeze();

$result = $jbreeze->data('data.json')
                    ->duplicate(5)
                    ->last()
                    // ->where(['name' => "Liam O'Connor"])
                    ->run();

echo $result;
