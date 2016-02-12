<?php

require_once 'MultipartRelatedRequest.php';


class FileRequest extends MultipartRelatedRequest {

    public function getMetadata() {
        return $this->getChunks(0);
    }

    public function getContent() {
        return $this->getChunks(1);
    }

}