<?php

it('lists every link the QR code sends the audience to', function () {
    $this->get(route('thank-you'))
        ->assertOk()
        ->assertSee('https://forms.gle/rwjXKDxRBH8S8beq5')
        ->assertSee('https://twitter.com/jigar_dhulla')
        ->assertSee(url('/slides.html'))
        ->assertSee('https://github.com/jigar-dhulla/laravel-bengaluru')
        ->assertSee('https://github.com/jigar-dhulla/laravel-whatsapp-ai-agent');
});
