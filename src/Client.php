<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Client {
   private $rpcClient;

   public function __construct(JsonRpcClient $rpcClient) {
      $this->rpcClient = $rpcClient;
   }

   public function login($login, $password): string {
      $body = (new Schema\LoginRequest)
         ->setLogin($login)
         ->setPass($password);

      $request = new SchemaRequest($body);
      $responseBody = $request->execute($this->rpcClient)->getBody();

      $auth = $responseBody->sessionId;
      $this->setAuth($auth);
      return $auth;
   }

   public function setAuth(string $auth) {
      $this->rpcClient->setAuth($auth);
   }
}
