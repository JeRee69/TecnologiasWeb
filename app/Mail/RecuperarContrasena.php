<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RecuperarContrasena extends Mailable
{
    public function __construct(
        public string $nombre,
        public string $enlace,
        public int $minutosVigencia,
    ) {}

    public function build(): static
    {
        return $this->subject('Restablece tu contraseña de EdiConnect')
            ->view('emails.recuperar-contrasena')
            ->text('emails.recuperar-contrasena-text');
    }
}
