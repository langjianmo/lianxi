<?php
 set_time_limit(0);
 $host="1.14.104.35";
 $port=2348;
 //创建一个socket
 $socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP)or die("cannot create socket\n");
 $conn=socket_connect($socket,$host,$port) or die("cannot connect server\n");
 if($conn){echo "client connect ok!";}
 socket_write($socket,"hello world!") or die("cannot write data\n");
 $buffer=socket_read($socket,1024,PHP_NORMAL_READ);
 if($buffer){
  echo "response was:".$buffer."\n";
 }
 socket_close($socket);
?>


