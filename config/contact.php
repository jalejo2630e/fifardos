<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destinatario del formulario de contacto
    |--------------------------------------------------------------------------
    | A dónde llegan los mensajes enviados desde el formulario del landing.
    | Definilo con CONTACT_MAIL_TO en el .env; si no, cae al remitente por defecto.
    */
    'to' => env('CONTACT_MAIL_TO', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];
