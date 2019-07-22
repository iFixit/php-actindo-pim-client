<?php
declare(strict_types = 1);

namespace Actindo\Pim;

abstract class Request {
   // Sets a property on the request body.
   abstract public function set(string $property, $value): void;

   // Uses $rpcClient to execute the request and return a Response instance.
   abstract public function execute(JsonRpcClient $rpcClient): Response;
}
