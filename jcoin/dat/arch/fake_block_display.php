<?php


class Block
{
    public $nonce;
    const Difficulty = 5;
    
    public function __construct($index, $timestamp, $data, $signature = null, $previousHash = null)
    {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->sign = $signature;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
        $this->nonce = 0;
       $this->mine();
    }

    public function calculateHash()
    {
        return hash("sha256", $this->index.$this->previousHash.$this->timestamp.((string)$this->data).$this->sign.$this->nonce);
    }

public function push($block)
    {
        //$block->previousHash = $this->getLastBlock()->hash;
        $this->mine();
        //array_push($this->chain, $block);
        return $block->index;
       
    }

 public function mine()
    {
        $time_start = self::microtime_float();
        while (substr($this->hash, 0, self::Difficulty) !== str_repeat("0", self::Difficulty)) {
            $this->nonce++;
            $this->hash =  $this->calculateHash();
        }
        $time_end = self::microtime_float();
        $time = $time_end - $time_start;
        echo "Block mined: ".$this->hash." in $time seconds.<br><br>";
       //return $this->hash;
    }
 public static function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

}  //end class fakeBlock




//Get below 2 values from the last block of the latest blockchain.dat
$prevHash = "00000be6143f1654e64d37fd06b32ae1ed7d6abd96c0159bc99880aefb82085c"; //hash of the last block
$last_block_index = 7; //last block index + 1 



$fuser = 'JOHN LIM'; $tuser = 'JENNY TEO'; $fethaddr = "0x4dce412a422b7573a8cb46a3103a6caff4c5152c";
$tethaddr = "0xa3b08f204484fa15e2448d9d8593a085e25a004e"; $famount = 20000;  $updHash = '1234567';
$txntimestamp = (time()*1000); 

$data = $fuser.$tuser.$fethaddr.$tethaddr.$famount.$txntimestamp;
$txn = $fuser.','.$tuser.','.$fethaddr.','.$tethaddr.','.$famount.','.$updHash;

//john lim keys
$privpem = "-----BEGIN RSA PRIVATE KEY-----
MIIBPQIBAAJBAPXNJgBrfbXfO2xR09UrQdAZgQrXFvduepZvosUliYhX9BZPJ1fk
YzmMhzRduKljGeoRYcwj4XxyBxX8MMtPTxcCAwEAAQJBAKgU++qHlrQajZj5r6By
bxOtjQdro+HZI2zhs+2aSJz5KO3qH8cAKGYlr2iw854TR2+Q3UUPidVxB5uObXT4
jSECIQD+dlKT/NMW8PNLme4889wUS7e9S921+zZRzjP0lwk90QIhAPdJbT4UqbaW
z9h8WKqP04w1avmQf5L9gyzjcDxpzHBnAiEA4Ld1baNUd2oKMbWaotFohbPoa49Y
GKHk8pF7aIEJdEECIQC/2qn0xlc9oBg1n5OzEM9SMoeChEdWJXXGN9b2KCdC9QIh
APjNPe+7m8pCDrC3FKZ97oDWb0klbVNIajxWtFqvB0BZ
-----END RSA PRIVATE KEY-----";

$public_key = "-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAPXNJgBrfbXfO2xR09UrQdAZgQrXFvdu
epZvosUliYhX9BZPJ1fkYzmMhzRduKljGeoRYcwj4XxyBxX8MMtPTxcCAwEAAQ==
-----END PUBLIC KEY-----
" ;

/* jack lim  keys
$privpem =  "-----BEGIN RSA PRIVATE KEY-----
MIIBOAIBAAJAcg39j6KRECtR3BF21sW6uAllf6iZ5mOKEPYBuV2I0gl8F+/1R60F
SEgZ1gzudBoltoC9UBQnaZ2Svxqi6v/bNwIDAQABAkBaEWZNiYmZBTyt1zTLwnYD
Y5g9yb1PTQf5AOy3n3+urTTiEPHo6Y4BUQop5/AZzseLZzxQw2OeZRW3TEu6LpeB
AiEArZzN1/CE7nmIQObt5jk7atNd1QLxB23rcnuHtPOgcyECIQCoLdcn4xXP8KhD
4dMYAq7/7eVicJNCeyIktsHvkQxbVwIgWBb45vl2KZ5mwS+rRCaD/HcU5DEi5Tcj
wSnmuKzpG6ECIGgI+2jMPCkG6UAcyTW4K0NciaKMmzvr6eImP/APnUI/AiAwc2AB
2eDcIKrRONTvOuSleDtPATPJPkSJc2R3f5fQPw==
-----END RSA PRIVATE KEY-----" ;

$public_key = "-----BEGIN PUBLIC KEY-----
MFswDQYJKoZIhvcNAQEBBQADSgAwRwJAcg39j6KRECtR3BF21sW6uAllf6iZ5mOK
EPYBuV2I0gl8F+/1R60FSEgZ1gzudBoltoC9UBQnaZ2Svxqi6v/bNwIDAQAB
-----END PUBLIC KEY-----";

*/
echo "Generated fake public key with length: ".strlen($public_key)."<br>";
echo dirtyStr($public_key); //echo out the fake pub key to insert into remote user.dat
echo '<br>';

$pkeyid = openssl_pkey_get_private($privpem);

// compute signature
openssl_sign($data, $sign, $pkeyid,OPENSSL_ALGO_SHA256);



// free the key from memory
//openssl_free_key($pkeyid);
 
$ok = openssl_verify($data, $sign, $public_key, OPENSSL_ALGO_SHA256);

echo 'ret: '.$ok.'<br>';


$sign = bin2hex($sign);



//$newBk_index = $jCoin->push(new Block($jCoin->getCountBk(), $txntimestamp, $txn, $sign ));  
echo serialize(new Block($last_block_index, $txntimestamp, $txn, $sign,$prevHash ));


 function  dirtyStr($txt) {
   $txt = str_replace("&",'^36^' ,$txt);
   $txt = str_replace("*",'^37^' ,$txt);
   $txt = str_replace("(",'^38^' ,$txt);
   $txt = str_replace(")",'^39^' ,$txt);
   $txt = str_replace("_",'^40^' ,$txt);
   $txt = str_replace("+",'^41^' ,$txt);
   $txt = str_replace("\n",'^42^' ,$txt);
   return $txt;
}


?>
