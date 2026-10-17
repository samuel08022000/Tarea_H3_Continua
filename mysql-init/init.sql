-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS db_minimarket_pro413;

-- Usar la base de datos
USE db_minimarket_pro413;

-- Crear la tabla Producto con la estructura exacta requerida
CREATE TABLE IF NOT EXISTS Producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    proveedor VARCHAR(100) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    stock INT NOT NULL CHECK (stock >= 0),
    eliminado TINYINT(1) NOT NULL DEFAULT 0
);

-- Insertar algunos productos de ejemplo
INSERT INTO Producto (nombre, proveedor, fecha_vencimiento, precio, stock, eliminado) VALUES
('Leche Entera 1L', 'Proveedor Norte', '2027-06-30', 2.50, 100, 0),
('Pan de Molde', 'Panaderia Central', '2026-11-15', 1.80, 50, 0),
('Arroz 5kg', 'Distribuidora Sur', '2028-12-31', 8.90, 200, 0),
('Aceite Vegetal 1L', 'Alimentos SA', '2027-09-20', 5.50, 75, 0),
('Azucar 2kg', 'Dulces del Valle', '2029-01-01', 3.20, 150, 0);
