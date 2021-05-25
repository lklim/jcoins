<?php
include("blockchain.php");
include("user.php");
include("Keccak.php");

date_default_timezone_set('Asia/Singapore');
$ob_file = fopen('../dat/Application.log','a');
ob_start('ob_file_callback');
$logended = false;

logHeader();
$nodeList = blockchain::getRemoteNodes();

if($nodeList == false ) {
  echo "No remote nodes list found. So does no attempt to communicate with any remote node. \n";
                                                          }
else {
 $peer2peer_status = null;
 $nodeArr = null;
  
    foreach ($nodeList as $node) {
        $response = blockchain::callRemote($node, "php/reply2peers.php?function=GetLastBlock&securityhash=", 1);
        
        if(($response) && trim($response) == 'none') 
           $peer2peer_status[0] = 5;   //remote node has no bc
        else if((!$response) || (strpos($response, '^^') ))
          $peer2peer_status[0] = 4;
        else if($response) {
                set_error_handler("enoticeHandle");
                if(unserialize($response) == false){
                   echo "Detected corruption when reading the last block of the Blockchain of remote node [".$node."] .Thus rejecting this node Blockchain.\n\n";
                   restore_error_handler();
                   continue;
                                      }
          $peer2peer_status = blockchain::peer2peerState($response);
        }
        switch ($peer2peer_status[0]) { //$peer2peer_status[0] nodes relation, when 3 $peer2peer_status[1] is index
          case 1:  //both nodes in sync so do nothing
               echo "Local and Remote Nodes [$node] are in Sync . "."\n";
               break;
          case 2:  //local nodes more updated tell dest node to update
                
               if(count($_GET) == 0 || $_GET['function'] == 'server') { //only the server tell other nodes
                 echo "Remote Node [$node] is less updated. Told Remote Node to update . "."\n";
                 $response = blockchain::callRemote($node, "php/sync_peers_blockchain.php?function=client&securityhash=", 0); //must use 0 as last param else keep waiting for data to return when there's none
                 ///sleep(1);    
                                }
               break;
          case 3: //dest nodes more updated, copy dest bc over to tmp, verify bc then replace local with dest
               echo 'Me ['.blockchain::myNode().'] am less updated than ['.$node."]\n";
               $nodeArr[$node] = $peer2peer_status[1]; //store dest last index with url ie assoc array
               break;
          case 4: //dest not responding, do nothing just reject dest node
               echo 'resp:'.$response.'\n';
               echo "Remote Node [$node] reply2peers.php GetLastBlockreturn blank ! "."\n";
               break;
          case 5: //remote node has no blockchain or not responding so do nothing with the remote node
               
               if(count($_GET) == 0 || $_GET['function'] == 'server') { //only the server tell other nodes
                 echo "Remote Node [$node] says it has no Block Chain. So I call on it to do a update check on itself"."\n";
                 $response = blockchain::callRemote($node, "php/sync_peers_blockchain.php?function=client&securityhash=", 0); //must use 0 as last param else keep waiting for data to return when there's none
                 ///sleep(1);
                               }
               break;
         case 6: 
               blockchain::downgradeRemoteNode($node);  
               echo "Remote Node [$node] last block corrupted. Ignoring the node \n";
               echo "[$node] set to doubted in nodes.dat and will be skipped while it is in doubted state\n";   
               $msg = "sync_peers_blockchain detected Remote Node [$node] last block corrupted at ".date('d/m/Y h:i:s a', time()).". Please check Application.log for details!";
                error_log($msg, 1, "llkt1@yahoo.com");
                break;
          default:
               break;
                                     } 
            
                      } //loop thru if node is corrupted set it index = 0 find next longest chain node
    if($nodeArr) { //deal with above case 3 situation, if there are nodes with bc with longer chain
             // I will check which Remote Node to update with . 
        blockchain::setSuperuserPubkey(); //using local user.setup super user pub key to verify remote node bc  
        
        foreach ($nodeArr as $nodestub ){ //assoc array key is node str, content is its last index
             // $noded = implode(" ",array_keys($nodeArr,max($nodeArr))); //get key aka node has content is max index
        
             $noded = array_keys($nodeArr,max($nodeArr)); //return nodes with same max index
             foreach($noded as $node2check) { //in case where 2 or more nodes have same max index
               if($nodeArr[$node2check] == -1) //no point doing if the node index has been set to -1
                  continue;
               if(!replicate_remote_bc($node2check)) { //if remote node bc is not valid
                 $nodeArr[$node2check] = -1; //set the longest chain index to -1 inorder to get next longest chain
                 blockchain::downgradeRemoteNode($node2check);  
                 $doubtedNode = (explode("/",trim($node2check)));
                 copy('../dat/blockchain.dat.tmp', '../dat/blockchain.dat'.'.doubted.'.$doubtedNode[3]);
                 echo "Node [$node2check] set to doubted in nodes.dat and will be skipped while it is in doubted state\n";   
                                                       }
                else break 2; // break both for loop since replicated successfully from one remote node no need to test other nodes
                                               }
              
                                            }
                     }  
  }
  
  
