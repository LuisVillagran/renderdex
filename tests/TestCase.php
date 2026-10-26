<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony simula un navegador en inglés ("en-us"); la interfaz se prueba en español,
        // salvo en los tests de idioma, que cambian este encabezado.
        $this->withHeader('Accept-Language', 'es');
    }
}
