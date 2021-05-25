<?php
const MePath    = './dat/me.dat';

if (!file_exists(MePath))
  file_put_contents(MePath,explode("/",trim($_SERVER['REQUEST_URI']))[1]);
                          
include 'html/form.html';

?>