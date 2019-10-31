<?php
/**
 * Created by PhpStorm.
 * User: isacsandin
 * Date: 30/10/2019
 * Time: 21:41
 */

// Queremos que o PHP reporte apenas erros graves, estamos explicitamente ignorando warnings aqui.
// Warnings que, por sinal, acontecem bastante ao se trabalhar com sockets.
error_reporting(E_ERROR | E_PARSE);

// Inicia o servidor na porta 7181
$server = stream_socket_server('tcp://127.0.0.1:7181', $errno, $errstr);

// Em caso de falha, para por aqui.
if ($server === false) {
    fwrite(STDERR, "Error: $errno: $errstr");

    exit(1);
}

// Sucesso, servidor iniciado.
fwrite(STDERR, sprintf("Listening on: %s\n", stream_socket_get_name($server, false)));

// Looping infinito para "escutar" novas conexões
while (true) {
    // Aceita uma conexão ao nosso socket da porta 7181
    // O valor -1 seta um timeout infinito para a função receber novas conexões (socket accept timeout) e isso significa que a execução ficará bloqueada aqui até que uma conexão seja aceita;
    $connection = stream_socket_accept($server, -1, $clientAddress);

    // Se a conexão foi devidamente estabelecida, vamos interagir com ela.
    if ($connection) {
        fwrite(STDERR, "Client [{$clientAddress}] connected \n");

        // Lê 2048 bytes por vez (leitura por "chunks") enquanto o cliente enviar.
        // Quando os dados não forem mais enviados, fread() retorna false e isso é o que interrompe o loop.
        // fread() também retornará false quando o cliente interromper a conexão.
        while ($buffer = fread($connection, 2048)) {
            if ($buffer !== '') {
                // Escreve na conexão do cliente
                fwrite($connection, "Server says: $buffer");
            }
        }

        // Fecha a conexão com o cliente
        fclose($connection);
    }
}