<?php
namespace App\Libraries\TaskQueue\Demo01;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Sunra\PhpSimple\HtmlDomParser;


class Receive {

    private $conn;
    private $channel;

    private $msg_data;
    private $movie_content;


    //用于调度消息处理
    private function processMsg() {
        echo "接收到{$this->msg_data}";
        $this->fetchData();
        $this->checkUnique();
    }

    //获取网页内容
    private function fetchData() {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $this->msg_data);
        $this->movie_content = mb_convert_encoding($res->getBody(), "utf8", "gbk");
    }

    private function checkUnique() {
        $url = $this->msg_data;
        $movie = new \App\Models\Movie;
        $count = $movie->where('url', $url)->count();
        if($count>0) {
            echo "此页面已有记录" . PHP_EOL;
        } else {
            $this->parseContent();
        }
    }

    //解析html
    private function parseContent() {
        $dom = HtmlDomParser::str_get_html($this->movie_content);
        $data['url'] = $this->msg_data;
        $data['name'] = $dom->find('.co_area2 .title_all', 3)->plaintext;
        $data['desc'] = $dom->find('.co_content8', 0)->plaintext;
        $data['link'] = $dom->find('.co_content8 table a', 0)->plaintext;
        print_r($data);
        $movie = new \App\Models\Movie;
        $movie->url = $data['url'];
        $movie->name = $data['name'];
        $movie->desc = $data['desc'];
        $movie->link = $data['link'];
        $movie->save();
    }

    function __construct() {
        $this->setup();
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume('movie_queue', '', false, false, false, false, function($msg){
            $this->msg_data = $msg->body;
            $this->processMsg();
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        });

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    function __destruct() {
        $this->channel->close();
        $this->conn->close();
    }

    private function setup() {
        $this->conn = new AMQPStreamConnection('localhost', 5672, 'jack', 'jack');
        $this->channel = $this->conn->channel();
    }

}