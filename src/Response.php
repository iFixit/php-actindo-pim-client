<?php
declare(strict_types = 1);

namespace Actindo\Pim;

/**
 * A wrapper around a raw response. This exists as a place to inject behavior
 * for different kinds of responses (e.g., to facilitate pagination).
 */
abstract class Response {
   /**
    * Returns the response's wrapped value, usually the hydrated return value
    * received from a JSON RPC method call.
    */
   abstract public function getValue();
}
