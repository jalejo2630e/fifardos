<?php

return [
    // Comunes a todas las salas
    'max_families' => 3,
    'min_families' => 2,
    'reveal_seconds' => 6,   // pausa mostrando el resultado entre rondas

    // ------------------------------------------------------------ Dibuja y Adivina
    'pictionary' => [
        'rounds_per_family' => 2,
        'round_seconds' => 80,
        'reveal_seconds' => 4,   // cuenta regresiva antes de la próxima ronda

        'words' => [
            'perro', 'gato', 'elefante', 'jirafa', 'pingüino', 'tortuga', 'caballo', 'delfín',
            'mariposa', 'araña', 'cocodrilo', 'pulpo', 'canguro', 'búho', 'ballena', 'abeja',
            'pizza', 'helado', 'banana', 'hamburguesa', 'sandía', 'zanahoria', 'huevo frito',
            'torta', 'empanada', 'sushi', 'palomitas', 'chupetín',
            'silla', 'paraguas', 'lámpara', 'reloj', 'llave', 'escoba', 'tijeras', 'martillo',
            'teléfono', 'televisor', 'guitarra', 'bicicleta', 'cepillo de dientes', 'anteojos',
            'sol', 'arcoíris', 'volcán', 'montaña', 'árbol', 'flor', 'estrella', 'nube', 'río',
            'cascada', 'isla', 'cactus',
            'avión', 'barco', 'tren', 'cohete', 'globo aerostático', 'ambulancia', 'submarino',
            'helicóptero', 'patineta',
            'pirata', 'astronauta', 'payaso', 'robot', 'fantasma', 'superhéroe', 'sirena',
            'dragón', 'mago', 'vaquero',
            'fútbol', 'básquet', 'nadar', 'esquiar', 'boxeo', 'ajedrez', 'saltar la cuerda',
            'pescar', 'bailar',
            'castillo', 'faro', 'circo', 'iglú', 'pirámide', 'carpa de camping',
            'corona', 'tesoro', 'semáforo', 'ancla', 'brújula', 'imán', 'esqueleto', 'momia',
        ],
    ],

    // ------------------------------------------------------------ Trivia
    'trivia' => [
        'rounds' => 8,
        'round_seconds' => 22,
        'reveal_seconds' => 5,
        // answer = índice (0..3) de la opción correcta
        'questions' => [
            ['q' => '¿Cuál es el planeta más grande del sistema solar?', 'options' => ['Marte', 'Júpiter', 'Saturno', 'La Tierra'], 'answer' => 1],
            ['q' => '¿En qué continente está Egipto?', 'options' => ['Asia', 'Europa', 'África', 'Oceanía'], 'answer' => 2],
            ['q' => '¿Cuántos lados tiene un hexágono?', 'options' => ['5', '6', '7', '8'], 'answer' => 1],
            ['q' => '¿Quién pintó La Mona Lisa?', 'options' => ['Picasso', 'Van Gogh', 'Da Vinci', 'Dalí'], 'answer' => 2],
            ['q' => '¿Cuál es el animal terrestre más rápido?', 'options' => ['León', 'Guepardo', 'Caballo', 'Galgo'], 'answer' => 1],
            ['q' => '¿En qué país se inventó la pizza?', 'options' => ['Francia', 'España', 'Italia', 'Grecia'], 'answer' => 2],
            ['q' => '¿Cuántos huesos tiene el cuerpo humano adulto?', 'options' => ['206', '150', '300', '250'], 'answer' => 0],
            ['q' => '¿Cuál es el océano más grande?', 'options' => ['Atlántico', 'Índico', 'Ártico', 'Pacífico'], 'answer' => 3],
            ['q' => '¿Qué gas respiramos para vivir?', 'options' => ['Hidrógeno', 'Oxígeno', 'Nitrógeno', 'Helio'], 'answer' => 1],
            ['q' => '¿Cuántos jugadores tiene un equipo de fútbol en cancha?', 'options' => ['9', '10', '11', '12'], 'answer' => 2],
            ['q' => '¿Cuál es la capital de Japón?', 'options' => ['Pekín', 'Seúl', 'Tokio', 'Bangkok'], 'answer' => 2],
            ['q' => '¿De qué color es la clorofila?', 'options' => ['Roja', 'Verde', 'Azul', 'Amarilla'], 'answer' => 1],
            ['q' => '¿Cuál es el metal líquido a temperatura ambiente?', 'options' => ['Hierro', 'Mercurio', 'Oro', 'Plomo'], 'answer' => 1],
            ['q' => '¿Cuántos colores tiene el arcoíris?', 'options' => ['5', '6', '7', '8'], 'answer' => 2],
            ['q' => '¿Qué instrumento tiene 88 teclas?', 'options' => ['Guitarra', 'Piano', 'Violín', 'Arpa'], 'answer' => 1],
            ['q' => '¿En qué país están las pirámides de Giza?', 'options' => ['México', 'Egipto', 'Perú', 'Irak'], 'answer' => 1],
            ['q' => '¿Cuál es el río más largo del mundo?', 'options' => ['Nilo', 'Amazonas', 'Misisipi', 'Yangtsé'], 'answer' => 1],
            ['q' => '¿Cuántas patas tiene una araña?', 'options' => ['6', '8', '10', '12'], 'answer' => 1],
            ['q' => '¿Qué planeta es conocido como el planeta rojo?', 'options' => ['Venus', 'Marte', 'Mercurio', 'Neptuno'], 'answer' => 1],
            ['q' => '¿Cuál es la moneda de Estados Unidos?', 'options' => ['Euro', 'Peso', 'Dólar', 'Libra'], 'answer' => 2],
        ],
    ],

    // ------------------------------------------------------------ Tutti Frutti / ¡Basta!
    'tuttifrutti' => [
        'rounds' => 4,
        'round_seconds' => 90,
        'reveal_seconds' => 9,
        'categories' => ['Nombre', 'Apellido', 'País o ciudad', 'Animal', 'Fruta o verdura', 'Color', 'Objeto', 'Marca'],
        'letters' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'V'],
    ],
];
