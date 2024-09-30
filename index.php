<?php

require('JBreeze/autoload.php');

$jb = new JBreeze();

echo $jb->data('https://jsonplaceholder.typicode.com/users')
        ->find('id', 9)
        ->run();


        