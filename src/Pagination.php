<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class Pagination {
   const DEFAULT_START = 0;
   const DEFAULT_LIMIT = 50;

   private $start = self::DEFAULT_START;
   private $limit = self::DEFAULT_LIMIT;

   public function start(int $n) {
      if ($n < 0) {
         throw new \InvalidArgumentException($n);
      }
      $this->start = $n;
   }

   public function limit(int $n) {
      if ($n < 1) {
         throw new \InvalidArgumentException($n);
      }
      $this->limit = $n;
   }

   public function apply(Request $request): void {
      $request->set('start', $this->start);
      $request->set('limit', $this->limit);
   }
}
