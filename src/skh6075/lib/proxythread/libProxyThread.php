<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread;

use JetBrains\PhpStorm\Pure;
use skh6075\lib\proxythread\proxy\MultiProxy;

final class libProxyThread{
	#[Pure] public static function createMultiProxy(string $address, int $port): MultiProxy{
		return new MultiProxy($address, $port);
	}
}