<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Loader;

class InvokableTestController
{
    public function __invoke(): void
    {
    }
}
