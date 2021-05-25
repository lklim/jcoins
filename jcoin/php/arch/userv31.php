<?php
class user
{
    public $pubKey;
    const FilePath = '../dat/user.dat';
    private $chain = [];
    
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
          $this->balance = 1000000;
          $this->update_index = 0;
          $this->update_time = strtotime("now");
          $this->update_Hash = self::calculateHash($this);
        }
        $this->addUser2file();
    }
    
   private function addUser2file(){
     $objData = serialize($this)."\n" ;
     $myfile = file_put_contents(self::FilePath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
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
        
 
 $handle = fopen(self::FilePath, "r");
 
 if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n","",$line);
        if($line)
           array_push($chain, unserialize($line));
    }
    fclose($handle);
} else {
    die("Unable to open file!");
} 
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
    
   if(file_exists(self::FilePath) )                      
      rename ( self::FilePath , self::FilePath.'.old' ) ;
     for ($i = 0; $i < count($chain); $i++) {
          $objData = serialize($chain[$i])."\n" ;
          $myfile = file_put_contents(self::FilePath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
                                                  }  
 
 
    }
    return $updHash1.'&'.$updHash2;
 }   
 
 
private function writeBks2file(){
     rename ( self::BlockPath , self::BlockPath.old ) ;
     for ($i = 0; $i < count($this->chain); $i++) {
          $objData = serialize($this->chain[$i])."\n" ;
          $myfile = file_put_contents(self::BlockPath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
                                                  }
   }
   
public static function getUserinfo($user, $detail)  {
     
 $chain = [];
 
 $handle = fopen(self::FilePath, "r");
 
 if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n","",$line);
        array_push($chain, unserialize($line));
    }
    fclose($handle);
} else {
    die("Unable to open file!");
} 
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
        break;
} 
    }    
                        }
   return $ret;  
 
 } 


public static function isNewUser($newuser) :bool {
     
 
 
 if(! file_exists(self::FilePath) ) //if file doesn't exist implicitly it is a new user
    return true;
    
 $handle = fopen(self::FilePath, "r");

 if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n","",$line);
        array_push($chain, unserialize($line));
        
    }
    fclose($handle);
} else { //if file doesn't exist implicitly it is a new user
    return true;
} 
 
foreach ($chain as $obj) {
  //if (isset($ob->fname))
   //if (isset($ob->lname))
   //if (property_exists($ob, 'fname')) 
   if(!$obj==null)
    if(strtoupper($newuser) == $obj->fname.$obj->lname){
        return false; 
    }    
                        }
   return true;  
 
 }
 
 public static function getUserlist ()
 {
    
 $handle = fopen(self::FilePath, "r");
 $userlist = null;
 if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n","",$line);
        if($line){
           $obj = unserialize($line);
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
 
 
}


?>