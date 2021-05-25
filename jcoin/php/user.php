<?php

class user
{
    public $pubKey;
    const UserPath = '../dat/user.dat';
    const UserSetupPath = '../dat/setup.user';
    public const SuperuserMax = 1000000; //At Genesis the start amount of all Jcoins 
    private static $updated_chain = [];
    
    public function __construct($fname, $lname, $wallet_addr, $pubKey, $balance = 0, $update_index = null, $update_hash = null, $update_time = null)
    {
        
        $this->create_time = strtotime("now");
        $this->fname = strtoupper($fname);
        $this->lname = strtoupper($lname);
        $this->wallet_addr = $wallet_addr;
        $this->pubKey = $pubKey;
        $this->balance = $balance;
        $this->update_index = $update_index;
        $this->update_time = $update_time;
        $this->update_Hash = $update_hash;
        
        if($this->isSuper()){
          $this->balance = self::SuperuserMax;
          $this->update_index = null;
          $this->update_time = null;
          $this->update_Hash = null;
          
          $jCoin = new BlockChain();
          $jCoin->writeBks2file();
        }
        $this->insertUser2file();
    }
    
   private function insertUser2file(){
     $objDataStr = serialize($this)."\n" ;
     $myfile = file_put_contents(self::UserPath, $objDataStr.PHP_EOL , FILE_APPEND | LOCK_EX);
     if($this->isSuper()) copy(self::UserPath, self::UserSetupPath);
   }
   
   private function isSuper() : bool {
     if($this->fname.$this->lname == "SUPERUSER") 
        return true;
     else 
       return false;
   }
   
   public function cleanStr($txt) {
   $txt = str_replace('^36^' ,"&",$txt);
   $txt = str_replace('^37^' ,"*",$txt);
   $txt = str_replace('^38^' ,"(",$txt);
   $txt = str_replace('^39^' ,")",$txt);
   $txt = str_replace('^40^' ,"_",$txt);
   $txt = str_replace('^41^' ,"+",$txt);
   $txt = str_replace('^42^' ,"\n",$txt);
   return $txt;
}


    public static function validate_balance($fuser, $amt) : bool
    {
        if(user::getUserinfo($fuser, 'bal') - $amt < 0) return false;
        else return true;
    }
    public function get_user_update_hash()
    {
        return [$this->update_Hash];
    }
    
    public static function calculateHash($obj)
    {
        return hash("sha256", $obj->update_time.$obj->update_index.$obj->balance.$obj->pubKey.$obj->wallet_addr.$obj->lname.$obj->fname.$obj->create_time);
        
    }

public static function update_user($fuser, $tuser, $amt, $update_index, $update_time)
    {
        
 $chain = [];
 $chain = self::loadUserlistFile(self::UserPath);  
 $ret = null;
 
foreach ($chain as $obj) {
 
   if(!$obj==null)
    if(strtoupper($fuser) == $obj->fname.' '.$obj->lname){
       $obj->balance =  $obj->balance - $amt; 
       $obj->update_index = $update_index;
       $obj->update_time  = $update_time;
       $obj->update_Hash = user::calculateHash($obj);
       $updHash1 = $obj->update_Hash;
      
    }  
    else if(strtoupper($tuser) == $obj->fname.' '.$obj->lname){
       $obj->balance =  $obj->balance + $amt; 
       $obj->update_index = $update_index;
       $obj->update_time  = $update_time;
       $obj->update_Hash = user::calculateHash($obj);
       $updHash2 = $obj->update_Hash;
                                                             }

    }
     self::$updated_chain = $chain;
     
    return $updHash1.'&'.$updHash2;
 }   
 
 
public static function writeUsers2file(){
    
     if(file_exists(self::UserPath) )                      
      rename ( self::UserPath , self::UserPath.'.old' ) ;
     for ($i = 0; $i < count(self::$updated_chain); $i++) {
          $objData = serialize(self::$updated_chain[$i])."\n" ;
          $myfile = file_put_contents(self::UserPath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
                                                  }
   }
   
public static function getUserinfo($user, $detail, $uf = self::UserPath )  {
     
 $chain = [];
 $chain = self::loadUserlistFile($uf);
 if( $chain == null ) return null; 
 
 $ret = null;
 
 
foreach ($chain as $obj) {
   
   if(!$obj==null)
    if(strtoupper($user) == $obj->fname.' '.$obj->lname){
        switch ($detail) {
    case 'ethaddr':
        $ret = $obj->wallet_addr;
        break;
    case 'pubkey':
        $ret = $obj->pubKey;
        break;
    case 'bal':
        $ret = $obj->balance;
        break;
    default:
        $ret = 'something wrong cannot read obj!';
        var_dump('something wrong cannot read obj!');
        break;
} 
    }
    
                        }
   return $ret;  
 
 } 


public static function isNewUser($newuser) :bool {
    
 $userChain = [];
 clearstatcache(); //file stat is cache so sometimes file_exists return false negative
 
 if(!file_exists(self::UserPath) ) //if file doesn't exist implicitly it is a new user
    return true;
    
$chain = self::loadUserlistFile(self::UserPath); 
 
foreach ($chain as $obj) {
  //if (isset($ob->fname))
   //if (isset($ob->lname))
   //if (property_exists($ob, 'fname')) 
   if(!$obj==null)
    if(strtoupper($newuser) == $obj->fname.' '.$obj->lname){
        return false; 
    } 
    
                        }
   return true;  
 
 }
 
 public static function getUserlist ()
 {
     
 clearstatcache(); //file stat is cache so sometimes file_exists return false negative
 
 $handle = fopen(self::UserPath, "r");
 $userlist = null;
 if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n","",$line);
        if($line){
           $obj = unserialize($line);
           if($obj)
             if($userlist)
                 $userlist = $userlist.','.$obj->fname.' '.$obj->lname;
             else
                 $userlist = $obj->fname.' '.$obj->lname;
                  }
                                                }
    fclose($handle);
  } else 
    die("Unable to open file!");

