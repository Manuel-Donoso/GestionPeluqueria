-- =====================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS: PELUQUERÍA (Corregido)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS peluqueria
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE peluqueria;

-- ---------------------------------------------------------------------
-- 1. TABLA: ROLES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ROLES (
    IDRoles INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(50) NOT NULL
);

INSERT INTO ROLES (IDRoles, rol)
VALUES
    (1, 'Administrador'),
    (2, 'Peluquero'),
    (3, 'Profesor')
ON DUPLICATE KEY UPDATE rol = VALUES(rol);

-- ---------------------------------------------------------------------
-- 2. TABLA: PELUQUEROS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PELUQUEROS (
    IDPeluquero INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(50) NOT NULL UNIQUE,
    Nombre VARCHAR(50) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    Telefono VARCHAR(20),
    Direccion VARCHAR(255),
    Email VARCHAR(100) UNIQUE,
    Notas TEXT,
    PW VARCHAR(255) NOT NULL,
    Restaurar BOOLEAN DEFAULT FALSE, -- Corregida errata tipográfica
    FK_rol INT NOT NULL,
    FechaAlta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaBaja DATETIME NULL,
    baja BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_rol) REFERENCES ROLES(IDRoles)
);

-- ---------------------------------------------------------------------
-- 3. TABLA: CLIENTES
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
    IDServicio INT AUTO_INCREMENT PRIMARY KEY, -- Cambiado a singular por consistencia
    servicio VARCHAR(100) NOT NULL
);

-- ---------------------------------------------------------------------
-- 6. TABLA: SERVICIOSPRECIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SERVICIOSPRECIOS (
    IDServicioprecio INT AUTO_INCREMENT PRIMARY KEY,
    FK_Servicio INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (FK_Servicio)
        REFERENCES SERVICIOS(IDServicio)
        ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 7. TABLA: FACTURA
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FACTURA (
    IDFactura INT AUTO_INCREMENT PRIMARY KEY,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,
    FK_Servicio INT NULL, -- Opcional: enlace al catálogo de servicios original
    Servicio VARCHAR(100) NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    notas TEXT,

    FOREIGN KEY (FK_Cliente) REFERENCES CLIENTES(IDCliente),
    FOREIGN KEY (FK_peluquero) REFERENCES PELUQUEROS(IDPeluquero),
    FOREIGN KEY (FK_Servicio) REFERENCES SERVICIOS(IDServicio) ON DELETE SET NULL
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
    operacion VARCHAR(20) NOT NULL,
    registro_id INT NOT NULL,
    FK_peluquero INT NULL,
    detalles MEDIUMTEXT NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- 10. TABLA: RESTABLECIMIENTOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS RESTABLECIMIENTOS (
    IDRestablecimiento INT AUTO_INCREMENT PRIMARY KEY,
    FK_Peluquero INT NOT NULL,
    Token VARCHAR(255) NOT NULL,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaExpiracion DATETIME NOT NULL,
    Utilizado BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_Peluquero) REFERENCES PELUQUEROS(IDPeluquero)
);

-- ---------------------------------------------------------------------
-- 11. TABLA: TOKENS_ACCESO
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS TOKENS_ACCESO (
    IDToken INT AUTO_INCREMENT PRIMARY KEY,
    FK_Peluquero INT NOT NULL,
    TokenHash CHAR(64) NOT NULL UNIQUE,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaExpiracion DATETIME NOT NULL,
    Revocado BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_Peluquero) REFERENCES PELUQUEROS(IDPeluquero)
);

