<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use \Swaggest\PhpCodeBuilder\PhpClass;
use \Swaggest\PhpCodeBuilder\PhpConstant;
use \Swaggest\PhpCodeBuilder\PhpCode;
use \Swaggest\JsonSchema\Schema;

class SchemaBuilder {
   private $inputPath;
   private $outputPath;
   private $namespaceRoot;
   private $app;
   private $builder;

   public static function run(): void {
      $builder = new self();
      $builder->setInputPath(realpath(__DIR__ . '/../schema'));
      $builder->setOutputPath(realpath(__DIR__ . '/Schema'));
      $builder->setNamespaceRoot('Actindo\Pim\Schema');
      $builder->build();
   }

   public function __construct() {
      $this->app = new \Swaggest\PhpCodeBuilder\App\PhpApp();

      $this->builder = new \Swaggest\PhpCodeBuilder\JsonSchema\PhpBuilder();
      $this->builder->buildSetters = true;
      $this->builder->makeEnumConstants = true;
      $this->builder->classCreatedHook =
         new \Swaggest\PhpCodeBuilder\JsonSchema\ClassHookCallback(
            \Closure::fromCallable([$this, 'handleClassCreated']));
   }

   public function setInputPath(string $path): void {
      $this->inputPath = $path;
   }

   public function setOutputPath(string $path): void {
      $this->outputPath = $path;
   }

   public function setNamespaceRoot(string $namespaceRoot): void {
      $this->namespaceRoot = $namespaceRoot;
      $this->app->setNamespaceRoot($namespaceRoot, '.');
   }

   public function build(): void {
      $schemaPaths = glob($this->inputPath . '/*.json');
      foreach ($schemaPaths as $path) {
         $this->buildSchemaAtPath($path);
      }
      $this->app->store($this->outputPath);
   }

   private function buildSchemaAtPath(string $path) {
      $swaggerSchema = \Swaggest\JsonSchema\Schema::import($path);
      // This is what registers the schema/type with the builder.
      $this->builder->getType($swaggerSchema);
   }

   private function handleClassCreated(
      PhpClass $class,
      string $path,
      Schema $schema
   ) {
      $class->setNamespace($this->namespaceRoot);
      $class->setName($this->buildClassNameFromPath($path));

      if ($this->isRequest($path)) {
         // Treat the `_method` property as the JSON RPC method that accepts
         // the schema object as an argument. It's required for request
         // schemas.
         $method = $schema->_method;
         if (!$method) {
            throw new \Exception("Missing _method property in '$path'");
         }
         $class->addConstant(new PhpConstant('API_METHOD', $schema->_method));

         // If the schema has a `_response_class` metadata property, treat it
         // as a class that can hydrate a raw JSON response to this request.
         $responseClass = $schema->_response_class;
         if (!$responseClass) {
            throw new \Exception("Missing _response_class property in '$path'");
         }
         $class->addConstant(new PhpConstant('RESPONSE_CLASS', $responseClass));
      }

      $this->app->addClass($class);
   }

   private function isRequest(string $path): bool {
      return (bool)preg_match('#\.request\.json$#', basename($path));
   }

   private function buildClassNameFromPath(string $path): string {
      $base = basename($path, '.json');
      return PhpCode::makePhpClassName($base);
   }
}
