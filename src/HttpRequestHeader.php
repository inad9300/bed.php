<?php

namespace bed;

require_once 'HttpCommonHeader.php';

/**
 * Constants for the standard headers allowed in HTTP requests.
 */
abstract class HttpRequestHeader extends HttpCommonHeader {

	const Accept = 'Accept';
	const AcceptCharset = 'Accept-Charset';
	const AcceptEncoding = 'Accept-Encoding';
	const AcceptLanguage = 'Accept-Language';
	const AcceptDatetime = 'Accept-Datetime';
	const Authorization = 'Authorization';
	const Cookie = 'Cookie';
	const Expect = 'Expect';
	const Forwarded = 'Forwarded';
	const From = 'From';
	const Host = 'Host';
	const IfMatch = 'If-Match';
	const IfModifiedSince = 'If-Modified-Since';
	const IfNoneMatch = 'If-None-Match';
	const IfRange = 'If-Range';
	const IfUnmodifiedSince = 'If-Unmodified-Since';
	const MaxForwards = 'Max-Forwards';
	const Origin = 'Origin';
	const Pragma = 'Pragma';
	const ProxyAuthorization = 'Proxy-Authorization';
	const Range = 'Range';
	const Referer = 'Referer';
	const Te = 'TE';
	const UserAgent = 'User-Agent';
}
