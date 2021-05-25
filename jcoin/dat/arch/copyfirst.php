<?php

$file = 'user.1st';
$newfile = '../user.dat';

if (!copy($file, $newfile)) {
    echo "failed to copy $file...<br>";
}




$file = 'blockchain.1st';
$newfile = '../blockchain.dat';

if (!copy($file, $newfile)) {
    echo "failed to copy $file...<br>";
}

echo "executed.";

?>