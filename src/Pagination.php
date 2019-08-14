<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use Actindo\Pim\Exception\InvalidPagination;

class Pagination {
   const DEFAULT_START = 0;
   const DEFAULT_LIMIT = 50;

   private $start = self::DEFAULT_START;
   private $limit = self::DEFAULT_LIMIT;

   public function start(int $n): self {
      if ($n < 0) {
         throw new InvalidPagination($n);
      }
      $this->start = $n;
      return $this;
   }

   public function limit(int $n): self {
      if ($n < 1) {
         throw new InvalidPagination($n);
      }
      $this->limit = $n;
      return $this;
   }

   public function getStart(): int {
      return $this->start;
   }

   public function getLimit(): int {
      return $this->limit;
   }
}
