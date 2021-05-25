<?php

$file = 'user.6txn';
$newfile = '../user.dat';

if (!copy($file, $newfile)) {
    echo "failed to copy $file...<br>";
}




$file = 'blockchain.6txn';
$newfile = '../blockchain.dat';

if (!copy($file, $newfile)) {
    echo "failed to copy $file...<br>";
}

echo "executed.";

?>