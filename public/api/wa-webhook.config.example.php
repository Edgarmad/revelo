<?php

return [
    // URL del webhook del escenario "Milapro - Pausa automatica por agente" en Make.
    'make_webhook_url' => 'https://hook.us2.make.com/REEMPLAZAR',

    // Debe coincidir exactamente con el token que se escriba en el panel de Meta.
    'verify_token' => 'REEMPLAZAR_CON_EL_TOKEN',

    // Meta > Configuracion de la app > Basica > Clave secreta de la app.
    // Sirve para verificar la firma de cada evento y rechazar los falsos.
    'app_secret' => 'REEMPLAZAR_CON_LA_CLAVE_SECRETA',
];
