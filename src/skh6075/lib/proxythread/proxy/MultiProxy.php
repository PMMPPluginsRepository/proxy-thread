<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\proxy;

use ArrayIterator;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use skh6075\lib\proxythread\event\ProxyReceiveDataEvent;
use skh6075\lib\proxythread\exception\ProxyException;
use skh6075\lib\proxythread\thread\ProxyThread;
use Volatile;

class MultiProxy extends Proxy{
	/**
	 * @phpstan-var array<string, ProxyThread>
	 * @var ProxyThread[]
	 */
	private array $threads = [];

	/**
	 * @phpstan-var array<string, Volatile>
	 * @var Volatile[]
	 */
	private array $volatiles = [];

	public function initialize(Plugin $plugin) : void{
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
			foreach($this->volatiles as $key => $volatile){
				while($volatile->count() > 0){
					$chunk = $volatile->shift();
					(new ProxyReceiveDataEvent($this, new ArrayIterator((array)$chunk)))->call();
				}
			}
		}), 5);
	}

	public function insert(string $key, ProxyThread $thread): void{
		$this->volatiles[$key] = $thread->getReceiveQueue();
		$this->threads[$key] = $thread;
		$thread->start(PTHREADS_INHERIT_ALL);
	}

	public function close(): void{
		foreach($this->threads as $key => $thread){
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
		unset($this->threads[$key], $this->volatiles[$key]);
	}

	public function select(string $key): ?ProxyThread{
		return $this->threads[$key] ?? null;
	}
}