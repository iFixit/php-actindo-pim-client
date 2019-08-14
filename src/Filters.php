<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Filters {
   private $filters = [];

   public function equals(string $property, $value): self {
      $this->filters[] = $this->makeFilter($property, '=', $value);
      return $this;
   }

   public function toArray(): array {
      return $this->filters;
   }

   private function makeFilter(
      string $property,
      string $operator,
      $value
   ): Schema\Filter {
      return (new Schema\Filter)
         ->setProperty($property)
         ->setOperator($operator)
         ->setValue($value);
   }
}
