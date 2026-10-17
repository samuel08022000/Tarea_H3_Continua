<?php
// ============================================================
// FRONTEND - Interfaz web para gestión de productos
// Archivo: index.php
// ============================================================

// URL del backend (API REST)
// Cuando PHP está en el servidor, usa el puerto del backend
define('API_URL', 'http://localhost:8080/?ruta=productos');

// ============================================================
// FUNCIÓN: Llamar a la API del backend
// Parámetros:
//   $url    = dirección completa de la API
//   $metodo = GET, POST, PUT o DELETE
//   $datos  = array con los datos a enviar (solo POST y PUT)
// ============================================================
function llamarAPI($url, $metodo = 'GET', $datos = null) {
    $opciones = [
        'http' => [
            'method'  => $metodo,
            'header'  => 'Content-Type: application/json',
            'timeout' => 10,
        ]
    ];
    
    if ($datos !== null) {
        $opciones['http']['content'] = json_encode($datos);
    }
    
    $contexto   = stream_context_create($opciones);
    $respuesta  = @file_get_contents($url, false, $contexto);
    
    if ($respuesta === false) {
        return null;
    }
    
    return json_decode($respuesta, true);
}

// ============================================================
// PROCESAR FORMULARIOS (POST desde el navegador)
// ============================================================
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    
    // --- CREAR producto ---
    if ($accion === 'crear') {
        $datos = [
            'nombre'           => trim($_POST['nombre']),
            'proveedor'        => trim($_POST['proveedor']),
            'fecha_vencimiento'=> $_POST['fecha_vencimiento'],
            'precio'           => (float)$_POST['precio'],
            'stock'            => (int)$_POST['stock'],
        ];
        
        $resultado = llamarAPI(API_URL, 'POST', $datos);
        
        if ($resultado && isset($resultado['id_creado'])) {
            $mensaje = '✅ Producto creado exitosamente con ID #' . $resultado['id_creado'];
            $tipo_mensaje = 'exito';
        } else {
            $error = $resultado['error'] ?? 'Error desconocido';
            $mensaje = '❌ Error al crear: ' . $error;
            $tipo_mensaje = 'error';
        }
    }
    
    // --- EDITAR producto ---
    elseif ($accion === 'editar') {
        $id    = (int)$_POST['id'];
        $datos = [
            'nombre'           => trim($_POST['nombre']),
            'proveedor'        => trim($_POST['proveedor']),
            'fecha_vencimiento'=> $_POST['fecha_vencimiento'],
            'precio'           => (float)$_POST['precio'],
            'stock'            => (int)$_POST['stock'],
        ];
        
        $resultado = llamarAPI(API_URL . '&id=' . $id, 'PUT', $datos);
        
        if ($resultado && isset($resultado['mensaje'])) {
            $mensaje = '✅ Producto actualizado correctamente';
            $tipo_mensaje = 'exito';
        } else {
            $error = $resultado['error'] ?? 'Error desconocido';
            $mensaje = '❌ Error al editar: ' . $error;
            $tipo_mensaje = 'error';
        }
    }
    
    // --- ELIMINAR lógicamente ---
    elseif ($accion === 'eliminar') {
        $id        = (int)$_POST['id'];
        $resultado = llamarAPI(API_URL . '&id=' . $id, 'DELETE');
        
        if ($resultado && isset($resultado['mensaje'])) {
            $mensaje = '✅ Producto eliminado lógicamente (el registro se conserva en la base de datos)';
            $tipo_mensaje = 'exito';
        } else {
            $error = $resultado['error'] ?? 'Error desconocido';
            $mensaje = '❌ Error al eliminar: ' . $error;
            $tipo_mensaje = 'error';
        }
    }
}

// ============================================================
// OBTENER la lista de productos desde el backend
// ============================================================
$productos = llamarAPI(API_URL);
if ($productos === null) {
    $productos = [];
    $error_conexion = true;
} else {
    $error_conexion = false;
}

// Verificar si se debe mostrar formulario de edición
$editar_id = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$producto_editar = null;
if ($editar_id > 0) {
    $producto_editar = llamarAPI(API_URL . '&id=' . $editar_id);
}

