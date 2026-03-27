USE tienda_db;

-- 1. Tabla USUARIOS
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') DEFAULT 'cliente',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla PRODUCTOS
DROP TABLE IF EXISTS productos;
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    imagen VARCHAR(255) DEFAULT 'default.jpg',
    destacado BOOLEAN DEFAULT 0
);

-- 3. Datos de Prueba
INSERT INTO usuarios (nombre, email, password, rol) 
VALUES ('Hugo Admin', 'admin@tienda.com', '1234', 'admin');

INSERT INTO productos (nombre, descripcion, precio, stock) 
VALUES ('Zapatillas Urban Flow', 'Diseño ergonómico ideal para el día a día.', 45.50, 10);