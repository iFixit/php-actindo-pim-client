<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use Actindo\Pim\Exception\InvalidHydratedResponseType;

class JsonRpcRequestHandler extends Handler {
   public function __construct(JsonRpcClient $rpcClient) {
      $this->rpcClient = $rpcClient;
   }

   public function handle(Request $request): Response {
      $method = $request->getMethod();
      $responseValue = $this->rpcClient->call($method, $request->getArg());
      $responseClass = $request->getResponseClass();

      if (is_subclass_of($responseClass, ClassStructure::class)) {
         $responseObj = $responseClass::import($responseValue);
         return new SchemaResponse($responseObj);
      }

      // Thus far, we expect all response objects to be schema-backed.
      throw new InvalidHydratedResponseType($responseClass);
   }
}
