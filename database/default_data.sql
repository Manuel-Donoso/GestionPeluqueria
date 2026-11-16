
-- =====================================================================
-- SCRIPT DE DATOS DE EJEMPLO: PELUQUERÍA
-- Compatible con init.sql
-- Motor: MariaDB 11.4
--
-- CONTRASEÑA PROVISIONAL DE TODOS LOS USUARIOS:
-- Peluqueria123!
--
-- IMPORTANTE:
-- Este script elimina los datos existentes y vuelve a insertar
-- los datos de demostración. No ejecutarlo sobre una base de datos
-- que contenga información real que se quiera conservar.
-- =====================================================================

USE peluqueria;

-- =====================================================================
-- 1. LIMPIEZA DE TABLAS
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM TOKENS_ACCESO;
DELETE FROM RESTABLECIMIENTOS;

DELETE FROM VERSIONES_FICHAS_TRATAMIENTO;
DELETE FROM HISTORIAL_FICHAS_TRATAMIENTO;
DELETE FROM FORMULAS_TRATAMIENTO;
DELETE FROM FICHAS_TRATAMIENTO;

DELETE FROM HISTORIALCLIENTES_AUDITORIA;
DELETE FROM HISTORIALCLIENTES;

DELETE FROM AUDITORIA;
DELETE FROM AGENDA;
DELETE FROM FACTURA;
DELETE FROM SERVICIOSPRECIOS;
DELETE FROM SERVICIOS;
DELETE FROM CLIENTES;
DELETE FROM PELUQUEROS;
DELETE FROM ROLES;

-- Reiniciar los contadores de identificadores.
ALTER TABLE ROLES AUTO_INCREMENT = 1;
ALTER TABLE PELUQUEROS AUTO_INCREMENT = 1;
ALTER TABLE CLIENTES AUTO_INCREMENT = 1;
ALTER TABLE HISTORIALCLIENTES AUTO_INCREMENT = 1;
ALTER TABLE HISTORIALCLIENTES_AUDITORIA AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOS AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOSPRECIOS AUTO_INCREMENT = 1;
ALTER TABLE FACTURA AUTO_INCREMENT = 1;
ALTER TABLE AGENDA AUTO_INCREMENT = 1;
ALTER TABLE AUDITORIA AUTO_INCREMENT = 1;
ALTER TABLE RESTABLECIMIENTOS AUTO_INCREMENT = 1;
ALTER TABLE TOKENS_ACCESO AUTO_INCREMENT = 1;
ALTER TABLE FICHAS_TRATAMIENTO AUTO_INCREMENT = 1;
ALTER TABLE FORMULAS_TRATAMIENTO AUTO_INCREMENT = 1;
ALTER TABLE VERSIONES_FICHAS_TRATAMIENTO AUTO_INCREMENT = 1;
ALTER TABLE HISTORIAL_FICHAS_TRATAMIENTO AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Usuario que aparecerá como responsable en los triggers de auditoría.
SET @usuario_actual = 1;


-- =====================================================================
-- 2. ROLES
-- =====================================================================

INSERT INTO ROLES (IDRoles, rol)
VALUES
    (1, 'Administrador'),
    (2, 'Peluquero'),
    (3, 'Profesor');


-- =====================================================================
-- 3. PELUQUEROS Y USUARIOS
-- =====================================================================
-- Contraseña provisional común: Peluqueria123!
-- El hash corresponde a PASSWORD_DEFAULT de PHP.
-- =====================================================================

