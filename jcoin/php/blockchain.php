<?php
require_once("block.php");

/**
 * A simple blockchain class with proof-of-work (mining).
 */
 
class BlockChain
{
    /**
     * Instantiates a new Blockchain.
     */
     
    const BlockPath = '../dat/blockchain.dat';
    const NodesPath = '../dat/nodes.dat';
    const MePath    = '../dat/me.dat';
    const Difficulty = 5;
    const RemoteCallRetries = 3;
    
    private $chain = [];
    private static $superuser_pubkey = '';
    

    public function __construct($alternate_bc = null)
    {
     
     
     
     clearstatcache(); //file stat is cache so sometimes file_exists return false negative  
       
     if($alternate_bc) //if a foreign bc has been imported to be candidacy for replacement of local bc
     {
       
       
       if(file_exists($alternate_bc) ) 
        $this->loadBkchainFile($alternate_bc);  
        else {
         //do what here ? ponder
        }
     }
     
     else {
      if(file_exists(self::BlockPath) ) 
        $this->loadBkchainFile(self::BlockPath);
      else     
        $this->chain = [$this->createGenesisBlock()];
          }
        
      
     }

  

    /**
     * Creates the genesis block.
     */
    private function createGenesisBlock()
    {
        return new Block(0, strtotime("2017-01-01"), "Genesis Block");
    }

    /**
     * Gets the last block of the chain.
     */
    public function getLastBlock()
    {
        return $this->chain[count($this->chain)-1];
    }

    /**
     * Pushes a new block onto the chain.
     */
    public function push($block)
    {
        $block->previousHash = $this->getLastBlock()->hash;
        $this->mine($block);
        array_push($this->chain, $block);
        return $block->index;
       
    }

    /**
     * Mines a block.
     */
    public function mine($block)
    {
        $time_start = self::microtime_float();
        while (substr($block->hash, 0, self::Difficulty) !== str_repeat("0", self::Difficulty)) {
            $block->nonce++;
            $block->hash = $block->calculateHash();
        }
        $time_end = self::microtime_float();
        $time = $time_end - $time_start;
        echo "Block mined: ".$block->hash." in $time seconds.<br>";
    }

    /**
     * Validates the blockchain's integrity. True if the blockchain is valid, false otherwise.
     */
    public function isBcValid($uf)
    {
       
        $balances['SUPER USER'] = user::SuperuserMax;
        
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i-1];
            
            if (!is_object($currentBlock)) {
                echo "A block with index [$i] in the Blockchain of the remote node is corrupted.\n"; 
                return false; 
             }
            if ($currentBlock->hash != $currentBlock->calculateHash()) {
                echo 'Running calculateHash() detected invalid block with index :'.$currentBlock->index." ! \n";
                return false;
            } 
            if(substr($currentBlock->hash, 0, self::Difficulty) != str_repeat("0", self::Difficulty))
             {
               echo "Block with index $currentBlock->index, hash :".$currentBlock->hash." does not have enough 0 prefixed. Requires ".self::Difficulty." zeros! \n";
               return false; //if current block no of 0 prefix does not match bc no of 0 reqd
             }
            if ($currentBlock->previousHash != $previousBlock->hash) {
                echo "Block with index $currentBlock->index, previousHash :".$currentBlock->previousHash." does not match previous Block with index $previousBlock->index, hash :".$previousBlock->hash." ! \n" ;
                return false;
            }
            
