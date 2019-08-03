<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Middleware {
   /**
    * Modifies a request on its way down the HandlerStack to being handled
    * (executed).
    */
   public function prepare(Request $request): void {
   }

   /**
    * Modifies a response on its way back up the HandlerStack. The original
    * response may be entirely replaced with a new one.
    */
   public function process(Response $response): Response {
      return $response;
   }
}