-- ---------------------------------------------------------------------
-- 12. TABLA: FICHAS_TRATAMIENTO
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FICHAS_TRATAMIENTO (
    IDFicha INT AUTO_INCREMENT PRIMARY KEY,
    Estado ENUM('borrador', 'finalizada') NOT NULL DEFAULT 'borrador',
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaFinalizacion DATETIME NULL,
    FechaTratamiento DATETIME NULL,

    FK_Cliente INT NOT NULL,
    FK_Peluquero INT NOT NULL,
    FK_Profesor INT NULL,

    ColorCompleto BOOLEAN DEFAULT FALSE,
    Raiz BOOLEAN DEFAULT FALSE,
    Mechas BOOLEAN DEFAULT FALSE,
    Matiz BOOLEAN DEFAULT FALSE,
    OtroServicio VARCHAR(255),
    UltimoColorProcesos TEXT,

    Alergias TINYINT UNSIGNED NULL,
    AlergiasDetalles TEXT,
    CueroCabelludoAlteraciones TEXT,
    Longitud VARCHAR(50),
    RaizCrecidaCm DECIMAL(5,2),
    Grosor VARCHAR(20),
    Canas INT UNSIGNED,
    Distribucion VARCHAR(255),

    ZonaRaizTonoReflejos VARCHAR(255),
    ZonaMediosTonoReflejos VARCHAR(255),
    ZonaPuntasTonoReflejos VARCHAR(255),

    ZonaRaizPorosidadResistencia TEXT,
    ZonaMediosPorosidadResistencia TEXT,
    ZonaPuntasPorosidadResistencia TEXT,

    PruebaAlergia TEXT,
    PruebaMechon TEXT,

    ObjetivoAltura VARCHAR(255),
    ObjetivoReflejo VARCHAR(255),
    ObjetivoFondoPrevisto VARCHAR(255),
    TecnicaJustificacion TEXT,
    VistoBuenoDocente TINYINT UNSIGNED NULL,

    MarcaLinea VARCHAR(255),
    PreparacionPrevia TEXT,
    OrdenAplicacion TEXT,

    InicioAplicacion DATETIME NULL,
    RetiradaAplicacion DATETIME NULL,
    TiempoAplicacionMinutos INT UNSIGNED NULL,

    IncidenciasAjustesTratamientoFinal TEXT,
    TonoReflejoFinal VARCHAR(255),
    CanasCubiertas TINYINT UNSIGNED NULL,
    Uniforme BOOLEAN DEFAULT FALSE,

    CuidadosMantenimiento TEXT,
    QueSalioBienMejoraria TEXT,
    ValoracionRevisionFinalDocente TEXT,

    FOREIGN KEY (FK_Cliente) REFERENCES CLIENTES(IDCliente),
    FOREIGN KEY (FK_Peluquero) REFERENCES PELUQUEROS(IDPeluquero),
    FOREIGN KEY (FK_Profesor) REFERENCES PELUQUEROS(IDPeluquero),

    INDEX IDX_FICHA_CLIENTE (FK_Cliente),
    INDEX IDX_FICHA_PELUQUERO (FK_Peluquero),
    INDEX IDX_FICHA_PROFESOR (FK_Profesor),
    INDEX IDX_FICHA_FECHA (FechaTratamiento),
    INDEX IDX_FICHA_ESTADO (Estado)
);

-- ---------------------------------------------------------------------
-- 13. TABLA: FORMULAS_TRATAMIENTO
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FORMULAS_TRATAMIENTO (
    IDFormula INT AUTO_INCREMENT PRIMARY KEY,
    FK_Ficha INT NOT NULL,
    ZonaFase ENUM('Raiz', 'Medios y puntas', 'Mechas', 'Matiz') NOT NULL,
    Tono VARCHAR(100),
    Gramos DECIMAL(8,2),
    Oxidante VARCHAR(100),
    CantidadOxidante DECIMAL(8,2),
    Proporcion VARCHAR(50),
    Tiempo VARCHAR(50),

    FOREIGN KEY (FK_Ficha) REFERENCES FICHAS_TRATAMIENTO(IDFicha) ON DELETE CASCADE,
    INDEX IDX_FORMULA_FICHA (FK_Ficha)
);


-- =====================================================================
-- TRIGGERS DE AUDITORÍA
-- =====================================================================

DELIMITER //

-- Trigger para INSERT en CLIENTES
CREATE TRIGGER trg_clientes_insert
AFTER INSERT ON CLIENTES
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'CLIENTES',
        'INSERT',
        NEW.IDCliente,
        @usuario_actual,
        CONCAT(
            'Nuevo cliente dado de alta: ',
            NEW.Nombre,
            ' ',
            NEW.Apellido1
        )
    );
END//


