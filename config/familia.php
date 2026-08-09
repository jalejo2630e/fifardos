<?php

return [
    // Comunes a todas las salas
    'max_families' => 10,   // máximo de participantes por sala
    'min_families' => 2,
    'prune_hours' => 2,     // borra la sala si nadie estuvo presente en estas horas
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
            // Animales
            'zorro', 'lobo', 'oso', 'oveja', 'vaca', 'cerdo', 'gallina', 'pato', 'rana', 'serpiente',
            'murciélago', 'ardilla', 'erizo', 'foca', 'rinoceronte', 'hipopótamo', 'camello', 'llama', 'koala', 'panda',
            'flamenco', 'loro', 'tucán', 'cangrejo', 'caracol', 'medusa', 'caballito de mar', 'escarabajo', 'oruga', 'lechuza',
            // Plantas
            'girasol', 'rosa', 'margarita', 'tulipán', 'hongo', 'helecho', 'palmera', 'pino', 'roble', 'bambú',
            'trébol', 'nenúfar', 'bonsái', 'hoja', 'semilla', 'maceta',
            // Otros
            'cometa', 'molino', 'tobogán', 'columpio',
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
            ['q' => '¿Cuántas patas tiene un insecto?', 'options' => ['4', '6', '8', '10'], 'answer' => 1],
            ['q' => '¿Qué animal es el rey de la selva?', 'options' => ['Tigre', 'León', 'Elefante', 'Oso'], 'answer' => 1],
            ['q' => '¿Cuál es el planeta más cercano al Sol?', 'options' => ['Venus', 'Mercurio', 'Marte', 'La Tierra'], 'answer' => 1],
            ['q' => '¿De qué animal obtenemos la lana?', 'options' => ['Vaca', 'Oveja', 'Cerdo', 'Caballo'], 'answer' => 1],
            ['q' => '¿Cuántos días tiene una semana?', 'options' => ['5', '6', '7', '8'], 'answer' => 2],
            ['q' => '¿Qué fruta es amarilla y curva?', 'options' => ['Manzana', 'Banana', 'Uva', 'Pera'], 'answer' => 1],
            ['q' => '¿Cuál es el idioma más hablado en Brasil?', 'options' => ['Español', 'Portugués', 'Inglés', 'Francés'], 'answer' => 1],
            ['q' => '¿Cuántos minutos tiene una hora?', 'options' => ['30', '45', '60', '90'], 'answer' => 2],
            ['q' => '¿Qué usan los médicos para escuchar el corazón?', 'options' => ['Termómetro', 'Estetoscopio', 'Jeringa', 'Lupa'], 'answer' => 1],
            ['q' => '¿Cuál es el continente más grande?', 'options' => ['África', 'Asia', 'América', 'Europa'], 'answer' => 1],
            ['q' => '¿A qué temperatura se congela el agua?', 'options' => ['0°C', '50°C', '100°C', '-50°C'], 'answer' => 0],
            ['q' => '¿Cuál de estos es un mamífero marino?', 'options' => ['Tiburón', 'Delfín', 'Pulpo', 'Cangrejo'], 'answer' => 1],
            ['q' => '¿De qué color es el sol de la bandera de Argentina?', 'options' => ['Blanco', 'Amarillo', 'Rojo', 'Azul'], 'answer' => 1],
            ['q' => '¿Cuántas ruedas tiene una bicicleta?', 'options' => ['1', '2', '3', '4'], 'answer' => 1],
            ['q' => '¿Qué órgano bombea la sangre?', 'options' => ['Pulmón', 'Corazón', 'Hígado', 'Riñón'], 'answer' => 1],
            ['q' => '¿En qué estación hace más calor?', 'options' => ['Invierno', 'Verano', 'Otoño', 'Primavera'], 'answer' => 1],
            ['q' => '¿Cuántos lados tiene un triángulo?', 'options' => ['2', '3', '4', '5'], 'answer' => 1],
            ['q' => '¿Qué planeta tiene anillos famosos?', 'options' => ['Marte', 'Saturno', 'Venus', 'Júpiter'], 'answer' => 1],
            ['q' => '¿Cuál es la capital de Francia?', 'options' => ['Roma', 'Madrid', 'París', 'Berlín'], 'answer' => 2],
            ['q' => '¿Qué se usa para escribir en el pizarrón?', 'options' => ['Tiza', 'Martillo', 'Cuchara', 'Peine'], 'answer' => 0],
            ['q' => '¿Cuántos dedos tiene una mano?', 'options' => ['4', '5', '6', '10'], 'answer' => 1],
            ['q' => '¿Qué animal tiene trompa y es muy grande?', 'options' => ['Jirafa', 'Elefante', 'Rinoceronte', 'Camello'], 'answer' => 1],
            ['q' => '¿Cuál es la capital de Italia?', 'options' => ['Milán', 'Nápoles', 'Roma', 'Turín'], 'answer' => 2],
            ['q' => '¿Qué producen las abejas?', 'options' => ['Leche', 'Miel', 'Seda', 'Lana'], 'answer' => 1],
            ['q' => '¿Cuántos planetas tiene el sistema solar?', 'options' => ['7', '8', '9', '10'], 'answer' => 1],
            ['q' => '¿Qué animal cambia de color para camuflarse?', 'options' => ['Camaleón', 'Perro', 'Caballo', 'Vaca'], 'answer' => 0],
            ['q' => '¿Cuál es el metal precioso de color amarillo?', 'options' => ['Plata', 'Oro', 'Cobre', 'Hierro'], 'answer' => 1],
            ['q' => '¿Qué usamos para ver las estrellas de cerca?', 'options' => ['Microscopio', 'Telescopio', 'Lupa', 'Espejo'], 'answer' => 1],
            ['q' => '¿Cuál es la capital de España?', 'options' => ['Barcelona', 'Madrid', 'Sevilla', 'Valencia'], 'answer' => 1],
            ['q' => '¿Cuántos meses tiene un año?', 'options' => ['10', '11', '12', '13'], 'answer' => 2],
            ['q' => '¿Qué necesita una planta para crecer, además de agua?', 'options' => ['Oscuridad', 'Luz solar', 'Sal', 'Aceite'], 'answer' => 1],
            ['q' => '¿Qué ave no vuela y vive en la Antártida?', 'options' => ['Águila', 'Pingüino', 'Loro', 'Gaviota'], 'answer' => 1],
            ['q' => '¿Qué forma tiene una pelota?', 'options' => ['Cuadrada', 'Esfera', 'Triángulo', 'Cubo'], 'answer' => 1],
            ['q' => '¿Cuál es el río más largo de Sudamérica?', 'options' => ['Paraná', 'Amazonas', 'Orinoco', 'Magdalena'], 'answer' => 1],
            ['q' => '¿Cuántas cuerdas tiene una guitarra clásica?', 'options' => ['4', '5', '6', '7'], 'answer' => 2],
            ['q' => '¿En qué planeta vivimos?', 'options' => ['Marte', 'La Tierra', 'La Luna', 'Venus'], 'answer' => 1],
            ['q' => '¿Cuál es la capital de México?', 'options' => ['Guadalajara', 'Cancún', 'Ciudad de México', 'Monterrey'], 'answer' => 2],
            ['q' => '¿Qué usamos para protegernos de la lluvia?', 'options' => ['Paraguas', 'Abanico', 'Linterna', 'Reloj'], 'answer' => 0],
            ['q' => '¿Cuál es el animal más alto del mundo?', 'options' => ['Elefante', 'Jirafa', 'Caballo', 'Camello'], 'answer' => 1],
            ['q' => '¿Qué color se forma al mezclar azul y amarillo?', 'options' => ['Verde', 'Naranja', 'Violeta', 'Marrón'], 'answer' => 0],
            ['q' => '¿Qué deporte se juega con raqueta y una pelota amarilla?', 'options' => ['Fútbol', 'Tenis', 'Básquet', 'Golf'], 'answer' => 1],
            ['q' => '¿Cuál es la estrella más cercana a la Tierra?', 'options' => ['La Luna', 'El Sol', 'Marte', 'Sirio'], 'answer' => 1],
            ['q' => '¿Qué animal ronronea?', 'options' => ['Perro', 'Gato', 'Vaca', 'Pato'], 'answer' => 1],
            ['q' => '¿Cuál es la capital de Estados Unidos?', 'options' => ['Nueva York', 'Washington D.C.', 'Los Ángeles', 'Chicago'], 'answer' => 1],
            ['q' => '¿Qué comen principalmente los conejos?', 'options' => ['Carne', 'Zanahorias', 'Pescado', 'Insectos'], 'answer' => 1],
            ['q' => '¿Cuántas letras tiene el abecedario español (aprox.)?', 'options' => ['21', '27', '30', '33'], 'answer' => 1],
            ['q' => '¿Cuál es el ave nacional que corre muy rápido y no vuela?', 'options' => ['Avestruz', 'Cóndor', 'Colibrí', 'Búho'], 'answer' => 0],
            ['q' => '¿Qué planeta es famoso por ser rojo?', 'options' => ['Júpiter', 'Marte', 'Saturno', 'Neptuno'], 'answer' => 1],
            ['q' => '¿Cuánto es 7 x 8?', 'options' => ['54', '56', '63', '48'], 'answer' => 1],
            ['q' => '¿Qué instrumento tiene teclas blancas y negras?', 'options' => ['Violín', 'Piano', 'Flauta', 'Batería'], 'answer' => 1],
            ['q' => '¿Cuál es el hueso más largo del cuerpo?', 'options' => ['Fémur', 'Cráneo', 'Costilla', 'Columna'], 'answer' => 0],
        ],
    ],

    // ------------------------------------------------------------ Tutti Frutti / ¡Basta!
    'tuttifrutti' => [
        'rounds' => 4,
        'round_seconds' => 90,
        'validate_seconds' => 35,   // tiempo para revisar/validar las respuestas
        'reveal_seconds' => 9,
        'categories' => ['Nombre', 'Apellido', 'País o ciudad', 'Animal', 'Fruta o verdura', 'Color', 'Objeto', 'Marca'],
        'letters' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'V'],
    ],

    // ------------------------------------------------------------ Ahorcado
    'hangman' => [
        'rounds' => 5,
        'round_seconds' => 100,
        'max_misses' => 6,   // partes del muñeco (cabeza, cuerpo, 2 brazos, 2 piernas)
        // Palabras sin tildes (para el teclado); se comparan en minúscula (ñ incluida).
        'words' => [
            'perro', 'gato', 'elefante', 'jirafa', 'tortuga', 'caballo', 'delfin', 'mariposa', 'araña', 'pulpo',
            'ballena', 'abeja', 'zorro', 'lobo', 'oveja', 'gallina', 'serpiente', 'cangrejo', 'caracol', 'pinguino',
            'pizza', 'helado', 'banana', 'hamburguesa', 'sandia', 'zanahoria', 'empanada', 'manzana', 'naranja', 'chocolate',
            'guitarra', 'piano', 'bicicleta', 'telefono', 'televisor', 'computadora', 'ventana', 'puerta', 'cocina', 'escoba',
            'escuela', 'familia', 'amigo', 'pelota', 'futbol', 'basquet', 'montaña', 'playa', 'arbol', 'girasol',
            'estrella', 'planeta', 'cohete', 'robot', 'pirata', 'dragon', 'castillo', 'tesoro', 'brujula', 'volcan',
        ],
    ],
];