INSERT INTO PELUQUEROS (
    IDPeluquero,
    Usuario,
    Nombre,
    Apellido1,
    Apellido2,
    Telefono,
    Direccion,
    Email,
    Notas,
    PW,
    Restaurar,
    FK_rol,
    FechaAlta,
    baja
)
VALUES
(
    1,
    'CarAdP',
    'Carlos',
    'Administrador',
    'Pérez',
    '600111111',
    'Calle Mayor 10',
    'admin@peluqueria.com',
    'Usuario administrador del sistema.',
    '$2y$12$.bC..uVBaR.FuajruOj3vehy1mgn9q.MFypF8SiwdAHE5/MQ8uBR2',
    FALSE,
    1,
    '2026-01-01 09:00:00',
    FALSE
),
(
    2,
    'LauGarc',
    'Laura',
    'García',
    'Sanz',
    '600222222',
    'Calle Real 15',
    'laura@peluqueria.com',
    'Peluquera especializada en coloración.',
    '$2y$12$ypS2V07o7tz9AaMXefSs4.dex./euB4MSi2vd87PdFssntEtI8W1e',
    FALSE,
    2,
    '2026-01-02 09:00:00',
    FALSE
),
(
    3,
    'MigFern',
    'Miguel',
    'Fernández',
    'López',
    '600333333',
    'Avenida de España 20',
    'miguel@peluqueria.com',
    'Peluquero especializado en corte y peinado.',
    '$2y$12$ypS2V07o7tz9AaMXefSs4.dex./euB4MSi2vd87PdFssntEtI8W1e',
    FALSE,
    2,
    '2026-01-03 09:00:00',
    FALSE
),
(
    4,
    'AnaProf',
    'Ana',
    'Martín',
    'Sánchez',
    '600444444',
    'Calle Escuela 5',
    'profesora@peluqueria.com',
    'Profesora responsable de supervisar y validar los tratamientos.',
    '$2y$12$ypS2V07o7tz9AaMXefSs4.dex./euB4MSi2vd87PdFssntEtI8W1e',
    FALSE,
    3,
    '2026-01-04 09:00:00',
    FALSE
);


-- =====================================================================
-- 4. CLIENTES
-- =====================================================================

INSERT INTO CLIENTES (
    IDCliente,
    Nombre,
    Apellido1,
    Apellido2,
    Telefono,
    Direccion,
    Email,
    Notas,
    FechaAlta,
    baja
)
VALUES
(
    1,
    'Ana',
    'Martínez',
    'Ruiz',
    '610111111',
    'Calle Cervantes 12',
    'ana.martinez@email.com',
    'Cliente habitual. Prefiere productos sin amoniaco.',
    '2026-02-01 10:00:00',
    FALSE
),
(
    2,
    'David',
    'Rodríguez',
    'Gómez',
    '610222222',
    'Avenida Constitución 25',
    'david.rodriguez@email.com',
    'Cliente habitual.',
    '2026-02-05 11:00:00',
    FALSE
),
(
    3,
    'Sofía',
    'Alonso',
    'Blanco',
    '610333333',
    'Calle Luna 8',
    'sofia.alonso@email.com',
    'Interesada en tratamientos de coloración.',
    '2026-03-10 12:00:00',
    FALSE
),
(
    4,
    'Javier',
    'Jiménez',
    'Pascual',
    '610444444',
    'Calle Sol 18',
    'javier.jimenez@email.com',
    'Cliente ocasional.',
    '2026-03-15 13:00:00',
    FALSE
);


-- =====================================================================
-- 5. SERVICIOS
-- =====================================================================

INSERT INTO SERVICIOS (
    IDServicio,
    servicio
)
VALUES
    (1, 'Corte de Cabello'),
    (2, 'Tinte / Coloración'),
    (3, 'Mechas Balayage'),
    (4, 'Lavado y Peinado'),
    (5, 'Arreglo de Barba');


-- =====================================================================
-- 6. PRECIOS DE SERVICIOS
-- =====================================================================

INSERT INTO SERVICIOSPRECIOS (
    IDServicioprecio,
    FK_Servicio,
    precio
)
VALUES
    (1, 1, 15.00),
    (2, 2, 35.00),
    (3, 3, 65.00),
    (4, 4, 20.00),
    (5, 5, 10.00);


-- =====================================================================
-- 7. HISTORIAL DE CLIENTES
-- =====================================================================

INSERT INTO HISTORIALCLIENTES (
    IDHistorial,
    observaciones,
    FechaCreacion,
    FK_Cliente,
    FK_peluquero
)
VALUES
(
    1,
    'Se realizó corte degradado y arreglo de barba. El cliente quedó satisfecho con el resultado.',
    '2026-11-02 11:30:00',
    2,
    3
),
(
    2,
    'Se aplicó tinte castaño claro. Se recomienda utilizar champú específico para cabello teñido.',
    '2026-11-02 12:30:00',
    1,
    2
);


-- =====================================================================
-- 8. FACTURAS
-- =====================================================================

