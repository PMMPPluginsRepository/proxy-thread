<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\proxy;

abstract class Proxy{
	public function __construct(
		private string $address,
		private int $port
	){}

	public function getAddress(): string{
		return $this->address;
	}

	public function getPort(): int{
		return $this->port;
	}
}