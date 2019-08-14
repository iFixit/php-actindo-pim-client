<?php
declare(strict_types = 1);

namespace Actindo\Pim;

abstract class Handler {
   abstract public function handle(Request $request): Response;
}