INSERT INTO FACTURA (
    IDFactura,
    Fecha,
    FK_Cliente,
    FK_peluquero,
    FK_Servicio,
    Servicio,
    Precio,
    notas
)
VALUES
(
    1,
    '2026-11-02 12:00:00',
    1,
    2,
    2,
    'Tinte / Coloración',
    35.00,
    'Servicio de coloración completo.'
),
(
    2,
    '2026-11-02 11:30:00',
    2,
    3,
    1,
    'Corte de Cabello',
    15.00,
    'Corte degradado.'
),
(
    3,
    '2026-11-03 17:00:00',
    3,
    2,
    4,
    'Lavado y Peinado',
    20.00,
    'Lavado y peinado.'
);


-- =====================================================================
-- 9. AGENDA
-- =====================================================================

INSERT INTO AGENDA (
    IDAgenda,
    Fecha,
    Hora,
    FK_Cliente,
    FK_peluquero
)
VALUES
    (1, '2026-11-02', '10:00:00', 1, 2),
    (2, '2026-11-02', '11:00:00', 2, 3),
    (3, '2026-11-03', '16:30:00', 3, 2);


-- =====================================================================
-- 10. FICHAS DE TRATAMIENTO
-- =====================================================================
--
-- Ficha 1: Finalizada.
-- Ficha 2: Finalizada.
-- Ficha 3: Borrador.
--
-- Las fichas finalizadas tienen profesor asignado y FueFinalizada=TRUE.
-- La ficha en borrador todavía no ha sido finalizada.
-- =====================================================================

INSERT INTO FICHAS_TRATAMIENTO (
    IDFicha,
    Estado,
    FueFinalizada,
    FechaCreacion,
    FechaEnvio,
    FechaFinalizacion,
    FechaTratamiento,
    FechaModificacion,

    FK_Cliente,
    FK_Peluquero,
    FK_Profesor,
    FK_modificadoPor,

    ColorCompleto,
    Raiz,
    Mechas,
    Matiz,
    OtroServicio,
    UltimoColorProcesos,

    Alergias,
    AlergiasDetalles,
    CueroCabelludoAlteraciones,

    Longitud,
    RaizCrecidaCm,
    Grosor,
    Canas,
    Distribucion,

    ZonaRaizTonoReflejos,
    ZonaMediosTonoReflejos,
    ZonaPuntasTonoReflejos,

    ZonaRaizPorosidadResistencia,
    ZonaMediosPorosidadResistencia,
    ZonaPuntasPorosidadResistencia,

    PruebaAlergia,
    PruebaMechon,

    ObjetivoAltura,
    ObjetivoReflejo,
    ObjetivoFondoPrevisto,
    TecnicaJustificacion,
    VistoBuenoDocente,

    MarcaLinea,
    PreparacionPrevia,
    OrdenAplicacion,

    InicioAplicacion,
    RetiradaAplicacion,
    TiempoAplicacionMinutos,
    IncidenciasAjustesTratamientoFinal,

    TonoReflejoFinal,
    CanasCubiertas,
    Uniforme,

    CuidadosMantenimiento,
    QueSalioBienMejoraria,
    ValoracionRevisionFinalDocente
)
VALUES

-- ---------------------------------------------------------------------
-- FICHA 1: COLORACIÓN COMPLETA
-- ---------------------------------------------------------------------

