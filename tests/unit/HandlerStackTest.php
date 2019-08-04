<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use Actindo\Pim\HandlerStack;
use Actindo\Pim\Handler;
use Actindo\Pim\Middleware;
use Actindo\Pim\Request;
use Actindo\Pim\Response;

class HandlerStackTest extends TestCase {
   public function testHandler() {
      $handler = new LoggingHandler;
      $handlerStack = new HandlerStack($handler);

      $request = new LoggingRequest;
      $response = $handlerStack->process($request);

      $this->assertEquals(['sent'], $request->log);
      $this->assertEquals([], $response->log);
   }

   public function testMiddleware() {
      $handler = new LoggingHandler;
      $handlerStack = (new HandlerStack($handler))
         ->pushMiddleware(new LoggingMiddleware('m1'))
         ->pushMiddleware(new LoggingMiddleware('m2'));

      $request = new LoggingRequest;
      $response = $handlerStack->process($request);

      $this->assertEquals([
         'm2 prepared',
         'm1 prepared',
         'sent',
      ], $request->log);

      $this->assertEquals([
         'm1 processed',
         'm2 processed',
      ], $response->log);
   }

   public function testReplacingRequestAndResponse() {
      $handler = new LoggingHandler;

      $replacingMiddleware = new class('m2') extends LoggingMiddleware {
         public $newRequest;
         public $oldResponse;
         public function prepare(Request $request): Request {
            $request->log($this->name, 'replaced');
            $this->newRequest = new LoggingRequest;
            return $this->newRequest;
         }
         public function process(Response $response): Response {
            $response->log($this->name, 'replaced');
            $this->oldResponse = $response;
            return new LoggingResponse;
         }
      };

      $handlerStack = (new HandlerStack($handler))
         ->pushMiddleware(new LoggingMiddleware('m1'))
         ->pushMiddleware($replacingMiddleware)
         ->pushMiddleware(new LoggingMiddleware('m3'));

      $request = new LoggingRequest;
      $response = $handlerStack->process($request);

      $this->assertEquals([
         'm3 prepared',
         'm2 replaced',
      ], $request->log);

      $this->assertEquals([
         'm1 prepared',
         'sent',
      ], $replacingMiddleware->newRequest->log);

      $this->assertEquals([
         'm1 processed',
         'm2 replaced',
      ], $replacingMiddleware->oldResponse->log);

      $this->assertEquals([
         'm3 processed'
      ], $response->log);
   }
}

trait Logging {
   public $log = [];
   public function log(...$args): self {
      $msg = implode(' ', array_map('strval', $args));
      $this->log[] = $msg;
      return $this;
   }
}

class LoggingHandler extends Handler {
   public function handle(Request $request): Response {
      $request->log('sent');
      return new LoggingResponse();
   }
}

class LoggingMiddleware extends Middleware {
   use Logging;
   public $name;
   public function __construct(string $name) {
      $this->name = $name;
   }
   public function prepare(Request $request): Request {
      $request->log($this->name, 'prepared');
      return $request;
   }
   public function process(Response $response): Response {
      $response->log($this->name, 'processed');
      return $response;
   }
}

class LoggingRequest extends Request {
   use Logging;
   public function getMethod(): string {
      return "echo";
   }
   public function getArg() {
   }
   public function getResponseClass(): string {
      return LoggingResponse::class;
   }
}

class LoggingResponse extends Response {
   use Logging;
   public $value;
   public function getValue() {
      return $this->value;
   }
}
