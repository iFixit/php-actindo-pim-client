<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use Swaggest\JsonSchema\Structure\ClassStructure as SwaggestClassStructure;
use Swaggest\JsonSchema\Context;

use Actindo\Pim\Exception\InvalidProperty;

/**
 * Our own wrapper around the Swaggest ClassStructure that doesn't allow
 * setting/getting properties that aren't in the schema. It also provides a few
 * convenience methods to streamline the process of validating and exporting to
 * primitive types and JSON.
 */
abstract class ClassStructure extends SwaggestClassStructure {
   private static $setOnlyDefinedProperties = true;

   /**
    * We want to catch typos when setting schema properties on our side, but at
    * the same time, we don't want to specify every single property that we
    * might receive from the API and throw an exception if we miss one or they
    * add one. To support both behaviors, we temporarily turn off property
    * validation on set when wholesale importing.
    */
   public static function import($data, Context $options = null) {
      $result = null;
      try {
         self::$setOnlyDefinedProperties = false;
         $result = parent::import($data, $options);
      } finally {
         self::$setOnlyDefinedProperties = true;
      }
      return $result;
   }

   public function __set($property, $value): void {
      if (
         self::$setOnlyDefinedProperties
         && !$this->properties()->offsetExists($property)
      ) {
         throw new InvalidProperty($property);
      }
      parent::__set($property, $value);
   }

   public function &__get($property) {
      if (!$this->properties()->offsetExists($property)) {
         throw new InvalidProperty($property);
      }
      return parent::__get($property);
   }

   /**
    * Validates and returns this structure as a PHP primitive suitable for
    * JSON-encoding (e.g., an instance of stdClass). The parent interface for
    * validating and exporting is static, which doesn't read very nicely when
    * you already have an instance of the class.
    */
   public function toPrimitive(Context $options = null) {
      return static::export($this, $options);
   }

   /**
    * Validates and returns this structure as a JSON-encoded string.
    */
   public function toJson(Context $options = null): string {
      return json_encode($this->toPrimitive($options));
   }
}