             $user = blockchain::getUserFromBk($currentBlock);
            if($user['famount'] <= 0){
                echo "Block with index $currentBlock->index, Transaction amount : ".$user['famount']." and is less than or equal to zero. Invalid transaction ! \n" ;
                       return false;         
                                     }  
            //if(!user::isNewUser($user['fuser'])) //if user in our user.dat then test its signature else maybe new user
              if(!blockchain::verifySign($currentBlock, $user, $uf)) { //if signature does not matches
                 echo "Block with index $currentBlock->index, Signature :".$currentBlock->sign." does not match the data in the block ! \n" ;
                       return false;                             } //no point test sign if new user in remote bc
              else { 
                    $eth = null;
                    $eth = user::cleanStr(user::getUserinfo($user['fuser'], 'pubkey', $uf ));
                    ///echo 'hd:'.$eth."\n";
                    $eth = substr($eth,28, 60); //after write to disk somehow length become shorter by 1 ? 
                    ///echo 'shd:'.$eth."\n";
                    ///echo user::cleanStr($user['fethaddr'])."\n";
                    $eth = '0x'.substr(Keccak256::hash($eth, 256),24,40);
                    ///echo $eth."\n";
                     if(user::cleanStr($user['fethaddr']) != $eth){
                      echo "Block with index $currentBlock->index,".$user['fuser']." public key in remote user.dat does not match its wallet address in remote blockchain.dat ! \n" ;
                       return false;
                    } 
                    $eth = null;
                    $eth = user::cleanStr(user::getUserinfo($user['tuser'], 'pubkey', $uf ));
                    $eth = substr($eth,28, 60);
                    $eth = '0x'.substr(Keccak256::hash($eth, 256),24,40);
                    ///echo 'shd:'.$eth."\n";
                    ///echo user::cleanStr($user['tethaddr'])."\n";
                    if(user::cleanStr($user['tethaddr']) != $eth){
                      echo "Block with index $currentBlock->index,".$user['tuser']." public key in remote user.dat does not match its wallet address in remote blockchain.dat ! \n" ;
                       return false;
                    } 
                    
                    if(!isset($balances[$user['fuser']])) $balances[$user['fuser']] = 0;
                    if(!isset($balances[$user['tuser']])) $balances[$user['tuser']] = 0;
                    $balances[$user['fuser']] -= $user['famount']; $balances[$user['tuser']] += $user['famount'];
                   }
            
                                                        }
                                                  
        foreach($balances as $key => $balance) //compute the balances in the remote bc file
               if($balance < 0) {
                   echo "User $key account balance $balance is less than zero in the Blockchain. Thus invalid !\n";
                   return false;
                                 }  
               
         
        echo "Blockchain file integrity validated ok \n";
        return true;
    }
    
 private function loadBkchainFile($f)
    {
      clearstatcache(); //file stat is cache so sometimes file_exists return false negative
      
      $handle = fopen($f, "r");
 
      if ($handle) {
          while (($line = fgets($handle)) !== false) {
              $line = str_replace("\n","",$line);
              if($line)
                 array_push($this->chain, unserialize($line));
                                                      }
          fclose($handle);
                    } 
       else 
          die("Unable to open file!");

     }

 public function writeBks2file(){
     clearstatcache(); //file stat is cache so sometimes file_exists return false negative
     
     if(file_exists(self::BlockPath) ) 
        rename ( self::BlockPath , self::BlockPath.'.old') ;
        
     for ($i = 0; $i < count($this->chain); $i++) {
          $objData = serialize($this->chain[$i])."\n" ;
          $myfile = file_put_contents(self::BlockPath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
                                                  }
   }
 
 public static function insertBk2file($objData){
     $objDataStr = serialize($objData)."\n" ;
     $myfile = file_put_contents(self::BlockPath, $objDataStr.PHP_EOL , FILE_APPEND | LOCK_EX);
   }
   
 public function getCountBk() {
     return count($this->chain);
 }
 
 public static function peer2peerState($bkRemote){
     $bkLocal = trim(self::getLastBk());
     $bkRemote = trim($bkRemote);
     $objRemote = unserialize($bkRemote); 
     
     clearstatcache(); //file stat is cache so sometimes file_exists return false negative
     
     /* if(!self::isUfExist())  //user.dat missing
        if(file_exists(user::UserSetupPath) ) {
           echo "user.dat is missing. Copying over a new user.dat via a template from setup.user in dat directory\n";
           copy(user::UserSetupPath, user::UserPath);
                                               } */
                                               
     if(!self::isBcExist()) { //if me myself has no blockchain
       //echo "Me [".self::myNode()."] is missing the Blockchain files \n";
       if(self::isValidBk($objRemote))
          return array(3, $objRemote->index); //dest node index greater than local as local is zero bc
        else
          return array(6, 0);  //dest is corrupted block
                                }  
     //if($bkLocal == $bkRemote)
     if(strcmp($bkLocal,$bkRemote) == 0)
        return array(1,0);  //we r sync
     else
       {  
          $objLocal = unserialize($bkLocal);
         
          if(self::isValidBk($objRemote)){
             if($objLocal->index > $objRemote->index )
               return array(2,0); // local node index greater than dest
             else if($objLocal->index < $objRemote->index )
               return array(3, $objRemote->index); //dest node index greater than local
             else if($objLocal->index == $objRemote->index )
                return array(1,0);  //both blocks same index number but data inside them are different , do nothing
                                            }
           else   return array(6, 0);  //dest is corrupted block 
          
       }       
 }
 
 public static function getLastBk(){
    $line = '^^';
    clearstatcache(); //file stat is cache so sometimes file_exists return false negative
    
    if(file_exists(self::BlockPath) ){
        $line = '';
       $handle = fopen(self::BlockPath, 'r');
       $cursor = -1;
       fseek($handle, $cursor, SEEK_END);
       $char = fgetc($handle);

       while ($char === "\n" || $char === "\r") {
              fseek($handle, $cursor--, SEEK_END);
              $char = fgetc($handle);
                                                }
       while ($char !== false && $char !== "\n" && $char !== "\r") {
             $line = $char . $line;
             fseek($handle, $cursor--, SEEK_END);
             $char = fgetc($handle);
                                                                    }
       fclose($handle);       
       if($line == '') $line = '^^^';
                                      }
      return $line;
  }

  public static function isValidBk($obj)
    {
             if (!is_object($obj)) {
                echo "The last block in the Blockchain of the remote node is corrupted.\n"; 
                return false; 
             }    
             if(substr($obj->hash, 0, self::Difficulty) != str_repeat("0", self::Difficulty))
             {
               echo "Block with index $obj->index, hash :".$obj->hash." does not have enough 0 prefixed. Requires ".self::Difficulty." zeros! \n";
               return false; //if current block no of 0 prefix does not match bc no of 0 reqd
             }

            if ($obj->hash != $obj->calculateHash()) {
                echo "Running calculateHash(), detected invalid block with index $obj->index from remote node ! \n";
                return false;
                                                      }
            return true;
    }

 public static function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

 public static function isBcExist(){
      clearstatcache(); //file stat is cache so sometimes file_exists return false negative
      
      if(file_exists(self::BlockPath) )
         return true;
      else
         return false;
 }

 public static function isUfExist(){ //is user.dat exist
      clearstatcache(); //file stat is cache so sometimes file_exists return false negative
      
      if(file_exists(user::UserPath) )
         return true;
      else
         return false;
 }
 
public function broadcastNewBk($newBkIdx)
 {
     $voteYes = 0;
     $nodeList = self::getRemoteNodes();
     $objstr = serialize($this->chain[$newBkIdx]);
     $objstr = urlencode($objstr);
     foreach ($nodeList as $node) {
        
       $response = self::callRemote($node, 'php/reply2peers.php?function=newBkNotify&type=q&blk='.$objstr.'&securityhash=', 1);  
       
       if(trim($response) == 'YES')
         $voteYes++ ;    
       else echo '<br>Node <b>['.$node.']</b> rejected new block addition because <br>'.$response.'<br><br>';
                                  }
         
     $result = number_format( ($voteYes/sizeof($nodeList)) * 100, 0 );
     echo 'Peer Nodes Consensus polling result:<b>'.$result.'%</b>';
     if($result >= 50) {
        foreach ($nodeList as $node) {  //tell each nodes to insert the new block
          $response = self::callRemote($node, 'php/reply2peers.php?function=newBkNotify&type=i&blk='.$objstr.'&securityhash=', 1); 
                                      }
        return true;  //shd return true
                        }    
     else return false;
 }
 
 public static function getRemoteNodes()
 {
  clearstatcache(); //file stat is cache so sometimes file_exists return false negative
  
  if(!file_exists(self::NodesPath)){
    echo "cannot find file ".self::NodesPath.", so skip looking for remote peer nodes \n";
    return false;                   }
                            
   $str = file_get_contents(self::NodesPath);
   $checkList = explode("\n", trim($str));
   $nodeList = [];
   foreach($checkList as $item)              {
        $itemDetails = explode(",", trim($item));
     if($itemDetails[1] == 'trustworthy') 
       array_push($nodeList,$itemDetails[0]);
                                              }
   //print_r($nodeList) ;                                          
   return $nodeList;  
       
 }
     
 public static function downgradeRemoteNode($node)
 {
  clearstatcache(); //file stat is cache so sometimes file_exists return false negative
  
  if(!file_exists(self::NodesPath)){
    echo 'cannot find file';
    return;                          }
                            
   $str = file_get_contents(self::NodesPath);
   $checkList = explode("\n", trim($str));
   $file = fopen(self::NodesPath, 'w');
   foreach($checkList as $item)              {
        $itemDetails = explode(",", trim($item));
        if($itemDetails[0] == $node) $itemDetails[1] = 'doubted';
        fwrite($file, $itemDetails[0].','.$itemDetails[1]."\n");
                                              }
    fclose($file);
  
       
 }
public static function whoAmI(){
    clearstatcache(); //file stat is cache so sometimes file_exists return false negative
    
    if (!file_exists(self::MePath)){
    echo 'cannot find file';
    return null ;                 }
    
    $meNode = file_get_contents(self::MePath);
    
    return trim($meNode);
 }
 
  public static function myNode(){
    clearstatcache(); //file stat is cache so sometimes file_exists return false negative
    
    if (!file_exists(self::MePath)){
    echo 'cannot find file';
    return null ;                 }
    
    $meNode = file_get_contents(self::MePath);
    
    return trim($meNode);
 }
 
public static function callRemote($p1, $p2, $p3){
  $hash = bin2hex(random_bytes(16));
  $request_url = $p1.$p2.$hash;
 
  for ($i=0; $i < self::RemoteCallRetries; ++$i) { //tries x times then give up if still draw blank
    sleep($i);
    $ret = self::get_url($request_url, $p3);
    if($ret == false) continue;
    else break;
     
                                               }
                                                 
  return $ret ;
}                               

public static function get_url($request_url, $wait) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $request_url);
  curl_setopt($ch, CURLOPT_POST,0);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, $wait);
  
  $response = curl_exec($ch);
  //var_dump($response);  //must be careful as anything echo from peer reply is captured in $response b4 any YES/NO
  if (curl_error($ch)) $ret = false;
  else $ret = $response;
  curl_close($ch);
  return $ret;
}
 
 public static function getUserFromBk($bk){
      $objArr =   explode(",",$bk->data);  //break txn data (joined by ,)up to retrieve 1st field ie fuser
      $user = array("fuser"=>$objArr[0], "tuser"=>$objArr[1], "fethaddr"=>$objArr[2],"tethaddr"=>$objArr[3],"famount"=>$objArr[4], "updHash"=>$objArr[5]);
      return $user;
      
 }
 
 public function getChainCount(){
     return count($this->chain);
 }
 
 public function getBkData($idx){
     return $this->chain[$idx]->data;
 }
 public function getBkIndex($idx){
     return $this->chain[$idx]->index;
 }
 
 public static function verifySign($obj, $user, $uf = user::UserPath){
   
  if($user['fuser'] == 'SUPER USER')  //get from trusted user.setup
    $pubKey = self::$superuser_pubkey;
  else
   $pubKey =   user::getUserinfo($user['fuser'], 'pubkey', $uf); //retrieve fuser pubkey, fuser aka $objArr[0]
   
  $pubKey =   user::cleanStr($pubKey); 
  
  $sign   =   user::cleanStr($obj->sign);
  $data = $user['fuser'].$user['tuser'].user::cleanStr($user['fethaddr']).user::cleanStr($user['tethaddr']).$user['famount'].$obj->timestamp; //user txn data that is signed is embedded in the block
  
  $r = openssl_verify($data, hex2bin($sign), $pubKey, OPENSSL_ALGO_SHA256);  
  if ($r <> 1) 
    return false;
  else
    return true;
} 
 
 public static function setSuperuserPubkey(){
    self::$superuser_pubkey =  user::cleanStr(user::getUserinfo('SUPER USER', 'pubkey', user::UserSetupPath)); 
     
 }
 
}  // end of class blockchain