<?php

include("Keccak.php");
include("user.php");

 
 $data = $_POST['message'];
 $sign = $_POST['signature'];
 $pubstr = $_POST['pubKey'];
                        
 if(!user::isNewUser($data)) {
      echo '<input type="hidden" id="ethaddr" name="ethaddr" value="'. "User name already exist !".'">';
      return;
 }
 
 $ethaddrkey = $pubKey = user::cleanStr($pubstr); 
 $sign = user::cleanStr($sign);

 $r = openssl_verify($data, hex2bin($sign), $pubKey, OPENSSL_ALGO_SHA256);
 
 if ($r == 1) {
   //$ethaddrkey = substr($ethaddrkey,26, strlen($ethaddrkey) - 28 - 26);
   $ethaddrkey = substr($ethaddrkey,29, 20);
   $ethaddr = '0x'.substr(Keccak256::hash($ethaddrkey, 256),24,40); 
   echo '<input type="hidden" id="ethaddr" name="ethaddr" value="'. $ethaddr.'">';
 }
 else
  echo '<input type="hidden" id="ethaddr" name="ethaddr" value="'. "Signature verification failed !".'">';
 
 
 

 
?>