(
    1,
    'finalizada',
    TRUE,
    '2026-11-02 09:00:00',
    '2026-11-02 09:15:00',
    '2026-11-02 12:00:00',
    '2026-11-02 10:00:00',
    '2026-11-02 12:00:00',

    1,
    2,
    4,
    4,

    TRUE,
    TRUE,
    FALSE,
    TRUE,
    NULL,
    'Última coloración hace aproximadamente dos meses. Tono castaño claro.',

    0,
    NULL,
    'Cuero cabelludo sin alteraciones visibles.',

    'Media',
    2.00,
    'Medio',
    15,
    'Canas concentradas en sienes y zona frontal.',

    'Altura 5, castaño claro.',
    'Altura 6, castaño claro.',
    'Altura 6, ligeramente más clara.',

    'Porosidad media, resistencia buena.',
    'Porosidad media, resistencia buena.',
    'Porosidad media-alta, resistencia moderada.',

    'Prueba de alergia realizada 48 horas antes. Sin reacción.',
    'Prueba de mechón satisfactoria.',

    '5',
    'Natural',
    'Fondo castaño natural.',
    'Unificar el color y conseguir una cobertura homogénea de las canas. Aplicar primero en raíz y posteriormente refrescar medios y puntas.',
    1,

    'Línea profesional de coloración permanente.',
    'Preparación del cabello y separación en cuatro secciones.',
    'Aplicación en raíz, control del tiempo y emulsión posterior en medios y puntas.',

    '2026-11-02 10:00:00',
    '2026-11-02 10:45:00',
    45,
    'Sin incidencias durante la aplicación.',

    'Castaño natural uniforme.',
    1,
    TRUE,

    'Utilizar champú y acondicionador para cabello teñido. Evitar lavados frecuentes con agua muy caliente.',
    'Buena cobertura de canas y resultado uniforme. Mejorar la distribución del producto en la zona posterior.',
    'Tratamiento correctamente realizado. Se valida la técnica y el resultado obtenido.'
),

-- ---------------------------------------------------------------------
-- FICHA 2: MECHAS Y MATIZ
-- ---------------------------------------------------------------------

(
    2,
    'finalizada',
    TRUE,
    '2026-11-04 09:00:00',
    '2026-11-04 09:20:00',
    '2026-11-04 13:00:00',
    '2026-11-04 09:30:00',
    '2026-11-04 13:00:00',

    2,
    3,
    4,
    4,

    FALSE,
    FALSE,
    TRUE,
    TRUE,
    'Mechas balayage.',
    'Cabello con coloración anterior en medios y puntas.',

    0,
    NULL,
    'Cuero cabelludo sin alteraciones.',

    'Larga',
    1.50,
    'Fino',
    5,
    'Canas aisladas.',

    'Altura 5, castaño claro.',
    'Altura 6, castaño claro.',
    'Altura 7, rubio oscuro.',

    'Porosidad media.',
    'Porosidad media-alta.',
    'Porosidad alta, resistencia moderada.',

    'Prueba de alergia realizada sin reacción.',
    'Prueba de mechón satisfactoria. Aclaración controlada.',

    '8',
    'Ceniza',
    'Fondo de aclaración amarillo pálido.',
    'Realizar mechas balayage para aportar luminosidad. Controlar el tiempo de exposición y matizar para neutralizar reflejos amarillos.',
    1,

    'Polvo decolorante y línea profesional de matización.',
    'Protección de las zonas de trabajo y separación del cabello.',
    'Aplicación de decolorante en secciones seleccionadas y posterior matización.',

    '2026-11-04 09:30:00',
    '2026-11-04 11:00:00',
    90,
    'Aclaración correcta. Se controló visualmente el proceso.',

    'Rubio claro ceniza.',
    3,
    TRUE,

    'Utilizar champú violeta de forma moderada y mascarilla hidratante. Evitar herramientas térmicas sin protector.',
    'Buena distribución de las mechas. Vigilar especialmente las puntas por su porosidad.',
    'Técnica correctamente ejecutada. Resultado homogéneo y adecuado al objetivo planteado.'
),

-- ---------------------------------------------------------------------
-- FICHA 3: RETOQUE DE RAÍZ
-- ---------------------------------------------------------------------

(
    3,
    'borrador',
    FALSE,
    '2026-11-10 09:00:00',
    NULL,
    NULL,
    '2026-11-10 10:00:00',
    '2026-11-10 09:30:00',

    3,
    2,
    4,
    2,

    FALSE,
    TRUE,
    FALSE,
    FALSE,
    'Retoque de raíz.',
    'Coloración anterior en tono rubio oscuro.',

    NULL,
    NULL,
    'Pendiente de revisión antes de comenzar el tratamiento.',

    'Media',
    3.00,
    'Medio',
    20,
    'Canas distribuidas principalmente en la zona frontal.',

    'Altura 6, rubio oscuro natural.',
    'Altura 6, rubio oscuro natural.',
    'Altura 7, rubio medio.',

    'Pendiente de valoración.',
    'Pendiente de valoración.',
    'Pendiente de valoración.',

    'Pendiente de realizar.',
    'Pendiente de realizar.',

    '6',
    'Natural',
    'Fondo natural.',
    'Retoque de raíz para igualar el crecimiento con el color existente.',
    NULL,

    'Línea profesional de coloración permanente.',
    'Pendiente de preparar.',
    'Aplicación prevista exclusivamente en raíz.',

    NULL,
    NULL,
    NULL,
    NULL,

    NULL,
    NULL,
    FALSE,

    'Pendiente de establecer.',
    NULL,
    NULL
);


