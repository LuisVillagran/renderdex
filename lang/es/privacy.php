<?php

return [

    'title' => 'Privacidad',
    'heading' => 'Política de privacidad',
    'updated' => 'Última actualización: 26 de octubre de 2026.',
    'intro' => 'Renderdex es un proyecto personal, sin publicidad ni analítica. Esta página explica qué datos consulta, cuáles guarda y por cuánto tiempo.',
    'contents' => 'Contenido',

    'sections' => [
        [
            'id' => 'que-consultamos',
            'title' => 'Qué consultamos',
            'items' => [
                'Solo consultamos un perfil cuando tú lo pides: al pegar tu URL o SteamID, o al iniciar sesión con Steam.',
                'A la Steam Web API le pedimos el SteamID64 que corresponde a una URL personalizada y la lista de juegos de esa biblioteca, con las horas jugadas.',
                'Solo si abres la pestaña "Lista de deseados", también pedimos los juegos de esa lista (solo sus AppIDs).',
                'Si inicias sesión, también pedimos tu nombre visible y tu avatar, para mostrarlos en el encabezado.',
                'Steam solo entrega bibliotecas públicas: iniciar sesión no nos da acceso a datos privados.',
            ],
        ],
        [
            'id' => 'que-guardamos',
            'title' => 'Qué guardamos y por cuánto tiempo',
            'items' => [
                'La biblioteca y la lista de deseados no se guardan en nuestra base de datos. Quedan en caché en el servidor durante 1 hora, identificadas por un hash de tu SteamID64, para no repetir la consulta a Steam. Después se borran.',
                'No hay cuentas ni tabla de usuarios. No guardamos SteamIDs en registros (logs) ni en analítica.',
                'Para cuidar el límite de la API de Steam, contamos cuántas consultas llegan desde cada IP. Ese contador usa un hash de la IP y dura un minuto.',
            ],
        ],
        [
            'id' => 'sesion',
            'title' => 'Inicio de sesión con Steam',
            'items' => [
                'Usamos "Sign in through Steam" (OpenID). Tu contraseña la escribes en Steam: Renderdex nunca la ve ni la guarda.',
                'Steam nos devuelve tu SteamID64. Junto con tu nombre visible y tu avatar, vive solo en la sesión mientras esté abierta.',
                'El botón "Cerrar sesión" destruye la sesión y esos datos.',
            ],
        ],
        [
            'id' => 'cookies',
            'title' => 'Cookies y almacenamiento local',
            'items' => [
                'Como cualquier sitio hecho con Laravel, cada visita tiene una cookie de sesión y una de protección contra CSRF. Son necesarias para que el sitio funcione.',
                'La sesión se guarda en el servidor junto con la IP y el navegador desde los que se abrió, y se borra tras 2 horas de inactividad. Si eliges un idioma, se recuerda en la sesión.',
                'Tu elección de "Mi GPU" (marca y generación), el tema de colores y si prefieres ver todas las tecnologías se guardan en el almacenamiento local de tu navegador (localStorage). Solo se usan para mostrarte la página así y no se guardan en el servidor.',
                'No usamos cookies de terceros, analítica ni publicidad.',
            ],
        ],
        [
            'id' => 'terceros',
            'title' => 'Servicios de terceros',
            'items' => [
                'Las portadas de los juegos y el botón de inicio de sesión se cargan desde los servidores de Steam (Valve), que reciben tu IP como en cualquier visita a su sitio.',
                'Los datos de compatibilidad vienen de PCGamingWiki y se sincronizan una vez al día desde nuestro servidor. Tu visita nunca genera consultas a la wiki.',
                'Las fuentes tipográficas se sirven desde nuestro propio servidor.',
            ],
        ],
        [
            'id' => 'contacto',
            'title' => 'Contacto',
            'body' => 'Si tienes dudas o quieres reportar un problema, abre un issue en el :repository.',
            'repository' => 'repositorio del proyecto',
        ],
    ],

];
