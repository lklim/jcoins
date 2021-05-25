<?php
$file = '../Application.log';
$count = 10;

error_reporting(0);

$buf_line = [];

if(!file_exists($file))
touch($file);

echo ">>>>>>>>>>>>  Start web tail -f $file ".date("d-m-Y H:i:s"). '<br><br>';

flushing();

$time_start = microtime_float();
$time_current = microtime_float();
$time_change = microtime_float();

while($time_current - $time_start < 29)   {
    if($time_current - $time_change > 1)       {
        display_tail($file , $count, $buf_line);
        $time_change = microtime_float();      }
        $time_current = microtime_float(); }
                                            
 echo '<br><br>';
echo "End web tail -f $file ".date("d-m-Y H:i:s").'  <<<<<<<<<<<<'.'<br>'; 

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function display_tail($file, $n, &$buf_line){  //need to use & prefix if want to pass by reference
   $line = '';
   $arrLines = array(); 


$f = fopen($file, 'r');
$cursor = -1;

fseek($f, $cursor, SEEK_END);
$char = fgetc($f);

while ($char === "\n" || $char === "\r") {
    fseek($f, $cursor--, SEEK_END);
    $char = fgetc($f);
} 

for ($i=0; $i<$n; ++$i)                        { 

while ($char !== false && $char !== "\n" && $char !== "\r") {
    
    $line = $char . $line;
    fseek($f, $cursor--, SEEK_END);
    $char = fgetc($f);
                                                            }
    array_push($arrLines, $line);  
    $line = '';
    fseek($f, $cursor--, SEEK_END); $char = fgetc($f);
                                                  }
                        
    if($arrLines !== $buf_line ) { 
    //if(array_diff($arrLines,$buf_line) ) {
    $buf_line  = $arrLines;
    $max = count($buf_line) - 1;
    for($i = $max ; $i >= 0; --$i)    {
      if($buf_line[$i])
        echo $buf_line[$i].'<br>';    }
   
    flushing(); 
                                  }
                                 
    fclose($f); 
}

function flushing(){
  try {
        flush();
        ob_flush();
        } catch (Exception $e) {
         $x = $e->getMessage();   
        }
}



?>
