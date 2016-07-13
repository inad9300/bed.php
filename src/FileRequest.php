<?php

require_once 'MultipartRelatedRequest.php';

/**
 * Specific kind of multipart requests for files, where two chunks are
 * expected, the first containing the metadata, and the second the actual
 * raw content of the file.
 */
class FileRequest extends MultipartRelatedRequest {

	public function getMetadata() {
		return $this->getChunk(0);
	}

	public function getContent() {
		return $this->getChunk(1);
	}
}

