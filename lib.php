<?php

  function query_socket($host, $port, $query){
    if (!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))){
      echo 'ERROR: Unknown error'.PHP_EOL;
      exit(2);
    }
    if(!socket_connect($sock, $host, $port)){
      echo 'ERROR: Connection refused'.PHP_EOL;
      exit(2);
    }
    if(!socket_write($sock, $query, strlen($query))){
      echo 'ERROR: Unable to write into socket'.PHP_EOL;
      exit(2);
    }
    $buf = '';
    if(!socket_shutdown($sock, 1)){ // 0 for read, 1 for write, 2 for r/w
      echo 'ERROR: Cannot close socket'.PHP_EOL;
      exit(2);
    }
    $read ='';
    while(($flag=socket_recv($sock, $buf, $bufsize,0))>0){
      $asc=ord(substr($buf, -1));
      if($asc==0) {
        $read.=substr($buf,0,-1);
        break;
      } else {
        $read.=$buf;
      }
    }
    if ($flag<0){
      echo 'ERROR: Socket read error'.PHP_EOL;
      exit(2);
    }
    socket_close($sock);
    return $read;
  }

  function query_livestatus($host, $port, $query){
    $buf=query_socket($host, $port, $query);
    if(empty($buf)){
      return array();
    }
    $lines=explode("\n", $buf);
    if(preg_match('/^Invalid GET/', $lines[0])){
      echo 'CRITICAL: Invalid request. Check filters'.PHP_EOL;
      exit(2);
    }
    return $lines;
  }

  function do_curl($protocol, $host, $port, $url){
    $ch = curl_init();
    curl_setopt_array($ch, array( CURLOPT_URL => $protocol."://".$host.":".$port.$url,
                                  CURLOPT_RETURNTRANSFER => true,
                                  CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                                  CURLOPT_USERPWD => ":",
                                  CURLOPT_SSL_VERIFYPEER => FALSE ));
    $ret = curl_exec($ch);
    curl_close($ch);
    return $ret;
  }

  function get_from_jmx($protocol, $host, $port, $query){
    $json_string = do_curl($protocol, $host, $port, '/jmx?qry='.$query);
    if($json_string === false){
      return false;
    }
    $json_array = json_decode($json_string, true);
    if(empty($json_array['beans']) || empty($json_array['beans'][0])){
      return false;
    }
    return $json_array['beans'][0];
  }
?>