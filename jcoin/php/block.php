<?php
class Block
{
    public $nonce;
   
    
    public function __construct($index, $timestamp, $data, $signature = null, $previousHash = null)
    {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->sign = $signature;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
        $this->nonce = 0;
    }

    public function calculateHash()
    {
        return hash("sha256", $this->index.$this->previousHash.$this->timestamp.((string)$this->data).$this->sign.$this->nonce);
    }
}
