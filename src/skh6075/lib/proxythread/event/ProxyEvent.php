<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\event;

use ArrayIterator;
use pocketmine\event\Event;
use skh6075\lib\proxythread\proxy\Proxy;

abstract class ProxyEvent extends Event{
	public function __construct(
		private Proxy $proxy,
		private ArrayIterator $iterator
	){}

	public function getProxy(): Proxy{
		return $this->proxy;
	}

	public function getIterator(): ArrayIterator{
		return $this->iterator;
	}
}