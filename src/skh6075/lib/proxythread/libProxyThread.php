<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread;

use pocketmine\plugin\Plugin;
use skh6075\lib\proxythread\proxy\MultiProxy;

final class libProxyThread{
	public static function createMultiProxy(Plugin $plugin, string $address): MultiProxy{
		return new MultiProxy($plugin, $address);
	}
}