<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\proxy;

use skh6075\lib\proxythread\exception\ProxyException;
use skh6075\lib\proxythread\thread\ProxyThread;
use function array_keys;
use function array_values;

class MultiProxy extends Proxy{
	/**
	 * @phpstan-var array<string, ProxyThread>
	 * @var ProxyThread[]
	 */
	private array $threads = [];

	public function insert(string $key, ProxyThread $thread): void{
		$this->threads[$key] = $thread;
		$thread->start(PTHREADS_INHERIT_ALL);
	}

	public function close(): void{
		foreach(array_keys($this->threads) as $key){
			$this->delete($key);
		}
	}

	public function delete(string $key): void{
		if(!isset($this->threads[$key])){
			throw ProxyException::wrap("No proxy found with key $key");
		}

		($proxy = $this->threads[$key])->shutdown();
		while($proxy->isRunning()){
		}
		unset($this->threads[$key]);
		$this->threads = array_values($this->threads);
	}

	public function select(string $key): ?ProxyThread{
		return $this->threads[$key] ?? null;
	}
}