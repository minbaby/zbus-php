<?php

namespace Rushmore\Zbus;

class Message
{
    public $status;          //integer
    public $method = "GET";
    public $url = "/";
    public $headers = [];
    public $body;


    public function removeHeader($name)
    {
        if (!array_key_exists($name, $this->headers)) {
            return;
        }
        unset($this->headers[$name]);
    }

    public function getHeader($name, $value = null)
    {
        if (!array_key_exists($name, $this->headers)) {
            return null;
        }
        return $this->headers[$name];
    }

    public function setHeader($name, $value)
    {
        if ($value === null) {
            return;
        }
        $this->headers[$name] = $value;
    }

    public function setJsonBody($value)
    {
        $this->headers['content-type'] = 'application/json';
        $this->body = $value;
    }

    public function __set($name, $value)
    {
        if ($value === null) {
            return;
        }
        $this->headers[$name] = $value;
    }


    public function __get($name)
    {
        if (!array_key_exists($name, $this->headers)) {
            return null;
        }
        return $this->headers[$name];
    }

    public function __toString()
    {
        return $this->encode();
    }


    public function encode()
    {
        $res = "";
        $desc = "unknown status";
        if ($this->status) {
            if (array_key_exists($this->status, HTTP_STATUS_TABLE)) {
                $desc = HTTP_STATUS_TABLE[$this->status];
            }
            $res .= sprintf("HTTP/1.1 %s %s\r\n", $this->status, $desc);
        } else {
            $res .= sprintf("%s %s HTTP/1.1\r\n", $this->method?:"GET", $this->url?:"/");
        }
        foreach ($this->headers as $key => $value) {
            if ($key == 'content-length') {
                continue;
            }
            $res .= sprintf("%s: %s\r\n", $key, $value);
        }
        $body_len = 0;
        if ($this->body) {
            $body_len = strlen($this->body);
        }
        $res .= sprintf("content-length: %d\r\n", $body_len);
        $res .= sprintf("\r\n");
        if ($this->body) {
            $res .= $this->body;
        }

        return $res;
    }

    public static function decode($buf, $start = 0)
    {
        $p = strpos($buf, "\r\n\r\n", $start);
        if ($p === false) {
            return [null, $start];
        }
        $head_len = $p - $start;

        $head = substr($buf, $start, $head_len);
        $msg = Message::decodeHeaders($head);
        $body_len = 0;
        if (array_key_exists('content-length', $msg->headers)) {
            $body_len = $msg->headers['content-length'];
            $body_len = intval($body_len);
        }
        if ($body_len == 0) {
            return [$msg, $p + 4];
        }

        if (strlen($buf) - $p < $body_len) {
            return [null, $start];
        }
        $msg->body = substr($buf, $p + 4, $body_len);
        return [$msg, $p + 4 + $body_len];
    }

    private static function decodeHeaders($buf)
    {
        $msg = new Message();
        $lines = preg_split('/\r\n?/', $buf);
        $meta = $lines[0];
        $blocks = explode(' ', $meta);
        if (substr(strtoupper($meta), 0, 4) == "HTTP") {
            $msg->status = intval($blocks[1]);
        } else {
            $msg->method = strtoupper($blocks[0]);
            if (count($blocks) > 1) {
                $msg->url = $blocks[1];
            }
        }
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            $kv = explode(':', $line);
            if (count($kv) < 2) {
                continue;
            }
            $key = strtolower(trim($kv[0]));
            $val = trim($kv[1]);
            $msg->headers[$key] = $val;
        }
        return $msg;
    }
}
