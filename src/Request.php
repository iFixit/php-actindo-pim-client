<?php
declare(strict_types = 1);

namespace Actindo\Pim;

abstract class Request {
   /**
    * Returns the name of the JSON RPC method for which this request builds an
    * argument.
    */
   abstract public function getMethod(): string;

   /**
    * Returns the single argument for the JSON RPC method associated with this
    * request. Normally, JSON RPC methods accept an array of arguments, but the
    * Actindo API is special in that it only ever expects an array with one
    * argument, which will usually be an object with nested data.
    */
   abstract public function getArg();

   /**
    * Returns the fully-qualified name of a class that can import a response
    * from the remote procedure.
    */
   abstract public function getResponseClass(): string;
}
