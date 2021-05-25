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
    private $chain = [];
    
    public function __construct($alternate_bc = null)
    {
       
       
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
        
      $this->difficulty = 4;
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
        
        $this->writeBks2file();
    }

    /**
     * Mines a block.
     */
    public function mine($block)
    {
        $time_start = self::microtime_float();
        while (substr($block->hash, 0, $this->difficulty) !== str_repeat("0", $this->difficulty)) {
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
    public function isValid()
    {
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i-1];
            
            if(substr($currentBlock->hash, 0, $this->difficulty) != str_repeat("0", $this->difficulty))
             {
               var_dump("Block with index $currentBlock->index, hash :".$currentBlock->hash." does not have enough 0 prefixed. Requires $this->difficulty zeros!");
               return false; //if current block no of 0 prefix does not match bc no of 0 reqd
             }
            if ($currentBlock->previousHash != $previousBlock->hash) {
                var_dump("Block with index $currentBlock->index, previousHash :".$currentBlock->previousHash." does not match previous Block with index $previousBlock->index, hash :".$previousBlock->hash.' .') ;
                return false;
            }
            if ($currentBlock->hash != $currentBlock->calculateHash()) {
                var_dump(' calculateHash() detected invalid block with index :'.$currentBlock->index. ' ');
                return false;
            }

        }
        echo 'bc validated ok ';
        return true;
    }
    
 private function loadBkchainFile($f)
    {
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

 private function writeBks2file(){
     
     if(file_exists(self::BlockPath) ) 
        rename ( self::BlockPath , self::BlockPath.'.old') ;
        
     for ($i = 0; $i < count($this->chain); $i++) {
          $objData = serialize($this->chain[$i])."\n" ;
          $myfile = file_put_contents(self::BlockPath, $objData.PHP_EOL , FILE_APPEND | LOCK_EX);
                                                  }
   }
   
 public function getCountBk() {
     return count($this->chain);
 }
 
 public static function peer2peerState($bkRemote){
     $bkLocal = trim(self::getLastBk());
     $bkRemote = trim($bkRemote);
     
     //if($bkLocal == $bkRemote)
     if(strcmp($bkLocal,$bkRemote) == 0)
        return array(1,0);  //we r sync
     else
       {  
          $objLocal = unserialize($bkLocal);
          $objRemote = unserialize($bkRemote); 
          if(self::isValidBk($objRemote)){
             if($objLocal->index > $objRemote->index )
               return array(2,0); // local node index greater than dest
              else
               return array(3, $objRemote->index); //dest node index greater than local
          }
          else   return array(4, 0);  //dest is corrupted block 
          
       }       
 }
 
 public static function getLastBk(){
    $line = '';
    if(file_exists(self::BlockPath) ){
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
                                      }
      return $line;
  }

  public static function isValidBk($obj)
    {

            if ($obj->hash != $obj->calculateHash()) {
                return false;
            }
        return true;
    }

 public static function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


     
}
