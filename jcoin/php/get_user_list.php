<?php


include("user.php");


$userinfo =  user::getUserlist();
 
echo '<input type="hidden" id="id_user_info" name="userinfo" value="'.$userinfo.'">';
 
?>