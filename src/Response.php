<?php
declare(strict_types = 1);

namespace Actindo\Pim;

/**
 * A wrapper around a raw response. This exists as a place to inject behavior
 * for different kinds of responses (e.g., to facilitate pagination).
 */
class Response {
   private $body;

   public function __construct($body) {
      $this->body = $body;
   }

   public function getBody() {
      return $this->body;
   }

   public function toJson(): string {
      return json_encode($this->body, JSON_PRETTY_PRINT);
   }

   public function __toString(): string {
      return "{$this->toJson()}\n";
   }
}
