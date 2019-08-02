#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__ . '/vendor/autoload.php';

use Actindo\Pim\JsonRpcClient;
use Actindo\Pim\Client;

function main() {
   $sandboxUrl = getenv('ACTINDO_SANDBOX_URL');
   $login = getenv('ACTINDO_LOGIN');
   $password = getenv('ACTINDO_PASSWORD');

   if (!$sandboxUrl || !$login || !$password) {
      throw new RuntimeException(
         'Missing one of ACTINDO_SANDBOX_URL, ACTINDO_LOGIN, or ' .
         'ACTINDO_PASSWORD in environment. Did you export them?');
   }

   $rpcClient = new JsonRpcClient($sandboxUrl);
   $pim = new Client($rpcClient);

   authenticate($pim, $login, $password);

   demoGetBaseAttributeSetId($pim);
   /* demoListAttributeSets($pim); */
}

// Authenticates, caching the auth token received from the server on a
// successful login in a file and reusing it for up to two hours.
function authenticate($pim, $login, $password) {
   $authTokenFilename = '.auth-token';

   $mtime = (int)@filemtime($authTokenFilename);
   if ($mtime >= strtotime('-2 hour')) {
      $auth = @file_get_contents($authTokenFilename);
      if ($auth) {
         $pim->setAuth($auth);
         return;
      }
   }

   $auth = $pim->login($login, $password);
   @file_put_contents($authTokenFilename, $auth);
}

function demoGetBaseAttributeSetId(Client $pim) {
   echo "{$pim->getBaseAttributeSetId()}\n";
}

function demoListAttributeSets($pim) {
   echo $pim->listAttributeSets();
}

main();