// Verificar si se debe mostrar formulario de creación
$mostrar_crear = isset($_GET['crear']) && $_GET['crear'] === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimarket Pro - Gestión de Productos</title>
    <style>
        /* =============================================
           ESTILOS GENERALES
        ============================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #e0e0e0;
        }

        /* =============================================
           HEADER / ENCABEZADO
        ============================================= */
        header {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
        }

        header h1 span {
            color: #e94560;
        }

        header p {
            font-size: 0.85rem;
            color: #a0a0b0;
        }

        /* =============================================
           CONTENIDO PRINCIPAL
        ============================================= */
        .contenedor {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* =============================================
           MENSAJES DE ÉXITO / ERROR
        ============================================= */
        .mensaje {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .mensaje.exito {
            background: rgba(46, 213, 115, 0.15);
            border: 1px solid rgba(46, 213, 115, 0.4);
            color: #2ed573;
        }

        .mensaje.error {
            background: rgba(233, 69, 96, 0.15);
            border: 1px solid rgba(233, 69, 96, 0.4);
            color: #e94560;
        }

        .alerta-conexion {
            background: rgba(255, 165, 0, 0.15);
            border: 1px solid rgba(255, 165, 0, 0.4);
            color: #ffa500;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        /* =============================================
           BOTONES
        ============================================= */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-verde {
            background: linear-gradient(135deg, #2ed573, #1abc9c);
            color: #fff;
        }

        .btn-verde:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 213, 115, 0.4);
        }

        .btn-azul {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
        }

        .btn-azul:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-rojo {
            background: linear-gradient(135deg, #e94560, #c0392b);
            color: #fff;
        }

        .btn-rojo:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 69, 96, 0.4);
        }

        .btn-gris {
            background: rgba(255,255,255,0.1);
            color: #a0a0b0;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-gris:hover {
            background: rgba(255,255,255,0.2);
        }

        /* =============================================
           TABLA DE PRODUCTOS
        ============================================= */
        .seccion-tabla {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .seccion-tabla h2 {
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .barra-acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .total-productos {
            font-size: 0.9rem;
            color: #a0a0b0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: rgba(255,255,255,0.08);
            padding: 12px 15px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: #a0a0b0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:first-child { border-radius: 8px 0 0 8px; }
        thead th:last-child  { border-radius: 0 8px 8px 0; }

        tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }

        tbody td {
            padding: 14px 15px;
            font-size: 0.9rem;
            color: #d0d0d0;
        }

        .id-badge {
            display: inline-block;
            background: rgba(233, 69, 96, 0.2);
            color: #e94560;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .stock-badge {
            display: inline-block;
            background: rgba(46, 213, 115, 0.15);
            color: #2ed573;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .precio-texto {
            font-weight: 600;
            color: #f0c040;
        }

        .acciones-celda {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-tabla {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .sin-productos {
            text-align: center;
            padding: 40px;
            color: #606080;
            font-size: 1.1rem;
        }

        /* =============================================
           FORMULARIOS
        ============================================= */
        .seccion-formulario {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .seccion-formulario h2 {
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .grid-formulario {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 600px) {
            .grid-formulario { grid-template-columns: 1fr; }
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .campo.ancho-completo {
            grid-column: span 2;
        }

        @media (max-width: 600px) {
            .campo.ancho-completo { grid-column: span 1; }
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #a0a0b0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"] {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 12px 15px;
            color: #e0e0e0;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
            width: 100%;
        }

        input:focus {
            outline: none;
            border-color: #e94560;
        }

        .botones-formulario {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<header>
    <div>
        <h1>🛒 Minimarket<span>Pro</span></h1>
        <p>Sistema de Gestión de Productos - Microservicios</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-gris">🏠 Inicio</a>
    </div>
</header>

<div class="contenedor">

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_conexion): ?>
        <div class="alerta-conexion">
            ⚠️ <strong>No se puede conectar al backend.</strong>
            Asegúrate de que el servidor backend esté corriendo en 
            <code>http://localhost:8080</code>
        </div>
    <?php endif; ?>

    <!-- ================================================
         FORMULARIO: CREAR NUEVO PRODUCTO
    ================================================ -->
    <?php if ($mostrar_crear): ?>
    <div class="seccion-formulario">
        <h2>➕ Crear Nuevo Producto</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="accion" value="crear">
            <div class="grid-formulario">
                <div class="campo">
                    <label for="nombre">Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" 
                           placeholder="Ej: Leche Entera 1L" required>
                </div>
                <div class="campo">
                    <label for="proveedor">Proveedor</label>
                    <input type="text" id="proveedor" name="proveedor" 
                           placeholder="Ej: Distribuidora Norte" required>
                </div>
                <div class="campo">
                    <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                    <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required>
                </div>
                <div class="campo">
                    <label for="precio">Precio (S/)</label>
                    <input type="number" id="precio" name="precio" 
                           step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="campo">
                    <label for="stock">Stock (unidades)</label>
                    <input type="number" id="stock" name="stock" 
                           min="0" placeholder="0" required>
                </div>
            </div>
            <div class="botones-formulario">
                <button type="submit" class="btn btn-verde">💾 Guardar Producto</button>
                <a href="index.php" class="btn btn-gris">❌ Cancelar</a>
            </div>
        </form>
    </div>

    <!-- ================================================
         FORMULARIO: EDITAR PRODUCTO
    ================================================ -->
    <?php elseif ($producto_editar): ?>
    <div class="seccion-formulario">
        <h2>✏️ Editar Producto #<?php echo $producto_editar['id']; ?></h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?php echo $producto_editar['id']; ?>">
            <div class="grid-formulario">
                <div class="campo">
                    <label for="nombre">Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($producto_editar['nombre']); ?>" required>
                </div>
                <div class="campo">
                    <label for="proveedor">Proveedor</label>
                    <input type="text" id="proveedor" name="proveedor" 
                           value="<?php echo htmlspecialchars($producto_editar['proveedor']); ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                    <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" 
                           value="<?php echo $producto_editar['fecha_vencimiento']; ?>" required>
                </div>
                <div class="campo">
                    <label for="precio">Precio (S/)</label>
                    <input type="number" id="precio" name="precio" 
                           step="0.01" min="0" 
                           value="<?php echo $producto_editar['precio']; ?>" required>
                </div>
                <div class="campo">
                    <label for="stock">Stock (unidades)</label>
                    <input type="number" id="stock" name="stock" 
                           min="0" 
                           value="<?php echo $producto_editar['stock']; ?>" required>
                </div>
            </div>
            <div class="botones-formulario">
                <button type="submit" class="btn btn-azul">💾 Actualizar Producto</button>
                <a href="index.php" class="btn btn-gris">❌ Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ================================================
         TABLA DE PRODUCTOS ACTIVOS
    ================================================ -->
    <div class="seccion-tabla">
        <div class="barra-acciones">
            <h2>📦 Productos Activos</h2>
            <div style="display:flex; align-items:center; gap:15px;">
                <span class="total-productos">
                    Total: <strong><?php echo count($productos); ?></strong> productos
                </span>
                <a href="index.php?crear=1" class="btn btn-verde">
                    ➕ Nuevo Producto
                </a>
            </div>
        </div>

        <?php if (empty($productos)): ?>
            <div class="sin-productos">
                📭 No hay productos activos registrados.<br>
                <small>Haz clic en "Nuevo Producto" para agregar el primero.</small>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Proveedor</th>
                        <th>Fecha Venc.</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><span class="id-badge">#<?php echo $p['id']; ?></span></td>
                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($p['proveedor']); ?></td>
                        <td><?php echo $p['fecha_vencimiento']; ?></td>
                        <td class="precio-texto">S/ <?php echo number_format($p['precio'], 2); ?></td>
                        <td><span class="stock-badge"><?php echo $p['stock']; ?></span></td>
                        <td>
                            <div class="acciones-celda">
                                <!-- Botón Editar -->
                                <a href="index.php?editar=<?php echo $p['id']; ?>" 
                                   class="btn-tabla btn-azul">✏️ Editar</a>

                                <!-- Botón Eliminar lógicamente -->
                                <form method="POST" action="index.php" 
                                      onsubmit="return confirm('¿Eliminar lógicamente el producto #<?php echo $p['id']; ?>?\n\nEl registro se conservará en la base de datos.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-tabla btn-rojo">🗑️ Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pie de página informativo -->
    <div style="text-align:center; padding:20px; color:#505070; font-size:0.8rem;">
        <p>Frontend PHP → Backend API REST (localhost:8080) → MySQL (localhost:3306)</p>
        <p>Arquitectura de Microservicios | PRO-314</p>
    </div>

</div>
</body>
</html>
