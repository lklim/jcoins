<?php

include("../Keccak.php");
include("../user.php");


 
 
   $pubstr = "-----BEGIN PUBLIC KEY----- ^42^MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAPXNJgBrfbXfO2xR09UrQdAZgQrXFvdu ^42^epZvosUliYhX9BZPJ1fkYzmMhzRduKljGeoRYcwj4XxyBxX8MMtPTxcCAwEAAQ== ^42^-----END PUBLIC KEY----- ^42^";
   
   //$pubstr2 =  dirtyStr($pubstr);
   //echo $pubstr2.'<br>';
   $ethaddrkey = user::cleanStr($pubstr); 
   //$ethaddrkey = $pubstr; 
   echo  'PEM:'.$ethaddrkey.'<br>';
   //$ethaddrkey = substr($ethaddrkey,26, strlen($ethaddrkey) - 28 - 26);
   $ethaddrkey = substr($ethaddrkey,28, 60);
   echo 'PEM:'.$ethaddrkey.'<br>';
   //$ethaddrkey = " MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAN9vYpOOCXy6Rs7QSjrZKVI7AvHWp+og 9o88Pp4k6IL9a5gGUDwqD5yQBhQBpnvfenOQvHUVG3uj1pop7Hw9sA8CAwEAAQ==";
   echo "Generated e wallet address: \n";
   $ethaddr = '0x'.substr(Keccak256::hash($ethaddrkey, 256),24,40);  
   echo  $ethaddr;
 
 
 function  dirtyStr($txt) {
   $txt = str_replace("&",'^36^' ,$txt);
   $txt = str_replace("*",'^37^' ,$txt);
   $txt = str_replace("(",'^38^' ,$txt);
   $txt = str_replace(")",'^39^' ,$txt);
   $txt = str_replace("_",'^40^' ,$txt);
   $txt = str_replace("+",'^41^' ,$txt);
   $txt = str_replace("\n",'^42^' ,$txt);
   return $txt;
}

 
?>