<?php
declare(strict_types = 1);

namespace Actindo\Pim;
use Actindo\Pim\Schema\ProductSaveRequest;

class Client {
   private $handlerStack;

   public function __construct(HandlerStack $handlerStack) {
      $this->handlerStack = $handlerStack;
   }

   public function filters(): Filters {
      return new Filters;
   }

   public function pagination(): Pagination {
      return new Pagination();
   }

   public function login(string $login, string $password): string {
      $body = (new Schema\LoginRequest)
         ->setLogin($login)
         ->setPass($password);

      $request = new SchemaRequest($body);
      $responseBody = $this->executeRequest($request)->getValue();
      return $responseBody->sessionId;
   }

   public function getBaseAttributeSetId(): int {
      $body = $this->listAttributeSets(
         $this->filters()->equals('key', 'pim_base_set'),
         $this->pagination()->limit(2))->getValue();

      if (!$body->data) {
         throw new InvalidBaseAttributeSet(
            "Failed to find attribute set 'pim_base_set'");
      } else if (count($body->data) > 1) {
         throw new InvalidBaseAttributeSet(
            "2+ attribute sets with key 'pim_base_set' (1 expected)");
      }

      return $body->data[0]->id;
   }

   public function listAttributeSets(
      Filters $filters = null,
      Pagination $pagination = null
   ): Response {
      $request = new SchemaRequest(new Schema\ListAttributeSetsRequest);
      return $this->executeListRequest($request, $filters, $pagination);
   }

   public function productSave(ProductSaveRequest $request): Response {
      return $this->executeRequest(new SchemaRequest($request));
   }

   private function executeListRequest(
      Request $request,
      ?Filters $filters,
      ?Pagination $pagination
   ): Response {
      if ($filters) {
         $request->setFilters($filters);
      }

      // Always paginate so that we aren't implicitly relying on default limits
      // defined by the API.
      $request->setPagination($pagination ?? new Pagination);

      return $this->executeRequest($request);
   }

   private function executeRequest(Request $request): Response {
      return $this->handlerStack->process($request);
   }
}
