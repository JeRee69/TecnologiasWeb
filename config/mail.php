<?php

return [
    'default' => env('MAIL_MAILER', 'resend'),
    'mailers' => [
        'resend' => ['transport' => 'resend'],
        // Solo para desarrollo explícito; no confirma entrega al destinatario.
        'log' => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        'array' => ['transport' => 'array'],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'onboarding@resend.dev'),
        'name' => env('MAIL_FROM_NAME', 'EdiConnect'),
    ],
];