-- Trigger para UPDATE en CLIENTES
CREATE TRIGGER trg_clientes_update
AFTER UPDATE ON CLIENTES
FOR EACH ROW
BEGIN
    DECLARE cambios TEXT DEFAULT '';

    IF NOT (OLD.Nombre <=> NEW.Nombre) THEN
        SET cambios = CONCAT(
            cambios,
            'Nombre: [', OLD.Nombre, ' -> ', NEW.Nombre, '] '
        );
    END IF;

    IF NOT (OLD.Apellido1 <=> NEW.Apellido1) THEN
        SET cambios = CONCAT(
            cambios,
            'Apellido1: [', OLD.Apellido1, ' -> ', NEW.Apellido1, '] '
        );
    END IF;

    IF NOT (OLD.Telefono <=> NEW.Telefono) THEN
        SET cambios = CONCAT(
            cambios,
            'Teléfono: [', OLD.Telefono, ' -> ', NEW.Telefono, '] '
        );
    END IF;

    IF NOT (OLD.Email <=> NEW.Email) THEN
        SET cambios = CONCAT(
            cambios,
            'Email: [', OLD.Email, ' -> ', NEW.Email, '] '
        );
    END IF;

    -- Detectar cambios en el estado de baja
    IF NOT (OLD.baja <=> NEW.baja) THEN
        IF NEW.baja = 1 THEN
            SET cambios = CONCAT(
                cambios,
                'Estado: [Dado de Baja] '
            );
        ELSE
            SET cambios = CONCAT(
                cambios,
                'Estado: [Reactivado] '
            );
        END IF;
    END IF;

    IF cambios <> '' THEN
        INSERT INTO AUDITORIA (
            tabla_afectada,
            operacion,
            registro_id,
            FK_peluquero,
            detalles
        )
        VALUES (
            'CLIENTES',
            'UPDATE',
            NEW.IDCliente,
            @usuario_actual,
            CONCAT(
                'Modificación en cliente ID ',
                NEW.IDCliente,
                '. Cambios -> ',
                cambios
            )
        );
    END IF;
END//



-- =====================================================================
-- TRIGGERS DE AUDITORÍA: FICHAS DE TRATAMIENTO Y FÓRMULAS
-- =====================================================================



-- ---------------------------------------------------------------------
-- 1. CREACIÓN DE UNA FICHA DE TRATAMIENTO
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_tratamiento_insert
AFTER INSERT ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FICHAS_TRATAMIENTO',
        'INSERT',
        NEW.IDFicha,
        @usuario_actual,
        CONCAT(
            'Creación de ficha de tratamiento. ',
            'Cliente: ', NEW.FK_Cliente,
            '. Peluquero responsable: ', NEW.FK_Peluquero,
            '. Profesor: ', COALESCE(NEW.FK_Profesor, 'Sin asignar'),
            '. Estado: ', NEW.Estado
        )
    );
END//


