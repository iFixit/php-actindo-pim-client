<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use Swaggest\JsonSchema\Structure\ClassStructure;

class SchemaRequest extends Request {
   private $body;

   public function __construct(ClassStructure $body) {
      $this->body = $body;
   }

   public function set(string $property, $value): void {
      $this->body->{$property} = $value;
   }

   public function execute(JsonRpcClient $rpcClient): Response {
      $method = $this->body::API_METHOD;
      $response = $rpcClient->call($method, $this->body);
      return $this->hydrateResponse($response);
   }

   private function hydrateResponse($responseBody): Response {
      $responseClass = $this->body::RESPONSE_CLASS;
      $hydratedResponseBody = $responseClass::import($responseBody);
      return new Response($hydratedResponseBody);
   }
}
