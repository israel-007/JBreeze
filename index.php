<?php

require('JBreeze/autoload.php');

$jb = new JBreeze();

echo $jb->data('data.json')
        ->select(['id'])
        ->run();

