<?php
include("blockchain.php");
$filePath = '../dat/nodes.dat';
date_default_timezone_set('Asia/Singapore');
$ob_file = fopen('../dat/Application.log','a');
ob_start('ob_file_callback');
$logended = false;

logHeader();

if (!file_exists($filePath)){
    echo 'cannot find file';
    return;
                            }


$str = file_get_contents($filePath);
$nodelist = explode("\n", $str);
$peer2peer_status = null;
$nodeArr = null;

    foreach ($nodelist as $node) {
        $response = callRemote($node, "php/reply2peers.php?function=GetLastBlock&securityhash=", 1);
        //$peer2peer_status = blockchain::peer2peerState(blockchain::decodeStr($response));
        $peer2peer_status = blockchain::peer2peerState($response);
        switch ($peer2peer_status[0]) {
          case 1:  //both nodes in sync so do nothing
               echo 'Local and Remote Nodes are in Sync . '."\n";
               break;
          case 2:  //local nodes more updated tell dest node to update
               echo 'Remote Node less updated. Told Remote Node to update . '."\n";
               logFooter();
               ob_end_flush();
               $logended = true;
               $response = callRemote($node, "php/sync_peers_blockchain.php?function=doNothing&securityhash=", 1);
               echo $response;
               break;
          case 3: //dest nodes more updated, copy dest bc over to tmp, verify bc then replace local with dest
               echo 'I will check which Remote Node to update with . '."\n";
               $nodeArr[$node] = $peer2peer_status[1]; //store dest index with url ie assoc array
               break;
          case 4: //dest node corrupted, do nothing just reject dest node
               
               break;
         
          default:
               break;
                                     } 
        if($nodeArr) { //if there are nodes with bc with higher no of blocks 
            $noded = implode(" ",array_keys($nodeArr,max($nodeArr))); //replicate over the node with highest index
            replicate_remote_bc($noded) ;
                      }
        }      
if(!$logended){ 
 logFooter();
 ob_end_flush();
}

function callRemote($p1, $p2, $p3){
  $hash = bin2hex(random_bytes(16));
  $request_url = $p1.$p2.$hash;
  return get_url($request_url, $p3);   
}                               

function get_url($request_url, $wait) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $request_url);
  curl_setopt($ch, CURLOPT_POST,0);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, $wait);
  
  $response = curl_exec($ch);
  curl_close($ch);

  return $response;
}

function replicate_remote_bc($remoteNode)
{
    if(!@copy($remoteNode.'dat/blockchain.dat','../dat/blockchain.dat.tmp'))
{
    $errors= error_get_last();
    echo "COPY ERROR: ".$errors['type'];
    echo "\n".$errors['message'];
    return false;
} else {
    echo "Blockchain File copied from remote ! "."\n";
}
   if(isValidRemoteBc('../dat/blockchain.dat.tmp')){ //validate the entire remote blockchain
       
       localBcFilesReplace($remoteNode);
   }
   else // not valid foreign bc, do nothing
     {   echo 'error validating remote block chain ! '."\n";
         $msg = 'sync_peers_blockchain produced error at '.date('d/m/Y h:i:s a', time()).". Please check Application.log for details!";
         error_log($msg, 1, "llkt1@yahoo.com");
         return false;}
     
    
  return true;
}

function isValidRemoteBc($f){
    $jCoinRemote = new BlockChain($f);
    if($jCoinRemote)
       return $jCoinRemote->isValid();
     else {
         echo 'unable to asign blockchain in memory'."\n";
         return false;
     }
}

function localBcFilesReplace($f){
   if(!@copy($f.'dat/user.dat','../dat/user.dat.tmp'))
{
    $errors= error_get_last();
    echo "COPY ERROR: ".$errors['type'];
    echo "\n".$errors['message'];
    return false;
} else {
    echo "User File copied from remote!"."\n";
}

copy('../dat/blockchain.dat','../dat/blockchain.dat.prev');
copy('../dat/user.dat','../dat/user.dat.prev');


if(rename('../dat/blockchain.dat.tmp', '../dat/blockchain.dat'))
  if(rename('../dat/user.dat.tmp', '../dat/user.dat'))
      echo 'Local Block Chain replace with remote Successful! '."\n";
   else {
      echo 'Local Block Chain replace with remote NOT Successful! '."\n";
      return false;
        }
else {
  echo 'Local Block Chain replace with remote NOT Successful! '."\n";
  return false;
     }
     
     return true;
  
}

function ob_file_callback($buffer)
{
  global $ob_file;
  fwrite($ob_file,$buffer);
}

function logHeader(){
echo date('d/m/Y h:i:s a', time())."\n";
echo 'Start logging sync_peers_blockchain.php ouput'."\n";
echo "\n";
}


function logFooter(){
echo "\n";        
echo "\n";
echo date('d/m/Y h:i:s a', time())."\n";
echo 'End logging sync_peers_blockchain.php ouput'."\n";
echo '***************************************************************************';
echo "\n";
echo "\n";    
}

?>
