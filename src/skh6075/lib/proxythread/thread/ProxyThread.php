<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\thread;

use ArrayIterator;
use JetBrains\PhpStorm\Pure;
use pocketmine\thread\ThreadException;
use skh6075\lib\proxythread\event\ProxyReceiveDataEvent;
use skh6075\lib\proxythread\event\ProxySendDataEvent;
use skh6075\lib\proxythread\proxy\Proxy;
use Socket;
use Thread;
use Volatile;
use function socket_create;
use function socket_set_nonblock;
use function socket_bind;
use function socket_close;
use function socket_sendto;
use function socket_read;
use function json_decode;
use function json_encode;
use function is_string;
use function is_array;

class ProxyThread extends Thread{
	public const KEY_IDENTIFY = "identify";
	public const KEY_DATA = "data";

	private bool $shutdown = true;

	private Volatile $sendQueue;

	#[Pure] public function __construct(
		private Proxy $proxy,
		private int $bindPort,
		?Volatile $sendQueue = null
	){
		$this->sendQueue = $sendQueue ?? new Volatile();
	}

	public function shutdown(): void{
		$this->shutdown = true;
	}

	public function run(){
		$receiveSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$sendSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

		socket_set_nonblock($receiveSocket);

		if($receiveSocket === false || $sendSocket === false){
			throw new ThreadException("Failed to create socket");
		}

		if(socket_bind($receiveSocket, "0.0.0.0", $this->bindPort) === false){
			throw new ThreadException("Failed to bind port");
		}

		if(!$this->shutdown){
			$this->sendData($sendSocket);
			$this->receiveData($receiveSocket);
		}
		socket_close($receiveSocket);
		socket_close($sendSocket);
	}

	private function sendData(Socket $sendSocket): void{
		while($this->sendQueue->count() > 0){
			$chunk = $this->sendQueue->shift();
			if(!isset($chunk[self::KEY_IDENTIFY], $chunk[self::KEY_DATA])){
				continue;
			}

			if(socket_sendto($sendSocket, json_encode($chunk), PHP_INT_MAX, 0, $this->proxy->getAddress(), $this->proxy->getPort()) === false){
				continue;
			}

			(new ProxySendDataEvent($this->proxy, new ArrayIterator([
				self::KEY_IDENTIFY => $chunk[self::KEY_IDENTIFY],
				self::KEY_DATA => $chunk[self::KEY_DATA]
			])))->call();
		}
	}

	private function receiveData(Socket $receiveSocket): void{
		while(is_string(($row = socket_read($receiveSocket, PHP_INT_MAX)))){
			$data = json_decode($row, true);
			if(!is_array($data) || !isset($data[self::KEY_IDENTIFY], $data[self::KEY_DATA])){
				continue;
			}

			(new ProxyReceiveDataEvent($this->proxy, new ArrayIterator($data)))->call();
		}
	}

	public function getSendQueue(): Volatile{
		return $this->sendQueue;
	}
}