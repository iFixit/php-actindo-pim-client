<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use Swaggest\JsonSchema\InvalidValue;

use Actindo\Pim\SchemaRequest;
use Actindo\Pim\Filters;
use Actindo\Pim\Pagination;
use Actindo\Pim\Schema\ListAttributeSetsRequest;
use Actindo\Pim\Schema\ListAttributeSetsResponse;

class SchemaRequestTest extends TestCase {
   public function testRequestInterface() {
      $request = new SchemaRequest(new ListAttributeSetsRequest);

      $request->setFilters((new Filters)
         ->equals('prop', 1));

      $request->setPagination((new Pagination)
         ->start(10)
         ->limit(20));

      $this->assertEquals(
         'Actindo.Modules.Actindo.DataHub.AttributeSets.get',
         $request->getMethod());

      $this->assertEquals((object)[
         'filter' => [
            (object)[
               'property' => 'prop',
               'operator' => '=',
               'value' => 1
            ],
         ],
         'start' => 10,
         'limit' => 20
      ], $request->getArg());

      $this->assertEquals(
         ListAttributeSetsResponse::class,
         $request->getResponseClass());
   }
}
