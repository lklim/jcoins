<?php

include("user.php");
include("blockchain.php");


if(count($_GET) == 0){
 
 $param['count'] = count($_POST);
 $param['fn'] = $_POST['firstname'];
 $param['ln'] = $_POST['lastname'];
 $param['ethaddr'] = $_POST['ethaddr'];
 $param['pubKey'] = $_POST['pubKey'];
 
}
else
{
 
 $param['count'] = count($_GET);
 $param['fn'] = $_GET['firstname'];
 $param['ln'] = $_GET['lastname'];
 $param['ethaddr'] = $_GET['ethaddr'];
 $param['pubKey'] = $_GET['pubKey'];  
}

if(!user::isNewUser($param['fn'].' '.$param['ln'])) {
      echo '<input type="hidden" id="usradd_status" name="ethaddr" value="User '.$param['fn']. ' '.$param['ln'].' already exist and cannot be added !">';
      return;
 }

$obj = new user($param['fn'], $param['ln'], $param['ethaddr'], $param['pubKey']);

echo '<input type="hidden" id="usradd_status" name="ethaddr" value="<b>User '.$param['fn']. ' '.$param['ln'].' added"></b><br>';


echo '<input type="hidden" id="usraddremote" name="ethaddr" value="'.$param['count'].'">';

if(trim(strtoupper($param['fn'].' '.$param['ln'])) != 'SUPER USER') //all other user except this need to broadcast out
 if($param['count'] == 4)  //if this is the primary node that the user addition is being executed 
   broadcastUserAdd($param);
  
function  broadcastUserAdd($param) {
 //echo 'inside fn broadcastUserAdd()';   
//$MeNode = blockchain::whoAmI();

//$nodelist = explode("\n", $str);
$nodeList = blockchain::getRemoteNodes();

if($nodeList) //only when this node is NOT the only node in the Blockchain
  foreach ($nodeList as $node) {
    
      $pubKey = $param['pubKey'];
      $pubKeystr = urlencode($pubKey);

      $vars = "firstname=".$param['fn']."&lastname=".$param['ln']."&ethaddr=".$param['ethaddr']."&pubKey=".$pubKeystr.'&securityhash='  ;
      $suffix = 'php/add_new_user.php?'.$vars;
      $response = blockchain::callRemote($node,$suffix , 1);
      //var_dump($response);
                                }
                             
                                   }





?>