-- =====================================================================
-- 11. FÓRMULAS DE TRATAMIENTO
-- =====================================================================

INSERT INTO FORMULAS_TRATAMIENTO (
    IDFormula,
    FK_Ficha,
    ZonaFase,
    Tono,
    Gramos,
    Oxidante,
    CantidadOxidante,
    Proporcion,
    Tiempo
)
VALUES

-- Ficha 1: coloración completa.

(
    1,
    1,
    'Raiz',
    '5.0 Castaño natural',
    60.00,
    '20 volúmenes',
    60.00,
    '1:1',
    '35 minutos'
),
(
    2,
    1,
    'Medios y puntas',
    '5.0 Castaño natural',
    40.00,
    '10 volúmenes',
    40.00,
    '1:1',
    '15 minutos'
),

-- Ficha 2: mechas y matización.

(
    3,
    2,
    'Mechas',
    'Polvo decolorante azul',
    30.00,
    '30 volúmenes',
    60.00,
    '1:2',
    '45 minutos'
),
(
    4,
    2,
    'Matiz',
    '9.1 Rubio muy claro ceniza',
    30.00,
    '10 volúmenes',
    60.00,
    '1:2',
    '15 minutos'
),

-- Ficha 3: retoque de raíz.

(
    5,
    3,
    'Raiz',
    '6.0 Rubio oscuro natural',
    50.00,
    '20 volúmenes',
    50.00,
    '1:1',
    '35 minutos'
);


-- =====================================================================
-- 12. VERSIONES DE FICHAS FINALIZADAS
-- =====================================================================
--
-- Se crea una versión inicial para cada ficha finalizada.
-- Las versiones son inmutables según los triggers del init.sql.
--
-- Los datos se almacenan como fotografías JSON de la ficha y sus
-- fórmulas. No se crea versión para la ficha que sigue en borrador.
-- =====================================================================

INSERT INTO VERSIONES_FICHAS_TRATAMIENTO (
    IDVersion,
    FK_Ficha,
    NumeroVersion,
    DatosFicha,
    DatosFormulas,
    FechaVersion,
    FK_finalizadoPor
)
VALUES

-- Versión 1 de la ficha 1.