-- ---------------------------------------------------------------------
-- 2. MODIFICACIÓN Y FINALIZACIÓN DE UNA FICHA
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_tratamiento_update
AFTER UPDATE ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FICHAS_TRATAMIENTO',

        CASE
            WHEN OLD.Estado <> NEW.Estado
                 AND NEW.Estado = 'finalizada'
            THEN 'FINALIZAR'
            ELSE 'UPDATE'
        END,

        NEW.IDFicha,
        @usuario_actual,

        CONCAT(
            'Modificación de ficha de tratamiento. ',
            'ANTES: ',
            JSON_OBJECT(
                'Estado', OLD.Estado,
                'FechaTratamiento', OLD.FechaTratamiento,
                'FK_Cliente', OLD.FK_Cliente,
                'FK_Peluquero', OLD.FK_Peluquero,
                'FK_Profesor', OLD.FK_Profesor,
                'ColorCompleto', OLD.ColorCompleto,
                'Raiz', OLD.Raiz,
                'Mechas', OLD.Mechas,
                'Matiz', OLD.Matiz,
                'OtroServicio', OLD.OtroServicio,
                'UltimoColorProcesos', OLD.UltimoColorProcesos,
                'Alergias', OLD.Alergias,
                'AlergiasDetalles', OLD.AlergiasDetalles,
                'CueroCabelludoAlteraciones', OLD.CueroCabelludoAlteraciones,
                'Longitud', OLD.Longitud,
                'RaizCrecidaCm', OLD.RaizCrecidaCm,
                'Grosor', OLD.Grosor,
                'Canas', OLD.Canas,
                'Distribucion', OLD.Distribucion,
                'ZonaRaizTonoReflejos', OLD.ZonaRaizTonoReflejos,
                'ZonaMediosTonoReflejos', OLD.ZonaMediosTonoReflejos,
                'ZonaPuntasTonoReflejos', OLD.ZonaPuntasTonoReflejos,
                'ZonaRaizPorosidadResistencia', OLD.ZonaRaizPorosidadResistencia,
                'ZonaMediosPorosidadResistencia', OLD.ZonaMediosPorosidadResistencia,
                'ZonaPuntasPorosidadResistencia', OLD.ZonaPuntasPorosidadResistencia,
                'PruebaAlergia', OLD.PruebaAlergia,
                'PruebaMechon', OLD.PruebaMechon,
                'ObjetivoAltura', OLD.ObjetivoAltura,
                'ObjetivoReflejo', OLD.ObjetivoReflejo,
                'ObjetivoFondoPrevisto', OLD.ObjetivoFondoPrevisto,
                'TecnicaJustificacion', OLD.TecnicaJustificacion,
                'VistoBuenoDocente', OLD.VistoBuenoDocente,
                'MarcaLinea', OLD.MarcaLinea,
                'PreparacionPrevia', OLD.PreparacionPrevia,
                'OrdenAplicacion', OLD.OrdenAplicacion,
                'InicioAplicacion', OLD.InicioAplicacion,
                'RetiradaAplicacion', OLD.RetiradaAplicacion,
                'TiempoAplicacionMinutos', OLD.TiempoAplicacionMinutos,
                'IncidenciasAjustesTratamientoFinal', OLD.IncidenciasAjustesTratamientoFinal,
                'TonoReflejoFinal', OLD.TonoReflejoFinal,
                'CanasCubiertas', OLD.CanasCubiertas,
                'Uniforme', OLD.Uniforme,
                'CuidadosMantenimiento', OLD.CuidadosMantenimiento,
                'QueSalioBienMejoraria', OLD.QueSalioBienMejoraria,
                'ValoracionRevisionFinalDocente', OLD.ValoracionRevisionFinalDocente,
                'FechaFinalizacion', OLD.FechaFinalizacion
            ),

            ' | DESPUÉS: ',

            JSON_OBJECT(
                'Estado', NEW.Estado,
                'FechaTratamiento', NEW.FechaTratamiento,
                'FK_Cliente', NEW.FK_Cliente,
                'FK_Peluquero', NEW.FK_Peluquero,
                'FK_Profesor', NEW.FK_Profesor,
                'ColorCompleto', NEW.ColorCompleto,
                'Raiz', NEW.Raiz,
                'Mechas', NEW.Mechas,
                'Matiz', NEW.Matiz,
                'OtroServicio', NEW.OtroServicio,
                'UltimoColorProcesos', NEW.UltimoColorProcesos,
                'Alergias', NEW.Alergias,
                'AlergiasDetalles', NEW.AlergiasDetalles,
                'CueroCabelludoAlteraciones', NEW.CueroCabelludoAlteraciones,
                'Longitud', NEW.Longitud,
                'RaizCrecidaCm', NEW.RaizCrecidaCm,
                'Grosor', NEW.Grosor,
                'Canas', NEW.Canas,
                'Distribucion', NEW.Distribucion,
                'ZonaRaizTonoReflejos', NEW.ZonaRaizTonoReflejos,
                'ZonaMediosTonoReflejos', NEW.ZonaMediosTonoReflejos,
                'ZonaPuntasTonoReflejos', NEW.ZonaPuntasTonoReflejos,
                'ZonaRaizPorosidadResistencia', NEW.ZonaRaizPorosidadResistencia,
                'ZonaMediosPorosidadResistencia', NEW.ZonaMediosPorosidadResistencia,
                'ZonaPuntasPorosidadResistencia', NEW.ZonaPuntasPorosidadResistencia,
                'PruebaAlergia', NEW.PruebaAlergia,
                'PruebaMechon', NEW.PruebaMechon,
                'ObjetivoAltura', NEW.ObjetivoAltura,
                'ObjetivoReflejo', NEW.ObjetivoReflejo,
                'ObjetivoFondoPrevisto', NEW.ObjetivoFondoPrevisto,
                'TecnicaJustificacion', NEW.TecnicaJustificacion,
                'VistoBuenoDocente', NEW.VistoBuenoDocente,
                'MarcaLinea', NEW.MarcaLinea,
                'PreparacionPrevia', NEW.PreparacionPrevia,
                'OrdenAplicacion', NEW.OrdenAplicacion,
                'InicioAplicacion', NEW.InicioAplicacion,
                'RetiradaAplicacion', NEW.RetiradaAplicacion,
                'TiempoAplicacionMinutos', NEW.TiempoAplicacionMinutos,
                'IncidenciasAjustesTratamientoFinal', NEW.IncidenciasAjustesTratamientoFinal,
                'TonoReflejoFinal', NEW.TonoReflejoFinal,
                'CanasCubiertas', NEW.CanasCubiertas,
                'Uniforme', NEW.Uniforme,
                'CuidadosMantenimiento', NEW.CuidadosMantenimiento,
                'QueSalioBienMejoraria', NEW.QueSalioBienMejoraria,
                'ValoracionRevisionFinalDocente', NEW.ValoracionRevisionFinalDocente,
                'FechaFinalizacion', NEW.FechaFinalizacion
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 3. CREACIÓN DE UNA FÓRMULA
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_insert
AFTER INSERT ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'INSERT',
        NEW.IDFormula,
        @usuario_actual,
        CONCAT(
            'Nueva fórmula. Ficha: ', NEW.FK_Ficha,
            '. Zona: ', NEW.ZonaFase,
            '. Datos: ',
            JSON_OBJECT(
                'Tono', NEW.Tono,
                'Gramos', NEW.Gramos,
                'Oxidante', NEW.Oxidante,
                'CantidadOxidante', NEW.CantidadOxidante,
                'Proporcion', NEW.Proporcion,
                'Tiempo', NEW.Tiempo
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 4. MODIFICACIÓN DE UNA FÓRMULA
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_update
AFTER UPDATE ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'UPDATE',
        NEW.IDFormula,
        @usuario_actual,
        CONCAT(
            'Modificación de fórmula de la ficha ',
            NEW.FK_Ficha,
            '. ANTES: ',
            JSON_OBJECT(
                'FK_Ficha', OLD.FK_Ficha,
                'ZonaFase', OLD.ZonaFase,
                'Tono', OLD.Tono,
                'Gramos', OLD.Gramos,
                'Oxidante', OLD.Oxidante,
                'CantidadOxidante', OLD.CantidadOxidante,
                'Proporcion', OLD.Proporcion,
                'Tiempo', OLD.Tiempo
            ),
            ' | DESPUÉS: ',
            JSON_OBJECT(
                'FK_Ficha', NEW.FK_Ficha,
                'ZonaFase', NEW.ZonaFase,
                'Tono', NEW.Tono,
                'Gramos', NEW.Gramos,
                'Oxidante', NEW.Oxidante,
                'CantidadOxidante', NEW.CantidadOxidante,
                'Proporcion', NEW.Proporcion,
                'Tiempo', NEW.Tiempo
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 5. ELIMINACIÓN DE UNA FÓRMULA
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_delete
AFTER DELETE ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'DELETE',
        OLD.IDFormula,
        @usuario_actual,
        CONCAT(
            'Eliminación de fórmula de la ficha ',
            OLD.FK_Ficha,
            '. Datos eliminados: ',
            JSON_OBJECT(
                'ZonaFase', OLD.ZonaFase,
                'Tono', OLD.Tono,
                'Gramos', OLD.Gramos,
                'Oxidante', OLD.Oxidante,
                'CantidadOxidante', OLD.CantidadOxidante,
                'Proporcion', OLD.Proporcion,
                'Tiempo', OLD.Tiempo
            )
        )
    );
END//




DELIMITER ;
