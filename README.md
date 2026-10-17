# Minimarket Pro

Este proyecto es un sistema para gestionar productos de un minimarket.
Tiene 3 partes: la base de datos, el backend y el frontend.

---

## Como funciona

El frontend (lo que se ve en el navegador) le pide datos al backend.
El backend le pregunta a la base de datos MySQL.
El frontend NUNCA habla directo con MySQL.

---

## Que necesitas tener instalado

- Docker Desktop (para la base de datos)
- PHP (para el backend y el frontend)

Para verificar que los tienes, abre una terminal y escribe:

    docker --version
    php --version

Si aparece un numero de version, esta bien.

---

## Como ejecutar el proyecto (paso a paso)

### Paso 1 - Abrir Docker Desktop

Busca Docker Desktop en tu escritorio o en el menu inicio y abrelo.
Espera hasta que aparezca el icono de Docker en la barra de tareas.

### Paso 2 - Levantar la base de datos

Abre una terminal en la carpeta del proyecto y escribe:

    docker-compose up -d

Esto crea la base de datos MySQL automaticamente.
Espera como 30 segundos antes de continuar.

### Paso 3 - Iniciar el backend

Abre OTRA terminal, ve a la carpeta backend y escribe:

    cd backend
    php -S localhost:8080

Deja esa terminal abierta. El backend corre en el puerto 8080.

### Paso 4 - Iniciar el frontend

Abre OTRA terminal mas, ve a la carpeta frontend y escribe:

    cd frontend
    php -S localhost:8081

### Paso 5 - Abrir en el navegador

Abre Chrome o cualquier navegador y escribe en la barra de direcciones:

    http://localhost:8081

Ahi vas a ver la pagina con la tabla de productos.

---

## Que puedes hacer en la pagina

- Ver la lista de productos activos
- Crear un producto nuevo (boton verde)
- Editar un producto (boton azul)
- Eliminar un producto (boton rojo) - el producto NO se borra de la base de datos, solo se oculta

---

## Como ver los datos en MySQL directamente

Abre una terminal y escribe:

    docker exec -it mysql_minimarket mysql -u minimarket_user -pminimarket1234 db_minimarket_pro413 -e "SELECT * FROM Producto;"

Ahi puedes ver todos los productos, incluso los eliminados.

---

## Como apagar todo cuando termines

Cierra las terminales del backend y frontend con Ctrl+C.

Para apagar MySQL escribe:

    docker-compose down

---

## Datos de la base de datos (por si los necesitas)

- Nombre de la base de datos: db_minimarket_pro413
- Usuario: minimarket_user
- Contrasena: minimarket1234
- Puerto: 3306
