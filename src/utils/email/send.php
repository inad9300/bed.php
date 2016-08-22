<?php

namespace bed\utils\email;

class SendMode {
	const FROM = 0;
	const COPY = 1;
	const BLIND = 2;
}

/**
 * Wrapper for the PHP's native mail() function.
 */
function send(
	string $from,
	$to,
	string $subject,
	string $body,
	int $mode = SendMode::FROM
) {
	if (!filter_var($from, FILTER_VALIDATE_EMAIL))
		throw new \InvalidArgumentException(
			'Trying to send an email from an invalid address');

	if (is_string($to))
		$to = explode(',', $to);
	else if (!is_array($to))
		throw new \InvalidArgumentException(
			'The second argument ($to) must be either a string or an array');

	foreach ($to as $address)
		if (!filter_var($address, FILTER_VALIDATE_EMAIL))
			throw new \InvalidArgumentException(
				'Trying to send an email to an invalid address: ' . $address);

	$to = implode(',', $to);

	$headers = [];
	$headers[] = 'From: ' . $from;
	$headers[] = 'Content-Type: text/html; charset=UTF-8';

	switch ($mode) {
	case SendMode::FROM:
		// Nothing to be done
		break;
	case SendMode::COPY:
	case SendMode::BLIND:
		$headerPrefix = $mode === SendMode::COPY ? 'Cc: ' : 'Bcc: ';
		$headers[] = $headerPrefix . $to;
		$to = $from;
		break;
	}

	$ok = mail($to, $subject, $body, implode("\r\n", $headers));
	if (!$ok)
		throw new \RuntimeException('Impossible to send the email');
}
