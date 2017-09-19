<?php
/**
 * Created by PhpStorm.
 * User: zhangshaomin
 * Date: 2017/9/19
 * Time: 15:27
 */

namespace Rushmore\Zbus;

class Stream {
    use EventEmitter;

    private $stream;
    private $loop;
    private $softLimit;
    private $readBufferSize;

    private $writable = true;
    private $readable = true;
    private $closed = false;

    private $data = '';

    public function __construct($stream, EventLoop $loop, $writeBufferSoftLimit = null, $readChunkSize = null) {
        if (!is_resource($stream) || get_resource_type($stream) !== "stream") {
            throw new \InvalidArgumentException('Stream required');
        }

        $meta = stream_get_meta_data($stream);
        if (isset($meta['mode']) && $meta['mode'] !== '' && strpos($meta['mode'], '+') === false) {
            throw new InvalidArgumentException('Given stream resource is not opened in read and write mode');
        }

        if (stream_set_blocking($stream, 0) !== true) {
            throw new \RuntimeException('Unable to set non-blocking mode');
        }

        $this->stream = $stream;
        $this->loop = $loop;
        $this->softLimit = ($writeBufferSoftLimit === null) ? 65536 : (int)$writeBufferSoftLimit;
        $this->readBufferSize= ($readChunkSize === null) ? 65536 : (int)$readChunkSize;

        $this->resume();
    }

    public function isActive(){
        return !$this->closed;
    }

    public function pause() {
        $this->loop->removeReadStream($this->stream);
    }

    public function resume() {
        if ($this->readable) {
            $this->loop->addReadStream($this->stream, array($this, 'handleRead'));
        }
    }

    public function write($data) {
        if (!$this->writable) {
            return false;
        }

        $this->data .= $data;
        if ($this->data !== '') {
            $this->loop->addWriteStream($this->stream, array($this, 'handleWrite'));
        }

        return !isset($this->data[$this->softLimit - 1]);
    }

    public function end($data = null) {
        if (null !== $data) {
            $this->write($data);
        }

        $this->readable = false;
        $this->writable = false;

        // close immediately if buffer is already empty
        // otherwise wait for buffer to flush first
        if ($this->data === '') {
            $this->close();
        }
    }

    public function close() {
        if ($this->closed) {
            return;
        }

        $this->loop->removeStream($this->stream);

        $this->closed = true;
        $this->readable = false;
        $this->writable = false;
        $this->data = '';

        $this->emit('close', array($this));
        //$this->removeAllListeners();

        $this->handleClose();
    }

    public function handleClose() {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }


    public function handleRead() {
        $error = null;
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$error) {
            $error = new ErrorException(
                $errstr,
                0,
                $errno,
                $errfile,
                $errline
            );
        });

        $data = stream_get_contents($this->stream, $this->readBufferSize);

        restore_error_handler();

        if ($error !== null) {
            $this->close();
            $this->emit('error', array(new RuntimeException('Unable to read from stream: ' . $error->getMessage(), 0, $error)));

            return;
        }

        if ($data !== '') {
            $this->emit('data', array($data));
        } else {
            // no data read => we reached the end and close the stream
            $this->close();
            $this->emit('error', array(new RuntimeException('Closed by remote server')) );
        }
    }


    public function handleWrite() {
        $error = null;
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$error) {
            $error = array(
                'message' => $errstr,
                'number' => $errno,
                'file' => $errfile,
                'line' => $errline
            );
        });

        $sent = fwrite($this->stream, $this->data);

        restore_error_handler();

        // Only report errors if *nothing* could be sent.
        // Any hard (permanent) error will fail to send any data at all.
        // Sending excessive amounts of data will only flush *some* data and then
        // report a temporary error (EAGAIN) which we do not raise here in order
        // to keep the stream open for further tries to write.
        // Should this turn out to be a permanent error later, it will eventually
        // send *nothing* and we can detect this.
        if ($sent === 0 || $sent === false) {
            if ($error !== null) {
                $error = new ErrorException(
                    $error['message'],
                    0,
                    $error['number'],
                    $error['file'],
                    $error['line']
                );
            }

            $this->close();
            $this->emit('error', array(new RuntimeException('Unable to write to stream: ' . ($error !== null ? $error->getMessage() : 'Unknown error'), 0, $error)));

            return;
        }

        $exceeded = isset($this->data[$this->softLimit - 1]);
        $this->data = (string) substr($this->data, $sent);

        // buffer has been above limit and is now below limit
        if ($exceeded && !isset($this->data[$this->softLimit - 1])) {
            $this->emit('drain');
        }

        // buffer is now completely empty => stop trying to write
        if ($this->data === '') {
            $this->loop->removeWriteStream($this->stream);

            // buffer is end()ing and now completely empty => close buffer
            if (!$this->writable) {
                $this->close();
            }
        }
    }
}