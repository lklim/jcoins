<?php

if(file_exists('../Application.log'))
 unlink('../Application.log');

foreach(glob("../blockchain.*") as $f) {

    if( $f == "nodes.dat") continue;
    if( $f == "me.dat") continue;
        //echo $f.'<br>';
    unlink($f);
}

foreach(glob("../user.*") as $f) {

    if( $f == "nodes.dat") continue;
    if( $f == "me.dat") continue;
        //echo $f.'<br>';
    unlink($f);
}

echo "Cleaned.";

?>