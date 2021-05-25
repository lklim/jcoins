<?php

include("user.php");
include("blockchain.php");

 
 $fuser = $_POST['fuser'];
 $tuser = $_POST['tuser'];
 $fethaddr = $_POST['fethaddr'];
 $tethaddr = $_POST['tethaddr'];
 $famount = $_POST['famount'];
 $txntimestamp = $_POST['txntimestamp'];
 $sign = $_POST['signature'];
 
 $fethaddr = user::cleanStr($fethaddr);
 $tethaddr = user::cleanStr($tethaddr);
 
 $data = $fuser.$tuser.$fethaddr.$tethaddr.$famount.$txntimestamp;
 $pubKey = user::getUserinfo($fuser, 'pubkey');    
 
 
 $pubKey =   user::cleanStr($pubKey); 
 $sign   =   user::cleanStr($sign);

 $r = openssl_verify($data, hex2bin($sign), $pubKey, OPENSSL_ALGO_SHA256);
 
 
 if ($r <> 1) { 
 echo '<input type="hidden" id="usradd_status" name="usradd_status" value="'. "Signature verification failed !".'">';
 return;
              }
 
 
 //need to add fn here to validate fuser has enough jcoins to give away.
 if(!user::validate_balance($fuser, $famount)){
    echo '<input type="hidden" id="usradd_status" name="usradd_status" value="Hi '.$fuser.', you do not have enough Jcoins to transfer'.'">';
    return;
 }
 
 $jCoin = new BlockChain();
 $updHash = user::update_user($fuser, $tuser, $famount, $jCoin->getCountBk(), $txntimestamp);
 $txn = $fuser.','.$tuser.','.$_POST['fethaddr'].','.$_POST['tethaddr'].','.$famount.','.$updHash;
 $newBk_index = $jCoin->push(new Block($jCoin->getCountBk(), $txntimestamp, $txn, $_POST['signature'] ));  
 
 $add_status = $jCoin->broadcastNewBk($newBk_index); //poll all the nodes for new block consensus
 if($add_status) { //if 50% of other nodes accept the new block
  user::writeUsers2file();  //commit user latest balance update to disk if votes >= 50% else discard block
  $jCoin->writeBks2file(); //commit block chain latest balance update to disk if votes >= 50% else discard block
  echo '<br><input type="hidden" id="usradd_status" name="usradd_status" value="Hi '.$fuser.', '.$famount. ' Jcoins successfully transferred to '.$tuser.'">';
                 }
  else
   echo '<br><input type="hidden" id="usradd_status" name="usradd_status" value="Hi '.$fuser.', <b>FAILED<b> to transfer '.$famount. ' Jcoins to '.$tuser.' Please try again later !">';
 
?>