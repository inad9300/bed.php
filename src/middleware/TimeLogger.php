<?php

require_once '../RequestInterceptor.php';
require_once '../ResponseInterceptor.php';
require_once '../Request.php';
require_once '../Response.php';

class TimeLogger implements RequestInterceptor, ResponseInterceptor {

    private $logfile;
    private $startTime;

    public function __construct(string $logfile = 'time.log') {
        $this->logfile = $logfile;
    }

    public function handle(Request $req, Response $res): Response {
        if ($this->startTime) {
            $endTime = microtime(true);

            $message = date(\DateTime::ATOM, $this->startTime)
                . ' ' . $req->getUrl()->getFull()
                . ' ' . ($endTime - $this->startTime);
            
            file_put_contents($this->logfile, $message, \FILE_APPEND | \LOCK_EX);
        } else {
            $this->startTime = microtime(true);
        }
    }
}
