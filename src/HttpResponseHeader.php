<?php

namespace bed;

require_once 'HttpCommonHeader.php';

/**
 * Constants for the standard headers allowed in HTTP responses.
 */
abstract class HttpResponseHeader extends HttpCommonHeader {

	const AccessControlAllowOrigin = 'Access-Control-Allow-Origin';
	const AcceptPath = 'Accept-Path';
	const AcceptRanges = 'Accept-Ranges';
	const Age = 'Age';
	const Allow = 'Allow';
	const AltSvc = 'Alt-Svc';
	const ContentDisposition = 'Content-Disposition';
	const ContentEncoding = 'Content-Encoding';
	const ContentLanguage = 'Content-Language';
	const ContentLocation = 'Content-Location';
	const ContentRange = 'Content-Range';
	const ETag = 'ETag';
	const Expires = 'Expires';
	const LastModified = 'Last-Modified';
	const Link = 'Link';
	const Location = 'Location';
	const P3p = 'P3P';
	const Pragma = 'Pragma';
	const ProxyAuthenticate = 'Proxy-Authenticate';
	const PublicKeyPins = 'Public-Key-Pins';
	const Refresh = 'Refresh';
	const RetryAfter = 'Retry-After';
	const Server = 'Server';
	const SetCookie = 'Set-Cookie';
	const Status = 'Status';
	const StrictTransportSecurity = 'Strict-Transport-Security';
	const Trailer = 'Trailer';
	const TransferEncoding = 'Transfer-Encoding';
	const Tsv = 'TSV';
	const Vary = 'Vary';
	const WwwAuthenticate = 'WWW-Authenticate';
	const XFrameOptions = 'X-Frame-Options';
}
