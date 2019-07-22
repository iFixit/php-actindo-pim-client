<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Filters {
   private $filters = [];

   public function equals(string $property, string $value): self {
      $this->filters[] = $this->makeFilter($property, '=', $value);
      return $this;
   }

   public function apply(Request $request): void {
      if ($this->filters) {
         $request->set('filter', $this->filters);
      }
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
