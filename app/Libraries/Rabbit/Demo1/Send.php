<?php
namespace App\Libraries\TaskQueue\Demo01;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class Send {

    private $conn;
    private $channel;

    function __construct() {
        $this->setup();

        $this->channel->queue_declare('movie_queue', false, true, false, false);

        $msg = new AMQPMessage("test", ['delivery_mode'=>AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($msg, '', 'movie_queue');
        echo 'send';
    }

    private function setup() {
        $this->conn = new AMQPStreamConnection('localhost', 5672, 'jack', 'jack');
        $this->channel = $this->conn->channel();
    }

    function __destruct() {
        $this->channel->close();
        $this->conn->close();
    }

}