<?php
declare(strict_types = 1);

namespace Actindo\Pim;

/**
 * The mirror image of a SchemaRequest: wraps a return value from a JSON RPC
 * method call that has been hydrated to a schema-backed object with magic
 * accessor methods.
 */
class SchemaResponse extends Response {
   private $value;

   public function __construct(ClassStructure $value) {
      $this->value = $value;
   }

   public function getValue() {
      // Return the ClassStructure instance (NOT a primitive data structure
      // like what you'd get from decoding JSON).
      return $this->value;
   }

   public function toJson(): string {
      return json_encode($this->value, JSON_PRETTY_PRINT);
   }

   public function __toString(): string {
      return "{$this->toJson()}\n";
   }
}