(
    1,
    1,
    1,

    JSON_OBJECT(
        'IDFicha', 1,
        'Estado', 'finalizada',
        'FueFinalizada', TRUE,
        'FechaCreacion', '2026-11-02 09:00:00',
        'FechaEnvio', '2026-11-02 09:15:00',
        'FechaFinalizacion', '2026-11-02 12:00:00',
        'FechaTratamiento', '2026-11-02 10:00:00',
        'FK_Cliente', 1,
        'FK_Peluquero', 2,
        'FK_Profesor', 4,
        'ColorCompleto', TRUE,
        'Raiz', TRUE,
        'Mechas', FALSE,
        'Matiz', TRUE,
        'OtroServicio', NULL,
        'UltimoColorProcesos', 'Última coloración hace aproximadamente dos meses. Tono castaño claro.',
        'Alergias', 0,
        'AlergiasDetalles', NULL,
        'CueroCabelludoAlteraciones', 'Cuero cabelludo sin alteraciones visibles.',
        'Longitud', 'Media',
        'RaizCrecidaCm', 2.00,
        'Grosor', 'Medio',
        'Canas', 15,
        'Distribucion', 'Canas concentradas en sienes y zona frontal.',
        'ZonaRaizTonoReflejos', 'Altura 5, castaño claro.',
        'ZonaMediosTonoReflejos', 'Altura 6, castaño claro.',
        'ZonaPuntasTonoReflejos', 'Altura 6, ligeramente más clara.',
        'ZonaRaizPorosidadResistencia', 'Porosidad media, resistencia buena.',
        'ZonaMediosPorosidadResistencia', 'Porosidad media, resistencia buena.',
        'ZonaPuntasPorosidadResistencia', 'Porosidad media-alta, resistencia moderada.',
        'PruebaAlergia', 'Prueba de alergia realizada 48 horas antes. Sin reacción.',
        'PruebaMechon', 'Prueba de mechón satisfactoria.',
        'ObjetivoAltura', '5',
        'ObjetivoReflejo', 'Natural',
        'ObjetivoFondoPrevisto', 'Fondo castaño natural.',
        'TecnicaJustificacion', 'Unificar el color y conseguir una cobertura homogénea de las canas.',
        'VistoBuenoDocente', 1,
        'MarcaLinea', 'Línea profesional de coloración permanente.',
        'PreparacionPrevia', 'Preparación del cabello y separación en cuatro secciones.',
        'OrdenAplicacion', 'Aplicación en raíz, control del tiempo y emulsión posterior en medios y puntas.',
        'InicioAplicacion', '2026-11-02 10:00:00',
        'RetiradaAplicacion', '2026-11-02 10:45:00',
        'TiempoAplicacionMinutos', 45,
        'IncidenciasAjustesTratamientoFinal', 'Sin incidencias durante la aplicación.',
        'TonoReflejoFinal', 'Castaño natural uniforme.',
        'CanasCubiertas', 1,
        'Uniforme', TRUE,
        'CuidadosMantenimiento', 'Utilizar champú y acondicionador para cabello teñido.',
        'QueSalioBienMejoraria', 'Buena cobertura de canas y resultado uniforme.',
        'ValoracionRevisionFinalDocente', 'Tratamiento correctamente realizado. Se valida la técnica y el resultado obtenido.'
    ),

    JSON_ARRAY(
        JSON_OBJECT(
            'IDFormula', 1,
            'ZonaFase', 'Raiz',
            'Tono', '5.0 Castaño natural',
            'Gramos', 60.00,
            'Oxidante', '20 volúmenes',
            'CantidadOxidante', 60.00,
            'Proporcion', '1:1',
            'Tiempo', '35 minutos'
        ),
        JSON_OBJECT(
            'IDFormula', 2,
            'ZonaFase', 'Medios y puntas',
            'Tono', '5.0 Castaño natural',
            'Gramos', 40.00,
            'Oxidante', '10 volúmenes',
            'CantidadOxidante', 40.00,
            'Proporcion', '1:1',
            'Tiempo', '15 minutos'
        )
    ),

    '2026-11-02 12:00:00',
    4
),

-- Versión 1 de la ficha 2.

