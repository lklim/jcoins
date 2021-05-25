<?php

$file = "../blockchain.dat";

$str=file_get_contents($file);

$str=str_replace('";s:12:"', '<br>";s:12:"',$str);
$str=str_replace('"previousHash";s:64:', '<b>"previousHash"</b>;s:64:<span style="background-color: FFFF00">',$str);
$str=str_replace(';s:4', '</span>;s:4',$str);
$str=str_replace('"hash";s:64:', '<b>"hash"</b>;s:64:<font color=red>',$str);
$str=str_replace(';}', '</font>;}<br><br>',$str);
echo '<html>';
echo "<h2>blockchain.dat</h2><br>";
echo $str;
echo '</html>';


?>