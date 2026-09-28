# CineVerse — Datos de prueba

CineVerse incluye una base de datos de prueba con usuarios y reseñas.

La base de datos puede reconstruirse importando el archivo SQL incluido en el repositorio.

## Base de datos incluida

El archivo SQL se encuentra en la carpeta `database` con el nombre:

```text
cineverse.sql
```

Este archivo contiene la estructura y los datos de prueba utilizados por el backend PHP de CineVerse.

Está preparado para MySQL 8 y crea automáticamente la base `cineverse` si no existe. También crea las tablas activas del backend y carga los usuarios y las reseñas incluidas en el conjunto de prueba.

No es necesario crear manualmente la base ni las tablas antes de ejecutarlo.

## Importar la base de datos

Para cargar la base con MySQL Workbench:

1. iniciá el servidor MySQL 8 que utilizará CineVerse;
2. conectate a ese servidor desde MySQL Workbench;
3. abrí `database/cineverse.sql`;
4. comprobá que el script esté asociado al servidor correcto;
5. ejecutá el contenido completo;
6. actualizá el panel **Schemas** al finalizar.

El usuario de MySQL utilizado para ejecutar el archivo debe tener permisos para crear la base, crear y eliminar tablas e insertar datos.

El script está diseñado para reconstruir un entorno de prueba controlado desde cero. Contiene instrucciones `DROP TABLE IF EXISTS`, por lo que elimina las tablas contempladas por el archivo antes de volver a crearlas.

Además de las tres tablas actuales, elimina algunas tablas heredadas de versiones anteriores que ya no utiliza el backend PHP.

Por este motivo, no debe ejecutarse sobre una base cuyos datos se quieran conservar sin realizar antes una copia de seguridad.

## Usuarios de prueba

El dataset contiene 5.000 usuarios. Entre las cuentas verificadas que pueden utilizarse para probar el inicio de sesión se encuentran:

```text
fantasyreels.1@hotmail.com - FantasyReels
filmbuff.2@outlook.com - FilmBuff
delfina_651.3@yahoo.com - Delfina_651
framehunter77.4@gmail.com - FrameHunter77
retroviewer44.5@hotmail.com - RetroViewer44
```

Estas cuentas forman parte del conjunto de datos incluido y tienen el correo marcado como verificado.

## Contraseña de prueba

Los 5.000 usuarios precargados utilizan la misma contraseña:

```text
password
```

La contraseña no se almacena en texto plano. Los registros contienen el hash correspondiente y el backend compara la contraseña ingresada con ese hash durante el inicio de sesión.

## Datos generados

La base contiene:

- 5.000 usuarios registrados.
- 4.800 usuarios con el correo verificado.
- 200 usuarios sin verificar.
- 3.448 usuarios con al menos una reseña.
- 1.552 usuarios sin reseñas.
- 24.322 reseñas.
- 24.322 comentarios distintos, sin duplicados exactos.

Las reseñas se distribuyen de la siguiente manera:

- 553 reseñas de 1 estrella.
- 2.437 reseñas de 2 estrellas.
- 6.191 reseñas de 3 estrellas.
- 8.594 reseñas de 4 estrellas.
- 6.547 reseñas de 5 estrellas.

Además:

- 314 películas distintas tienen al menos una reseña.
- 1.766 reseñas tienen una actualización posterior a su fecha de creación.
- La puntuación media del conjunto es aproximadamente 3,75 sobre 5.

## Correos electrónicos de prueba

Los usuarios precargados utilizan direcciones con formatos variados y los siguientes dominios públicos:

```text
gmail.com
hotmail.com
outlook.com
yahoo.com
```

Estos dominios son reales y las direcciones del dataset no deben tratarse como dominios reservados para pruebas.

Por ese motivo, las pruebas de verificación de correo y recuperación de contraseña deben realizarse con una configuración de correo local o controlada para evitar envíos accidentales a direcciones reales.

## Películas y contenido externo

CineVerse no almacena una copia completa del catálogo de películas en MySQL.

La tabla `reviews` conserva `movie_id` y `movie_title` para identificar la película asociada a cada reseña.

Los datos generales de las películas, como título, sinopsis, posters, reparto, trailers y películas similares, se obtienen desde TMDB durante el funcionamiento de la aplicación.

## Comprobaciones de integridad

La base de datos incluye restricciones estructurales para mantener la coherencia de los datos.

Entre otras cosas:

- `users.username` es único;
- `users.email` es único;
- `users.is_verified` solo admite `0` o `1`;
- `reviews.user_id` referencia a `users.id` mediante una clave foránea;
- la eliminación de un usuario elimina sus reseñas mediante `ON DELETE CASCADE`;
- la combinación `user_id + movie_id` es única, por lo que un usuario no puede tener dos reseñas para la misma película;
- `reviews.rating` debe estar entre 1 y 5;
- `reviews.comment` debe contener entre 20 y 1000 caracteres.

El conjunto de datos incluido cumple además las siguientes comprobaciones:

- no existen pares duplicados `user_id + movie_id`;
- no existen reseñas huérfanas sin un usuario asociado;
- los usuarios sin verificar no tienen reseñas;
- ninguna reseña tiene una fecha de creación anterior al registro de su usuario;
- no existen ratings fuera del rango permitido;
- no existen comentarios fuera del rango de longitud permitido;
- ninguna reseña tiene `updated_at` anterior a `created_at`.

El archivo SQL no carga intentos artificiales de inicio de sesión. Después de una importación limpia, `login_attempts` comienza vacía.

Para comprobar rápidamente el resultado de la importación pueden utilizarse estas consultas:

```sql
USE cineverse;

SHOW TABLES;

SELECT COUNT(*) AS users
FROM users;

SELECT COUNT(*) AS reviews
FROM reviews;

SELECT COUNT(*) AS login_attempts
FROM login_attempts;
```

Los resultados esperados después de una carga limpia son:

```text
users:           5.000
reviews:        24.322
login_attempts:      0
```

## Resultados del conjunto de prueba

Este archivo documenta la composición y la carga del conjunto de datos incluido con CineVerse.

El detalle de las estadísticas obtenidas a partir del contenido concreto de la base se encuentra en [resultado-datos-prueba.md](resultado-datos-prueba.md).