   return $userlist;
 }
 
 public static function isUserlistValid($bc, $uf){
     
     $userChain = self::loadUserlistFile($uf);
     
     $index_found = false;
     
    foreach ($userChain as $peruser) { //iterate thru each user of user.dat
    
     if($peruser->fname.' '.$peruser->lname == 'SUPER USER' ) {
          if($peruser->update_Hash == null && $peruser->balance != self::SuperuserMax)  {
            echo "Super user balance: $peruser->balance when update hash is null ! Invalid balance, should be ".self::SuperuserMax." ! \n";
                return false;                                                            }
                                                              }
     else //for cases not super user check...
      if($peruser->update_Hash == null && $peruser->balance != 0) { //how can balance be changed if upd hash not set ?
            echo "User $peruser->fname $peruser->lname balance: $peruser->balance when update hash is null ! Invalid balance, should be zero ! \n";
            return false;                                           }
          
          
      if($peruser->update_index) {           //Now inspect cases that txn been carried out on this user
          //echo $bc->getChainCount().'c';
         
         if($peruser->update_Hash != user::calculateHash($peruser)) { //user data does not match it hash
            echo 'User '.$peruser->fname.' '.$peruser->lname .' has data that does not match its update hash'."\n";
            return false;                                            }
         for ($i = 1; $i < $bc->getChainCount(); $i++) { //i=0 genesis block no need to check
             //$objArr =   explode(",",$bc->getBkData($i));
             //echo $bc->getBkIndex($i).',';
            if($peruser->update_index ==  $bc->getBkIndex($i)) {
               $index_found = true; // user upd index found a corresponding index in bc
               //echo "bc hash is: $objArr[5] \n";
               if(! strpos($bc->getBkData($i), $peruser->update_Hash) ){ //if 1 case upd hash not same sound alarm 
                   echo 'Cannot find hash '.$peruser->update_Hash.' at index '.$bc->getBkIndex($i)."\n";
                   return false;                                } 
                                                         }
                                              } 
                                                 
         if($index_found) $index_found = false; //reset to check next user upd index
         else   {
                echo $peruser->fname.' '.$peruser->lname.' Cannot find index '.$peruser->update_index." in remote bc file \n";
                return false; //can't find a corresponding index the remote bc sound alarm
                 }
                                   }
                                       }
                                       
    return true ; //after exhaustive loop thru didn't encounter failure to find user.dat hashes in bc
                                       
 }
 
 public static function _isUserlistValid($bc, $uf){
     
     $userChain = self::loadUserlistFile($uf);
     $userArr = [];
     
     foreach ($userChain as $objx) { //iterate thru each user of user.dat 
     
      if($objx->update_index){ //if txn carried out on this user
         if(in_array($objx->update_Hash, array_column($userArr, 1))) //already captured in userArr so skip adding
          continue;
         $userArr[$objx->update_index][0] = $objx->update_Hash;
        foreach ($userChain as $objy) { //go into user.dat look for another user with same update_index
           if($objy->update_index)
            if($objx->update_index == $objy->update_index)
              if($objx->fname.$objx->lname != $objy->fname.$objy->lname ) { //ie different user 
                     $userArr[$objx->update_index][1] = $objy->update_Hash;
                     print_r($userArr[$objx->update_index]);
                     break;                                                } //found other user, stop check
                                                                           
                                    }
                                   
                               }
            
     
                        }
      foreach ($userArr as $key => $value)  //the $key is update index                
       for ($i = 1; $i < count($bc); $i++) { //i=0 genesis block no need to check
         if($key ==  $bc[i]->index) {
               $objArr =   explode(",",$bc[i]->data);
               $counter = 0;
                   for($j = 0; $j < 2; $j++) {
                      if(strpos( $objArr[5], $userArr[$key][i]) ) //if both hashes found in the block index i
                          $counter++;
                                             }
                if($counter <> 2)   
                   return false; //a user.dat pair of update hash not found in bc with index i
                                                    }
                                           } 
    return true ; //after exhaustive loop thru didn't encounter failure to find user.dat hashes in bc
 }
 
 private static function loadUserlistFile($uf){
     
 $userChain  = [];
 clearstatcache(); //file stat is cache so sometimes file_exists return false negative
 
 if (file_exists($uf))
    $handle = fopen($uf, "r");
 else {
    echo "Unable to open file $uf \n";
    return null;  
      }
 
 

    while (($line = fgets($handle)) !== false)   {
        $line = str_replace("\n","",$line);
        if($line)
         array_push($userChain, unserialize($line));
                                                   }
 
 fclose($handle);
 return $userChain;  
      
                                            }
 
 
  
 
} //end of class user



?>