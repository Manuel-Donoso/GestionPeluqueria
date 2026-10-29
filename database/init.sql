-- =====================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS: PELUQUERÍA
-- =====================================================================

CREATE DATABASE IF NOT EXISTS peluqueria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE peluqueria;

-- Desactivar temporalmente la comprobación de claves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. TABLA: ROLES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ROLES (
    IDRoles INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(50) NOT NULL
);

INSERT INTO ROLES (IDRoles, rol) VALUES (1, 'Administrador'), (2, 'Peluquero')
ON DUPLICATE KEY UPDATE rol=rol;

-- ---------------------------------------------------------------------
-- 2. TABLA: PELUQUEROS (Actualizada con control de bajas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PELUQUEROS (
    IDPeluquero INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    Telefono VARCHAR(20),
    Direccion VARCHAR(255),
    Email VARCHAR(100),
    Notas TEXT,
    PW VARCHAR(255) NOT NULL,
    Restarurar BOOLEAN DEFAULT FALSE,
    FK_rol INT NOT NULL,
    FechaAlta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaBaja DATETIME NULL,
    baja BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (FK_rol) REFERENCES ROLES(IDRoles)
);

-- ---------------------------------------------------------------------
-- 3. TABLA: CLIENTES (Actualizada con control de bajas lógicas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CLIENTES (
    IDCliente INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    Telefono VARCHAR(20) UNIQUE NOT NULL,
    Direccion VARCHAR(255),
    Email VARCHAR(100),
    Notas TEXT,
    FechaAlta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaBaja DATETIME NULL,
    baja BOOLEAN DEFAULT FALSE
);

-- ---------------------------------------------------------------------
-- 4. TABLA: HISTORIALCLIENTES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS HISTORIALCLIENTES (
    IDHistorial INT AUTO_INCREMENT PRIMARY KEY,
    observaciones TEXT NOT NULL,
    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,
    FOREIGN KEY (FK_Cliente) REFERENCES CLIENTES(IDCliente),
    FOREIGN KEY (FK_peluquero) REFERENCES PELUQUEROS(IDPeluquero)
);

-- ---------------------------------------------------------------------
-- 5. TABLA: SERVICIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SERVICIOS (
    IDServicios INT AUTO_INCREMENT PRIMARY KEY,
    servicio VARCHAR(100) NOT NULL
);

-- ---------------------------------------------------------------------
-- 6. TABLA: SERVICIOSPRECIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SERVICIOSPRECIOS (
    IDServicioprecio INT AUTO_INCREMENT PRIMARY KEY,
    FK_Servicio INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (FK_Servicio) REFERENCES SERVICIOS(IDServicios) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 7. TABLA: FACTURA (Precio congelado en el momento de la venta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FACTURA (
    IDFactura INT AUTO_INCREMENT PRIMARY KEY,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,
    Servicio VARCHAR(100) NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    notas TEXT,
    FOREIGN KEY (FK_Cliente) REFERENCES CLIENTES(IDCliente),
    FOREIGN KEY (FK_peluquero) REFERENCES PELUQUEROS(IDPeluquero)
);

-- ---------------------------------------------------------------------
-- 8. TABLA: AGENDA
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AGENDA (
    IDAgenda INT AUTO_INCREMENT PRIMARY KEY,
    Fecha DATE NOT NULL,
    Hora TIME NOT NULL,
    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,
    FOREIGN KEY (FK_Cliente) REFERENCES CLIENTES(IDCliente),
    FOREIGN KEY (FK_peluquero) REFERENCES PELUQUEROS(IDPeluquero)
);

-- ---------------------------------------------------------------------
-- 9. TABLA: AUDITORIA
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AUDITORIA (
    IDAuditoria INT AUTO_INCREMENT PRIMARY KEY,
    tabla_afectada VARCHAR(50) NOT NULL,
    operacion VARCHAR(20) NOT NULL, -- INSERT, UPDATE, DELETE
    registro_id INT NOT NULL,
    FK_peluquero INT NULL,         -- Peluquero responsable del cambio
    detalles TEXT NOT NULL,        -- Descripción exacta de qué cambió
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP
);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- TRIGGERS DE AUDITORIA (Adaptados a CLIENTES)
-- =====================================================================

DELIMITER //

-- Trigger para INSERT en CLIENTES
CREATE TRIGGER trg_clientes_insert 
AFTER INSERT ON CLIENTES
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (tabla_afectada, operacion, registro_id, FK_peluquero, detalles)
    VALUES (
        'CLIENTES', 
        'INSERT', 
        NEW.IDCliente, 
        @usuario_actual,
        CONCAT('Nuevo cliente dado de alta: ', NEW.Nombre, ' ', NEW.Apellido1)
    );
END//

-- Trigger para UPDATE en CLIENTES (Registra cambios de datos y control de bajas)
CREATE TRIGGER trg_clientes_update 
AFTER UPDATE ON CLIENTES
FOR EACH ROW
BEGIN
    DECLARE cambios TEXT DEFAULT '';
    
    IF OLD.Nombre != NEW.Nombre THEN
        SET cambios = CONCAT(cambios, 'Nombre: [', OLD.Nombre, ' -> ', NEW.Nombre, '] ');
    END IF;
    IF OLD.Apellido1 != NEW.Apellido1 THEN
        SET cambios = CONCAT(cambios, 'Apellido1: [', OLD.Apellido1, ' -> ', NEW.Apellido1, '] ');
    END IF;
    IF OLD.Telefono != NEW.Telefono THEN
        SET cambios = CONCAT(cambios, 'Teléfono: [', OLD.Telefono, ' -> ', NEW.Telefono, '] ');
    END IF;
    IF OLD.Email != NEW.Email THEN
        SET cambios = CONCAT(cambios, 'Email: [', OLD.Email, ' -> ', NEW.Email, '] ');
    END IF;
    -- Detectar si ha cambiado el estado de baja
    IF OLD.baja != NEW.baja THEN
        IF NEW.baja = 1 THEN
            SET cambios = CONCAT(cambios, 'Estado: [Dado de Baja] ');
        ELSE
            SET cambios = CONCAT(cambios, 'Estado: [Reactivado] ');
        END IF;
    END IF;

    IF cambios != '' THEN
        INSERT INTO AUDITORIA (tabla_afectada, operacion, registro_id, FK_peluquero, detalles)
        VALUES (
            'CLIENTES', 
            'UPDATE', 
            NEW.IDCliente, 
            @usuario_actual,
            CONCAT('Modificación en cliente ID ', NEW.IDCliente, '. Cambios -> ', cambios)
        );
    END IF;
END//

-- Nota: Ya no usamos DELETE físico en clientes, por lo que no es necesario el trigger de DELETE,
-- ya que las bajas ahora se gestionan actualizando el campo `baja = 1` y `FechaBaja = NOW()`.

DELIMITER ;