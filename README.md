# proxy-thread
Proxy with server socket handling

## Usage
Connect when the plugin that will use the proxy is activated

**NOTE:** If you try to call the class without the library loaded, you will get an error. After adding `DEVirion to depend`, call the proxy class in the `onEnable` method.
```php
protected function onEnable() : void{
	$this->proxy = libProxyThread::createMultiProxy($this, $address ?? "127.0.0.1");
}
```

## Register a Proxy Server
This proxy library can connect multiple servers

**NOTE:** `receivePort` must not be duplicated, `sendPort` is the receivePort of another proxy server.
```php
$this->proxy->insert("lobby_multi_chat", new ProxyThread($this->multiProxy, 1000, (function(): Volatile{
	$volatile = new Volatile();
	$volatile[] = 1001; //skywars multi chat proxy port
	return $volatile;
})()));
$this->proxy->insert("skywars_multi_chat", new ProxyThread($this->multiProxy, 1001, (function(): Volatile{
	$volatile = new Volatile();
	$volatile[] = 1000; //lobby multi chat proxy port
	return $volatile;
})()));
```

## Example Proxy Handling
When the chat is sent from the lobby server, it is also sent to the skywars server.

**CHANNEL:** `lobby`
```php
public function onPlayerChatEvent(PlayerChatEvent $event): void{
	if(!$event->isCancelled()){
		$this->proxy->select("skywars_multi_chat")->send([
			ProxyThread::KEY_IDENTIFY => "multi-chat",
			ProxyThread::KEY_DATA => ["format" => $event->getFormat()]
		]);
	}
}
```
**CHANNEL:** `skywars`
```php
public function onProxyReceiveDataEvent(ProxyReceiveDataEvent $event): void{
	$iterator = $event->getIterator();
	if($iterator->offsetGet(ProxyThread::KEY_IDENTIFY) !== "multi-chat"){
		return;
	}
	$data = $iterator->offsetGet(ProxyThread::KEY_DATA);
	$this->getServer()->broadcastMessage($data["format"]);
}
```
