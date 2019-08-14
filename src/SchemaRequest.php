<?php
declare(strict_types = 1);

namespace Actindo\Pim;

class SchemaRequest extends Request {
   private $arg;
   private $filters;
   private $pagination;

   public function __construct(ClassStructure $arg) {
      $this->arg = $arg;
   }

   public function setFilters(Filters $filters): self {
      $this->filters = $filters;
      $this->arg->setFilter($filters->toArray());
      return $this;
   }

   public function setPagination(Pagination $pagination): self {
      $this->pagination = $pagination;
      $this->arg->setStart($pagination->getStart());
      $this->arg->setLimit($pagination->getLimit());
      return $this;
   }

   public function getMethod(): string {
      return $this->arg::API_METHOD;
   }

   public function getArg() {
      return $this->arg->toPrimitive();
   }

   public function getResponseClass(): string {
      return $this->arg::RESPONSE_CLASS;
   }
}
