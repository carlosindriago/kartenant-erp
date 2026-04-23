<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function test_basic_example(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
         // 2. Esperar unos segundos (por si es lento)
                ->pause(3000)
            // 3. Buscar texto que SABEMOS que está ahí (por el diseño que hicimos)
            // Puede ser "Bienvenido" o el nombre de la tienda "Fruteria"
                ->assertSee('Emporio Digital');
        });
    }
}
