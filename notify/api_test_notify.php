<?php

 file_put_contents("api_notify.txt",date("Y-m-d H:i:s",time()).'--报文--'.file_get_contents('php://input', 'r')."--"."\r\n",FILE_APPEND | LOCK_EX);
 echo '0000';