<?php
include("blockchain.php");
require_once("block.php");
include("user.php");

 
$fn = $_GET['function'];

if($fn == 'hello'){
    $ret = hello();
}
else if($fn == 'GetLastBlock')
 $ret = GetLastBlock();
else if($fn =='addUser')
 $ret = addUser($_GET['srcnode']);
else if($fn =='newBkNotify')  
 $ret = newBkNotify($_GET['blk'], $_GET['type']);
 
 echo $ret;

function addtUser(){
 
  $obj = new user($_GET['firstname'], $_GET['lastname'], $_GET['ethaddr'], $_GET['pubKey']);  
}

function addUser($src){
   
   if(!@copy($src,'../dat/user.dat'))
{
    $errors= error_get_last();
    echo "COPY ERROR: ".$errors['type'];
    echo "\n".$errors['message'];
    return false;
} else {
    echo "User File copied from remote!"."\n";
    return true;
}

}

function GetLastBlock(){
 
    if(blockchain::isBcExist())
      return blockchain::getLastBk();
    else
      return 'none'; 
    
}

function newBkNotify($objstr, $type)
{
  
  $existingBkStr = blockchain::getLastBk();
  $existingObj = unserialize($existingBkStr);
  $newObj = unserialize($objstr);
 
  $user = blockchain::getUserFromBk($newObj);
  
   if(!user::validate_balance($user['fuser'], $user['famount'])){
    var_dump('User [b]'.$user["fuser"].'[/b] does not have enough Jcoins to transfer.');
    return "NO";
    }
  if($newObj->index != $existingObj->index + 1) {
     var_dump("New block with index $newObj->index, is not 1 index higher than existing last Block with index $existingObj->index");     
       return "NO"; //new block index is not greater by 1 of existing block of this node 
                                                }
  if(substr($newObj->hash, 0, blockchain::Difficulty) != str_repeat("0", blockchain::Difficulty))
             { //if not enough 0000 infront in the hash
               var_dump("Block with index $newObj->index, hash : $newObj->hash does not have enough 0 prefixed. Requires blockchain::Difficulty zeros!");
               return "NO"; //if current block no of 0 prefix does not match bc no of 0 reqd
             }
             
  if ($newObj->previousHash != $existingObj->hash) {
                var_dump("New block with index $newObj->index, previousHash : $newObj->previousHash does not match existing Block with index $existingObj->index, hash : $existingObj->hash .") ;
                return "NO";
            }
  if ($newObj->hash != $newObj->calculateHash()) {
                var_dump(' calculateHash() detected invalid block with index :'.$newObj->index. ' ');
                return "NO";
            }
  if(!blockchain::verifySign($newObj, $user)) {   
     var_dump("New block with index : $newObj->index has Signature does not match with transaction !");
     return "NO"; //new block signature doesn't match
                            } 
 if($type == 'q') // if q just return vote
  return "YES";  
 else if($type == 'i') { //polling of the new block accepted >= 50% of nodes so add new block to all nodes
   $updHash = user::update_user($user['fuser'], $user['tuser'], $user['famount'], $newObj->index, $newObj->timestamp);
   user::writeUsers2file();
   blockchain::insertBk2file($newObj) ; 
   return "Updated"; 
                       }
  
}


function hello() {
    
     return 'Hello from '.blockchain::myNode();
}

?>
