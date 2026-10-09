<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_switch_the_interface_locale(): void
    {
        $response = $this->from('/')
            ->put(route('locale.update'), ['locale' => 'es']);

        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'es');

        $this->withSession(['locale' => 'es'])->get('/')->assertOk();
        $this->assertSame('es', app()->getLocale());
    }

    public function test_an_invalid_locale_is_rejected(): void
    {
        $response = $this->from('/')
            ->put(route('locale.update'), ['locale' => 'fr']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('locale');
    }
}