if(!$logended){ 
 logFooter();
 ob_end_flush();
 if(count($_GET) == 0 || $_GET['function'] == 'server')
  echo '<input type="hidden" id="usradd_status" name="refresh_node" value="'.blockchain::myNode().'">';
}



function replicate_remote_bc($remoteNode)
{
    if(!@copy($remoteNode.'dat/blockchain.dat','../dat/blockchain.dat.tmp')) {
     $errors= error_get_last();
     echo 'When copying remote '.$remoteNode.'dat/blockchain.dat'." COPY ERROR: ".$errors['type'];
     echo "\n".$errors['message'];
     return false;                                                              }
    else 
     echo "Blockchain File copied from remote node [$remoteNode] ! "."\n";


   if(!@copy($remoteNode.'dat/user.dat','../dat/user.dat.tmp')){
     $errors= error_get_last();
     echo 'When copying remote '.$remoteNode.'dat/user.dat'." COPY ERROR: ".$errors['type'];
      echo "\n".$errors['message'];
    return false;                                               }
   else 
     echo "User File copied from remote!"."\n";
        

   if(isValidRemoteBc('../dat/blockchain.dat.tmp', '../dat/user.dat.tmp')) //validate the entire remote bc
       localBcFilesReplace($remoteNode);
   else                                          { // not valid foreign bc, do nothing
         echo "Error validating remote block chain ! Thus skipping remote node [$remoteNode] Blockchain.\n";
         $msg = "sync_peers_blockchain detected Remote Node [$remoteNode] Blockchain has errors at ".date('d/m/Y h:i:s a', time()).". Please check Application.log for details!";
         error_log($msg, 1, "llkt1@yahoo.com");
         return false;                           }
     
    
  return true;
}

function isValidRemoteBc($bcf, $uf){
    $jCoinRemote = new BlockChain($bcf);
    if($jCoinRemote)
       if(!$jCoinRemote->isBcValid($uf)) {
         echo "Blockchain check unable to validate the chain integrity !\n";
         return false;  //fail test 1 , validity of blockchain
                                         }
       else ; //if valid do nothing let the last line in fn return true as still need to check user.dat;
     else {
         echo 'Unable to asign blockchain in memory'."\n";
         return false;
     }
     //next step check user.dat is valid or not
     if(!user::isUserlistValid($jCoinRemote, $uf)) {  //fail test 2 , validity of user list
       echo 'Unable to validate the integrity of the remote user.dat file'."\n";
       return false;
                                                      }
     return true;
}

function localBcFilesReplace($remoteNode){

if(file_exists('../dat/blockchain.dat') ) 
  copy('../dat/blockchain.dat','../dat/blockchain.dat.prev');

if(file_exists('../dat/user.dat') ) 
  copy('../dat/user.dat','../dat/user.dat.prev');


if(rename('../dat/blockchain.dat.tmp', '../dat/blockchain.dat'))
  if(rename('../dat/user.dat.tmp', '../dat/user.dat'))
      echo "Local blockchain.dat & user.dat file replace with remote [$remoteNode] Successful!\n";
   else {
      echo "Local user.dat file replace with remote [$remoteNode] NOT Successful!\n";
      return false;
        }
else {
  echo "Local blockchain.dat file replace with remote node [$remoteNode] NOT Successful! \n";
  return false;
     }
     
     return true;
  
}

function enoticeHandle($errno, $errstr, $errfile, $errline){
    ;
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
