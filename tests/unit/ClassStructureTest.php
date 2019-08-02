<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use Swaggest\JsonSchema\InvalidValue;

use Actindo\Pim\Schema\LoginRequest;
use Actindo\Pim\Exception\InvalidProperty;

class ClassStructureTest extends TestCase {
   public function testAccessingValidProperty() {
      $body = new LoginRequest;
      $body->setLogin('login_a');
      $this->assertEquals('login_a', $body->login);
      $body->login = 'login_b';
      $this->assertEquals('login_b', $body->login);
   }

   public function testGettingInvalidProperty() {
      $this->expectException(InvalidProperty::class);
      $body = new LoginRequest;
      $x = $body->invalidProperty;
   }

   public function testSettingInvalidProperty() {
      $this->expectException(InvalidProperty::class);
      $body = new LoginRequest;
      $body->invalidProperty = 1;
   }

   public function testExportToPrimitive() {
      $body = (new LoginRequest)->setLogin('login1')->setPass('pass1');
      $obj = $body->toPrimitive();
      $this->assertEquals((object)[
         'login' => 'login1',
         'pass' => 'pass1',
      ], $obj);
   }

   public function testExportToJson() {
      $body = (new LoginRequest)->setLogin('login1')->setPass('pass1');
      $json = $body->toJson();
      $this->assertEquals(json_encode([
         'login' => 'login1',
         'pass' => 'pass1',
      ]), $json);
   }

   public function testSchemaValidationOnExportToPrimitive() {
      $this->expectException(InvalidValue::class);
      // Login must be a string.
      $body = (new LoginRequest)->setLogin(123)->setPass('pass');
      $body->toPrimitive();
   }

   public function testSchemaValidationOnExportToJson() {
      $this->expectException(InvalidValue::class);
      $body = (new LoginRequest)->setLogin(123)->setPass('pass');
      $body->toJson();
   }
}
