#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use Actindo\Pim\JsonRpcClient;
use Actindo\Pim\Client;
use Actindo\Pim\HandlerStack;

define('AUTH_TOKEN_FILENAME', '.auth-token');

function main() {
   $sandboxUrl = getenv('ACTINDO_SANDBOX_URL');
   $login = getenv('ACTINDO_LOGIN');
   $password = getenv('ACTINDO_PASSWORD');

   if (!($sandboxUrl && $login && $password)) {
      throw new RuntimeException(
         'Missing one of ACTINDO_SANDBOX_URL, ACTINDO_LOGIN, or ' .
         'ACTINDO_PASSWORD in environment. Did you export them?');
   }

   $pim = makeAuthenticatedClient($sandboxUrl, $login, $password);

   demoGetBaseAttributeSetId($pim);
   /* demoListAttributeSets($pim); */
}

function makeAuthenticatedClient(
   string $sandboxUrl,
   string $login,
   string $password
): Client {
   $rpcClient = new JsonRpcClient($sandboxUrl);
   $handlerStack = HandlerStack::jsonRpc($rpcClient);
   $pim = new Client($handlerStack);

   $auth = '';
   $mtime = (int)@filemtime(AUTH_TOKEN_FILENAME);
   if ($mtime >= strtotime('-2 hour')) {
      $auth = (string)@file_get_contents(AUTH_TOKEN_FILENAME);
   }

   if (!$auth) {
      $auth = $pim->login($login, $password);
      @file_put_contents(AUTH_TOKEN_FILENAME, $auth);
   }

   $rpcClient->setAuth($auth);
   return $pim;
}

function demoGetBaseAttributeSetId(Client $pim) {
   echo "{$pim->getBaseAttributeSetId()}\n";
}

function demoListAttributeSets($pim) {
   echo $pim->listAttributeSets();
}

main();
