<?php
/**
 * Created by PhpStorm.
 * User: isacsandin
 * Date: 31/10/2019
 * Time: 01:52
 */

$host    = "127.0.0.1";
$port    = 7181;

// create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create socket\n");

// connect to server
$result = socket_connect($socket, $host, $port) or die("Could not connect to server\n");

while(true) {
    $message = "Hello Server ".rand(1000,7000);
    echo "Message To server: ".$message . PHP_EOL;

    // send string to server
    socket_write($socket, $message, strlen($message)) or die("Could not send data to server\n");

    // get server response
    $result = socket_read($socket, 1024) or die("Could not read server response\n");
    echo "Reply From Server: " . $result . PHP_EOL;

    sleep(5);
}


// close socket
socket_close($socket);