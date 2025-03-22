<?php 

require_once 'vendor/autoload.php';

use jbreeze\jbreeze;

$jbreeze = new JBreeze();

$result = $jbreeze->data('data.json')
                    // ->update(['name' => 'janee'])
                    // ->where(['name' => 'janee'])
                    // ->delete()
                    ->insert(['name' => 'Yemi'], 'id')
                    ->run();

echo $result;
