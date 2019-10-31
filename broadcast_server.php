<?php
/**
 * Created by PhpStorm.
 * User: isacsandin
 * Date: 31/10/2019
 * Time: 01:07
 */

error_reporting(E_ERROR | E_PARSE);

$address = '127.0.0.1';
$port = 7181;

// create a streaming socket, of type TCP/IP
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// set the option to reuse the port
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

// "bind" the socket to the address to "localhost", on port $port
// so this means that all connections on this port are now our resposibility to send/recv data, disconnect, etc..
socket_bind($sock, $address, $port);

// start listen for connections
socket_listen($sock);

// create a list of all the clients that will be connected to us..
// add the listening socket to this list

$clients = array($sock);

while (true) {
    // create a copy, so $clients doesn't get modified by socket_select()
    $read = $clients;

    // get a list of all the clients that have data to be read from
    // if there are no clients with data, go to next iteration
    if (socket_select($read, $write = NULL, $except = NULL, NULL) < 1)
        continue;

    // check if there is a client trying to connect
    if (in_array($sock, $read)) {
        // accept the client, and add him to the $clients array
        $clients[] = $newsock = socket_accept($sock);

        // send the client a welcome message
        socket_write($newsock, "There are ".(count($clients) - 1)." client(s) connected to the server\n");

        socket_getpeername($newsock, $ip, $port);
        echo "New client connected: {$ip}\n";

        // remove the listening socket from the clients-with-data array
        $key = array_search($sock, $read);
        unset($read[$key]);
    }

    // loop through all the clients that have data to read from
    foreach ($read as $read_sock) {
        // read until newline or 1024 bytes
        // socket_read while show errors when the client is disconnected, so silence the error messages
        $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

        socket_getpeername($read_sock, $ip_c, $port_c);

        // check if the client is disconnected
        if ($data === false) {
            // remove client for $clients array
            $key = array_search($read_sock, $clients);
            unset($clients[$key]);
            echo "client disconnected.\n";
            // continue to the next client to read from, if any
            continue;
        }

        // trim off the trailing/beginning white spaces
        $data = trim($data);

        // check if there is any data after trimming off the spaces
        if (!empty($data)) {

            // send this to all the clients in the $clients array (except the first one, which is a listening socket)
            foreach ($clients as $send_sock) {

                // if its the listening sock or the client that we got the message from, go to the next one in the list
                if ($send_sock == $sock || $send_sock == $read_sock)
                    continue;

                // write the message to the client -- add a newline character to the end of the message

                socket_write($send_sock, "[{$ip_c}:{$port_c}] says: {$data} \n");

            } // end of broadcast foreach

        }

    } // end of reading foreach
}

// close the listening socket
socket_close($sock);