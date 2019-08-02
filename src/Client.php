<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Client {
   private $rpcClient;

   public function __construct(JsonRpcClient $rpcClient) {
      $this->rpcClient = $rpcClient;
   }

   public function filters(): Filters {
      return new Filters;
   }

   public function pagination(): Pagination {
      return new Pagination();
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

   public function getBaseAttributeSetId(): int {
      $body = $this->listAttributeSets(
         $this->filters()->equals('key', 'pim_base_set'),
         $this->pagination()->limit(2))->getBody();

      if (!$body->data) {
         throw new InvalidBaseAttributeSet(
            "Failed to find attribute set 'pim_base_set'");
      } else if (count($body->data) > 1) {
         throw new InvalidBaseAttributeSet(
            "2+ attribute sets with key 'pim_base_set' (1 expected)");
      }

      return $body->data[0]->id;
   }

   public function listAttributeSets(
      Filters $filters = null,
      Pagination $pagination = null
   ): Response {
      return $this->executeListRequest(
         new SchemaRequest(new Schema\ListAttributeSetsRequest),
         $filters,
         $pagination);
   }

   private function executeListRequest(
      Request $request,
      ?Filters $filters,
      ?Pagination $pagination
   ): Response {
      if ($filters) {
         $filters->apply($request);
      }

      // Always paginate so that we aren't implicitly relying on default limits
      // defined by the API.
      if (!$pagination) {
         $pagination = new Pagination;
      }
      $pagination->apply($request);
      return $request->execute($this->rpcClient);
   }
}
