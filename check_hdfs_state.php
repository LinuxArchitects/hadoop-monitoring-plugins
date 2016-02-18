#!/usr/bin/env php
<?php

  require 'lib.php';

  $options = getopt ("hH:p:f:r:w:c:S");
  if (array_key_exists('h', $options) || !array_key_exists('H', $options) ||
     !array_key_exists('p', $options) || !array_key_exists('f', $options)) {
    usage();
    exit(3);
  }
  if (!(array_key_exists('w', $options) && array_key_exists('c', $options)) &&
      !(array_key_exists('r', $options))){
    usage();
    exit(3);
  }

  $host=$options['H'];
  $port=$options['p'];
  $field=$options['f'];

  $protocol = (array_key_exists('S', $options) ? 'https' : 'http');

    /* Get the json document */
  $object = get_from_jmx($protocol, $host, $port, 'Hadoop:service=NameNode,name=FSNamesystemState');
  if (empty($object)) {
    echo 'CRITICAL: Data inaccessible'.PHP_EOL;
    exit(2);
  }
  $val= array_key_exists($field, $object)? $object[$field] : false;
  $out_msg = $field.' = '.$val;
  if(empty($val)){
    echo 'UNKNOWN: Field '.$field.' not found'.PHP_EOL;
    exit(3);
  }
  if (array_key_exists('r', $options)){
    $wantedResp=json_decode($options['r'], true);
    if($val != $wantedResp){
      echo 'CRITICAL: '.$out_msg.PHP_EOL;
      exit(2);
    }
  }
  else{
    $warn=$options['w'];
    $crit=$options['c'];
    if ($val >= $crit) {
      echo 'CRITICAL: '.$out_msg.PHP_EOL;
      exit (2);
    }
    if ($val >= $warn) {
      echo 'WARNING: '.$out_msg.PHP_EOL;
      exit (1);
    }
  }
  echo 'OK: '.$out_msg.PHP_EOL;
  exit(0);

  /* print usage */
  function usage () {
    echo 'Usage: ./'.basename(__FILE__).' -h help -H <host> -p <port> -f <field> -w <warn%> -c <crit%> [-r <ret_value> -S ssl_enabled]'.PHP_EOL;
  }
?>