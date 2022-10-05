<?php

declare(strict_types=1);

namespace skh6075\lib\proxythread\thread;

use InvalidArgumentException;
use pocketmine\thread\ThreadException;
use skh6075\lib\proxythread\proxy\Proxy;
use Socket;
use Thread;
use Volatile;
use function is_array;
use function json_decode;
use function json_encode;
use function socket_bind;
use function socket_close;
use function socket_create;
use function socket_sendto;
use function socket_set_nonblock;

final class ProxyThread extends Thread{
	public const KEY_IDENTIFY = "identify";
	public const KEY_DATA = "data";

	private bool $shutdown = false;

	private Volatile $sendQueue;
	private Volatile $receiveQueue;

	public function __construct(
		private Proxy $proxy,
		private int $receivePort,
		private Volatile $sendPorts, //multi-proxy-socket
		?Volatile $sendQueue = null,
		?Volatile $receiveQueue = null
	){
		$this->sendQueue = $sendQueue ?? new Volatile();
		$this->receiveQueue = $receiveQueue ?? new Volatile();
	}

	public function shutdown() : void{
		$this->shutdown = true;
	}

	public function getReceiveQueue() : Volatile{
		return $this->receiveQueue;
	}

	public function run(){
		$receiveSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_nonblock($receiveSocket);

		if($receiveSocket === false){
			throw new InvalidArgumentException("Failed to create socket");
		}

		if(socket_bind($receiveSocket, "0.0.0.0", $this->receivePort) === false){
			throw new InvalidArgumentException("Failed to bind port (bindPort: $this->receivePort)");
		}

		$sendSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if($sendSocket === false){
			throw new InvalidArgumentException("Failed to create socket");
		}

		while(!$this->shutdown){
			$this->receiveData($receiveSocket);

			while($this->sendQueue->count() > 0){
				$chunk = $this->sendQueue->shift();
				if(!isset($chunk[self::KEY_IDENTIFY], $chunk[self::KEY_DATA])){
					continue;
				}

				foreach((array) $this->sendPorts as $port){
					socket_sendto($sendSocket, json_encode((array) $chunk), 65535, 0, $this->proxy->getAddress(), $port);
				}
			}
		}
		socket_close($sendSocket);
		socket_close($receiveSocket);
	}

	private function receiveData(Socket $receiveSocket) : void{
		$buffer = "";
		if(socket_recvfrom($receiveSocket, $buffer, 65535, 0, $source, $port) === false){
			$errno = socket_last_error($receiveSocket);
			if($errno === SOCKET_EWOULDBLOCK){
				return;
			}
			throw new ThreadException("Failed received");
		}

		if($buffer !== null && $buffer !== ""){
			$data = json_decode($buffer, true);
			if(!is_array($data) || !isset($data[self::KEY_IDENTIFY], $data[self::KEY_DATA])){
				return;
			}

			$this->receiveQueue[] = $data;
		}
	}

	public function send(array $data) : void{
		$this->sendQueue[] = $data;
	}
}