<?php


 include("user.php");
 
 $user = $_POST['user'];
 $detail = $_POST['detail'];

 
 $data = $_POST['data'];
 $sign = $_POST['signature'];
 
 
 $pubKey = user::getUserinfo($user, 'pubkey');    
 
 
 $pubKey =   user::cleanStr($pubKey); 
 $sign   =   user::cleanStr($sign);

 $r = openssl_verify($data, hex2bin($sign), $pubKey, OPENSSL_ALGO_SHA256);
 
 
 if ($r <> 1) { 
 echo '<input type="hidden" id="usradd_status" name="usradd_status" value="'. "Signature verification failed !".'">';
 echo '<input type="hidden" id="id_user_info" name="userinfo" value="Private Key not correct for above user">';
 return;
              }

  $userinfo =  user::getUserinfo($user, $detail);
 
  echo '<input type="hidden" id="id_user_info" name="userinfo" value="'.$userinfo.'">';
 
?>