(
    2,
    2,
    1,

    JSON_OBJECT(
        'IDFicha', 2,
        'Estado', 'finalizada',
        'FueFinalizada', TRUE,
        'FechaCreacion', '2026-11-04 09:00:00',
        'FechaEnvio', '2026-11-04 09:20:00',
        'FechaFinalizacion', '2026-11-04 13:00:00',
        'FechaTratamiento', '2026-11-04 09:30:00',
        'FK_Cliente', 2,
        'FK_Peluquero', 3,
        'FK_Profesor', 4,
        'ColorCompleto', FALSE,
        'Raiz', FALSE,
        'Mechas', TRUE,
        'Matiz', TRUE,
        'OtroServicio', 'Mechas balayage.',
        'UltimoColorProcesos', 'Cabello con coloración anterior en medios y puntas.',
        'Alergias', 0,
        'AlergiasDetalles', NULL,
        'CueroCabelludoAlteraciones', 'Cuero cabelludo sin alteraciones.',
        'Longitud', 'Larga',
        'RaizCrecidaCm', 1.50,
        'Grosor', 'Fino',
        'Canas', 5,
        'Distribucion', 'Canas aisladas.',
        'ZonaRaizTonoReflejos', 'Altura 5, castaño claro.',
        'ZonaMediosTonoReflejos', 'Altura 6, castaño claro.',
        'ZonaPuntasTonoReflejos', 'Altura 7, rubio oscuro.',
        'ZonaRaizPorosidadResistencia', 'Porosidad media.',
        'ZonaMediosPorosidadResistencia', 'Porosidad media-alta.',
        'ZonaPuntasPorosidadResistencia', 'Porosidad alta, resistencia moderada.',
        'PruebaAlergia', 'Prueba de alergia realizada sin reacción.',
        'PruebaMechon', 'Prueba de mechón satisfactoria. Aclaración controlada.',
        'ObjetivoAltura', '8',
        'ObjetivoReflejo', 'Ceniza',
        'ObjetivoFondoPrevisto', 'Fondo de aclaración amarillo pálido.',
        'TecnicaJustificacion', 'Realizar mechas balayage para aportar luminosidad.',
        'VistoBuenoDocente', 1,
        'MarcaLinea', 'Polvo decolorante y línea profesional de matización.',
        'PreparacionPrevia', 'Protección de las zonas de trabajo y separación del cabello.',
        'OrdenAplicacion', 'Aplicación de decolorante en secciones seleccionadas y posterior matización.',
        'InicioAplicacion', '2026-11-04 09:30:00',
        'RetiradaAplicacion', '2026-11-04 11:00:00',
        'TiempoAplicacionMinutos', 90,
        'IncidenciasAjustesTratamientoFinal', 'Aclaración correcta. Se controló visualmente el proceso.',
        'TonoReflejoFinal', 'Rubio claro ceniza.',
        'CanasCubiertas', 3,
        'Uniforme', TRUE,
        'CuidadosMantenimiento', 'Utilizar champú violeta de forma moderada y mascarilla hidratante.',
        'QueSalioBienMejoraria', 'Buena distribución de las mechas.',
        'ValoracionRevisionFinalDocente', 'Técnica correctamente ejecutada. Resultado homogéneo y adecuado al objetivo planteado.'
    ),

    JSON_ARRAY(
        JSON_OBJECT(
            'IDFormula', 3,
            'ZonaFase', 'Mechas',
            'Tono', 'Polvo decolorante azul',
            'Gramos', 30.00,
            'Oxidante', '30 volúmenes',
            'CantidadOxidante', 60.00,
            'Proporcion', '1:2',
            'Tiempo', '45 minutos'
        ),
        JSON_OBJECT(
            'IDFormula', 4,
            'ZonaFase', 'Matiz',
            'Tono', '9.1 Rubio muy claro ceniza',
            'Gramos', 30.00,
            'Oxidante', '10 volúmenes',
            'CantidadOxidante', 60.00,
            'Proporcion', '1:2',
            'Tiempo', '15 minutos'
        )
    ),

    '2026-11-04 13:00:00',
    4
);


-- =====================================================================
-- 13. HISTORIAL FUNCIONAL DE FICHAS
-- =====================================================================
--
-- Se registran las operaciones de creación y finalización.
-- Las fichas finalizadas tienen ambos eventos.
-- La ficha 3 solamente tiene el evento de creación.
--
-- IDFichaReferencia conserva el identificador original de la ficha.
-- =====================================================================

INSERT INTO HISTORIAL_FICHAS_TRATAMIENTO (
    IDHistorialFicha,
    IDFichaReferencia,
    FK_Ficha,
    Operacion,
    EstadoAnterior,
    EstadoNuevo,
    DatosAnteriores,
    DatosNuevos,
    Motivo,
    FechaOperacion,
    FK_peluquero
)
VALUES

-- Creación de ficha 1.

(
    1,
    1,
    1,
    'CREACION',
    NULL,
    'borrador',
    NULL,
    JSON_OBJECT(
        'IDFicha', 1,
        'FK_Cliente', 1,
        'FK_Peluquero', 2,
        'Estado', 'borrador'
    ),
    'Creación inicial de la ficha de tratamiento.',
    '2026-11-02 09:00:00',
    2
),

-- Envío y finalización de ficha 1.

(
    2,
    1,
    1,
    'ENVIO',
    'borrador',
    'enviada',
    JSON_OBJECT('Estado', 'borrador'),
    JSON_OBJECT('Estado', 'enviada'),
    'Ficha enviada para revisión docente.',
    '2026-11-02 09:15:00',
    2
),
(
    3,
    1,
    1,
    'FINALIZACION',
    'enviada',
    'finalizada',
    JSON_OBJECT('Estado', 'enviada'),
    JSON_OBJECT(
        'Estado', 'finalizada',
        'FueFinalizada', TRUE,
        'FK_Profesor', 4,
        'NumeroVersion', 1
    ),
    'Ficha revisada y finalizada por el profesor.',
    '2026-11-02 12:00:00',
    4
),

