<?php
/**
 * Created by PhpStorm.
 * User: isacsandin
 * Date: 30/10/2019
 * Time: 21:42
 */

error_reporting(E_ERROR | E_PARSE);

class EchoSocketServer
{
    private $except;
    private $server;

    private $buffers = [];
    private $writable = [];
    private $readable = [];
    private $connections = [];

    public function __construct($uri)
    {
        $this->server = stream_socket_server($uri);
        stream_set_blocking($this->server, false);

        if ($this->server === false) {
            exit(1);
        }
    }

    public function run()
    {
        while (true) {
            $this->except = null;
            $this->writable = $this->connections;
            $this->readable = $this->connections;

            // Adiciona a stream do servidor no array de streams de somente leitura,
            // para que consigamos aceitar novas conexões quando disponíveis;
            $this->readable[] = $this->server;

            // Em um looping infinito, a stream_select() retornará quantas streams foram modificadas,
            // a partir disso iteramos sobre elas (tanto as de escrita quanto de leitura), lendo ou escrevendo.
            // A stream_select() recebe os arrays por referência e ela os zera (remove seus itens) até que uma stream muda de estado,
            // quando isso acontece, a stream_select() volta com essa stream para o array, é nesse momento que conseguimos iterar escrevendo ou lendo.
            if (stream_select($this->readable, $this->writable, $this->except, NULL, NULL) > 0) {
                $this->readFromStreams();
                $this->writeToStreams();
                $this->release();
            }
        }
    }

    private function readFromStreams()
    {
        foreach ($this->readable as $stream) {
            // Se essa $stream é a do servidor, então uma nova conexão precisa ser aceita;
            if ($stream === $this->server) {
                $this->acceptConnection($stream);

                continue;
            }

            // Uma stream é um resource, tipo especial do PHP,
            // quando aplicamos um casting de inteiro nela, obtemos o id desse resource;
            $key = (int) $stream;

            // Armazena no nosso array de buffer os dados recebidos;
            if (isset($this->buffers[$key])) {
                $this->buffers[$key] .= fread($stream, 4096);
            } else {
                $this->buffers[$key] = '';
            }
        }
    }

    private function writeToStreams()
    {
        foreach ($this->writable as $stream) {
            $key = (int) $stream;
            $buffer = $this->buffers[$key] ? $this->buffers[$key] : null;

            if ($buffer && $buffer !== '') {
                // Escreve no cliente o que foi recebido;
                $bytesWritten = fwrite($stream, "Server says: {$this->buffers[$key]}", 2048);

                // Imediatamente remove do buffer a parte que foi escrita;
                $this->buffers[$key] = substr($this->buffers[$key], $bytesWritten);
            }
        }
    }

    private function release()
    {
        foreach ($this->connections as $key => $connection) {
            // Quando uma conexão é fechada, ela entra no modo EOF (end-of-file),
            // usamos a feof() pra verificar esse estado e então devidamente executar fclose().
            if (feof($connection)) {
                fwrite(STDERR, sprintf("Client [%s] closed the connection; \n", stream_socket_get_name($connection, true)));

                fclose($connection);
                unset($this->connections[$key]);
            }
        }
    }

    private function acceptConnection($stream)
    {
        $connection = stream_socket_accept($stream, 0, $clientAddress);

        if ($connection) {
            stream_set_blocking($connection, false);
            $this->connections[(int) $connection] = $connection;

            fwrite(STDERR, sprintf("Client [%s] connected; \n", $clientAddress));
        }
    }
}

$server = new EchoSocketServer('tcp://127.0.0.1:7181');
$server->run();
