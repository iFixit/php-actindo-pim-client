<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class HandlerStack {
   private $handler;
   private $middleware = [];

   public static function jsonRpc(JsonRpcClient $rpcClient): self {
      $handler = new JsonRpcRequestHandler($rpcClient);
      return new static($handler);
   }

   public function __construct(Handler $handler) {
      $this->setHandler($handler);
   }

   public function setHandler(Handler $handler): self {
      $this->handler = $handler;
      return $this;
   }

   public function pushMiddleware(
      Middleware $middleware,
      string $key = null
   ): self {
      $this->middleware[$key ?? count($this->middleware)] = $middleware;
      return $this;
   }

   public function process(Request $request): Response {
      $bottomUp = array_keys($this->middleware);
      $topDown = array_reverse($bottomUp);

      foreach ($topDown as $key) {
         $this->middleware[$key]->prepare($request);
      }

      $response = $this->handler->handle($request);

      foreach ($bottomUp as $key) {
         $response = $this->middleware[$key]->process($response);
      }

      return $response;
   }
}
