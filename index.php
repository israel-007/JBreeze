<?php

require('JBreeze/autoload.php');

$jb = new JBreeze();

echo $jb->data('data.json')
        ->find('id', 90)
        ->run();


