<?php
declare(strict_types = 1);

namespace Actindo\Pim;

use Swaggest\PhpCodeBuilder\PhpClass;
use Swaggest\PhpCodeBuilder\PhpConstant;
use Swaggest\PhpCodeBuilder\PhpCode;
use Swaggest\JsonSchema\Schema;

use Actindo\Pim\Exception\InvalidRequestSchema;

class SchemaBuilder {
   private $inputPath;
   private $outputPath;
   private $namespaceRoot;
   private $classStructureClass;
   private $app;
   private $builder;

   public static function run(): void {
      $builder = new self();
      $builder->setInputPath(realpath(__DIR__ . '/../schema'));
      $builder->setOutputPath(realpath(__DIR__ . '/Schema'));
      $builder->setNamespaceRoot('Actindo\Pim\Schema');
      $builder->setClassStructureClass('Actindo\Pim\ClassStructure');
      $builder->build();
   }

   public function __construct() {
      $this->app = new \Swaggest\PhpCodeBuilder\App\PhpApp();

      $this->builder = new \Swaggest\PhpCodeBuilder\JsonSchema\PhpBuilder();
      $this->builder->buildSetters = true;
      $this->builder->makeEnumConstants = true;
      $this->builder->classPreparedHook =
         new \Swaggest\PhpCodeBuilder\JsonSchema\ClassHookCallback(
            \Closure::fromCallable([$this, 'handleClassPrepared']));
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

   public function setClassStructureClass(string $qualifiedClassName): void {
      $this->classStructureClass = $qualifiedClassName;
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

   /**
    * A hook that PhpBuilder provides to customize the class that gets
    * generated to represent a schema-backed object. This is where our
    * particular application can do things like set the namespace, decide on
    * the class name, add properties, and so on.
    *
    * The class extends Swaggest\JsonSchema\Structure\ClassStructure by
    * default, but that can be overriden to provide our own default behavior.
    *
    * There is also a classCreatedHook, but that gets called before the library
    * sets the base class, so the base class that we try to set gets
    * overridden.
    */
   private function handleClassPrepared(
      PhpClass $class,
      string $path,
      Schema $schema
   ) {
      $class->setNamespace($this->namespaceRoot);
      $class->setName($this->buildClassNameFromPath($path));

      if ($this->classStructureClass) {
         $class->setExtends(PhpClass::byFQN($this->classStructureClass));
      }

      // If we decide that the schema looks like it defines a request body,
      // then we add some special behavior to help our library associate the
      // body with the right JSON RPC method and the expected response schema.
      if ($this->isRequest($path)) {
         // Treat the `_method` property as the JSON RPC method that accepts
         // the schema object as an argument. It's required for request
         // schemas.
         $method = $schema->_method;
         if (!$method) {
            throw new InvalidRequestSchema(
               "Missing _method property in '$path'");
         }
         $class->addConstant(new PhpConstant('API_METHOD', $schema->_method));

         // Treat the `_response_class` property as a class that can hydrate a
         // raw JSON response to this request.
         $responseClass = $schema->_response_class;
         if (!$responseClass) {
            throw new InvalidRequestSchema(
               "Missing _response_class property in '$path'");
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
