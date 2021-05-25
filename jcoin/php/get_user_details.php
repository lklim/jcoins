<?php

include("user.php");


$user = $_POST['user'];
$detail = $_POST['detail'];


$userinfo =  user::getUserinfo($user, $detail);
 
echo '<input type="hidden" id="id_user_info" name="userinfo" value="'.$userinfo.'">';
 
?>