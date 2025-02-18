#!/usr/bin/env php
<?php
$proxies = fopen('/home/delegate/proxylist/proxyList.txt','r');

$port = 49152;
$squid = '';
$i = 0;
while ($line = fgets($proxies)){
    $line = trim($line);
    $proxyInfo = (explode(":",$line));
    $openProxy = false;

    switch ($proxyInfo[2]){
        case 'socks5':
            $additionalArguments = 'SERVER=https'.PHP_EOL;
            $additionalArguments = 'SOCKS='.$proxyInfo[0].':'.$proxyInfo[1].'/-r'.PHP_EOL;
            $additionalArguments.= 'CONNECT=socks'.PHP_EOL;

            break;

        case 'https':
            $additionalArguments = 'SERVER=https'.PHP_EOL;
            $additionalArguments = 'PROXY='.$proxyInfo[0].':'.$proxyInfo[1].PHP_EOL;
            $additionalArguments.= 'CONNECT=https'.PHP_EOL;

            break;
        case 'openproxy':
            $openProxy = true;
            break;
        default:
            $additionalArguments = 'SERVER=http'.PHP_EOL;
            $additionalArguments = 'PROXY='.$proxyInfo[0].':'.$proxyInfo[1].PHP_EOL;
            $additionalArguments.= 'CONNECT=proxy'.PHP_EOL;
            break;

    }
    if(!empty($proxyInfo[3])) {
        $additionalArguments .= 'MYAUTH=' . urlencode($proxyInfo[3]) . ':' . urlencode($proxyInfo[4]) . PHP_EOL;
    }
    $additionalArguments.= '-P'.$port;

    $filename = '/home/delegate/config/proxy_'.$port.'.conf';

    if(!$openProxy) {
        file_put_contents($filename, file_get_contents('/home/delegate/delegateBase.conf') . PHP_EOL . $additionalArguments);
        shell_exec('/usr/local/bin/delegate +=' . $filename);
        $squid .= 'cache_peer 127.0.0.1 parent '.$port.' 0 connect-fail-limit=1 connect-timeout=5 round-robin no-query name=proxy_'.$port.PHP_EOL;
    }else{
        $squid .= 'cache_peer '.$proxyInfo[0].' parent '.$proxyInfo[1].' 0 connect-fail-limit=1 connect-timeout=8 round-robin no-query name=proxy_'.$i.PHP_EOL;
    }

    $port++;
    $i++;
    if(!$openProxy && $port > 65535){
        break;
    }
}
file_put_contents(
        '/home/delegate/squid.conf',
        file_get_contents("/home/delegate/acl.conf").PHP_EOL.
        file_get_contents('/home/delegate/squid.conf').PHP_EOL.
        $squid.
        file_get_contents('/home/delegate/anonsquid.conf').PHP_EOL

);

system('squid -f '.'/home/delegate/squid.conf');
while (true){
    //need public-proxy update feature
    sleep(5);
}

?>