-- Creación de ficha 2.

(
    4,
    2,
    2,
    'CREACION',
    NULL,
    'borrador',
    NULL,
    JSON_OBJECT(
        'IDFicha', 2,
        'FK_Cliente', 2,
        'FK_Peluquero', 3,
        'Estado', 'borrador'
    ),
    'Creación inicial de la ficha de tratamiento.',
    '2026-11-04 09:00:00',
    3
),

-- Envío y finalización de ficha 2.

(
    5,
    2,
    2,
    'ENVIO',
    'borrador',
    'enviada',
    JSON_OBJECT('Estado', 'borrador'),
    JSON_OBJECT('Estado', 'enviada'),
    'Ficha enviada para revisión docente.',
    '2026-11-04 09:20:00',
    3
),
(
    6,
    2,
    2,
    'FINALIZACION',
    'enviada',
    'finalizada',
    JSON_OBJECT('Estado', 'enviada'),
    JSON_OBJECT(
        'Estado', 'finalizada',
        'FueFinalizada', TRUE,
        'FK_Profesor', 4,
        'NumeroVersion', 1
    ),
    'Ficha revisada y finalizada por el profesor.',
    '2026-11-04 13:00:00',
    4
),

-- Creación de ficha 3.

(
    7,
    3,
    3,
    'CREACION',
    NULL,
    'borrador',
    NULL,
    JSON_OBJECT(
        'IDFicha', 3,
        'FK_Cliente', 3,
        'FK_Peluquero', 2,
        'Estado', 'borrador'
    ),
    'Creación inicial de la ficha de tratamiento. Pendiente de completar.',
    '2026-11-10 09:00:00',
    2
);


-- =====================================================================
-- 14. COMPROBACIÓN FINAL
-- =====================================================================

SELECT 'ROLES' AS Tabla, COUNT(*) AS Registros FROM ROLES
UNION ALL
SELECT 'PELUQUEROS', COUNT(*) FROM PELUQUEROS
UNION ALL
SELECT 'CLIENTES', COUNT(*) FROM CLIENTES
UNION ALL
SELECT 'SERVICIOS', COUNT(*) FROM SERVICIOS
UNION ALL
SELECT 'SERVICIOSPRECIOS', COUNT(*) FROM SERVICIOSPRECIOS
UNION ALL
SELECT 'HISTORIALCLIENTES', COUNT(*) FROM HISTORIALCLIENTES
UNION ALL
SELECT 'FACTURA', COUNT(*) FROM FACTURA
UNION ALL
SELECT 'AGENDA', COUNT(*) FROM AGENDA
UNION ALL
SELECT 'FICHAS_TRATAMIENTO', COUNT(*) FROM FICHAS_TRATAMIENTO
UNION ALL
SELECT 'FORMULAS_TRATAMIENTO', COUNT(*) FROM FORMULAS_TRATAMIENTO
UNION ALL
SELECT 'VERSIONES_FICHAS_TRATAMIENTO', COUNT(*) FROM VERSIONES_FICHAS_TRATAMIENTO
UNION ALL
SELECT 'HISTORIAL_FICHAS_TRATAMIENTO', COUNT(*) FROM HISTORIAL_FICHAS_TRATAMIENTO;

-- Comprobar el estado y la asignación de responsables de las fichas.

SELECT
    F.IDFicha,
    F.Estado,
    F.FueFinalizada,
    F.FK_Cliente,
    F.FK_Peluquero,
    F.FK_Profesor,
    COUNT(DISTINCT V.IDVersion) AS Versiones,
    COUNT(DISTINCT H.IDHistorialFicha) AS OperacionesHistorial
FROM FICHAS_TRATAMIENTO F
LEFT JOIN VERSIONES_FICHAS_TRATAMIENTO V
    ON V.FK_Ficha = F.IDFicha
LEFT JOIN HISTORIAL_FICHAS_TRATAMIENTO H
    ON H.IDFichaReferencia = F.IDFicha
GROUP BY
    F.IDFicha,
    F.Estado,
    F.FueFinalizada,
    F.FK_Cliente,
    F.FK_Peluquero,
    F.FK_Profesor
ORDER BY F.IDFicha;

-- =====================================================================
-- FIN DEL SCRIPT
-- =====================================================================
