<?php

declare(strict_types=1);

namespace bed;

/**
 * Constants for the standard headers applicable to both HTTP requests and
 * responses. Avoid using this class, in favor of HttpRequestHeader and
 * HttpResponseHeader.
 */
abstract class HttpCommonHeader {

	const CacheControl = 'Cache-Control';
	const Connection = 'Connection';
	const ContentLength = 'Content-Length';
	const ContentMd5 = 'Content-Md5';
	const ContentType = 'Content-Type';
	const Date = 'Date';
	const Upgrade = 'Upgrade';
	const Via = 'Via';
	const Warning = 'Warning';
}
