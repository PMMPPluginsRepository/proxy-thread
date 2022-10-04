<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\exception;

use Exception;
use JetBrains\PhpStorm\Pure;

final class ProxyException extends Exception{
	#[Pure] public static function wrap(string $message): ProxyException{
		return new ProxyException($message);
	}
}