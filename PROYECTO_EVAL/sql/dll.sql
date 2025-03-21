DROP DATABASE IF EXISTS BD_Proyecto;

CREATE DATABASE BD_Proyecto;

USE BD_Proyecto;

CREATE TABLE users(
    id_user INT AUTO_INCREMENT PRIMARY KEY, 
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE aulas(
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(5) NOT NULL UNIQUE
);

CREATE TABLE aulas_profes(
    id_aula INT NOT NULL,
    id_user INT NOT NULL,
    PRIMARY KEY (id_aula, id_user), -- Clave primaria compuesta
    FOREIGN KEY (id_aula) REFERENCES aulas(id_aula),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE puestos(
    id_puesto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(10)
);

CREATE TABLE tipos_incidencias(
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL
);

CREATE TABLE incidencias (
    id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_aula INT NOT NULL,
    id_user INT NOT NULL,
    id_tipo INT NOT NULL,
    id_puesto INT NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente', 'En proceso', 'Resuelto', 'Cerrado', 'Cancelado') DEFAULT 'Pendiente',
    fecha_cierre TIMESTAMP NULL DEFAULT NULL, -- Fecha cuando se cierra la incidencia
    solucion TEXT NULL, -- Explicación de la solución
    FOREIGN KEY (id_aula) REFERENCES aulas(id_aula),
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_tipo) REFERENCES tipos_incidencias(id_tipo),
    FOREIGN KEY (id_puesto) REFERENCES puestos(id_puesto)
);


INSERT INTO tipos_incidencias (tipo) VALUES
('Problema trcnico'),
('Fallo de equipo'),
('Problema de software'),
('Problema de conectividad'),
('Fallo de Configuracion'),
('Otro tipo de incidencia');

INSERT INTO aulas (nombre)
VALUES
('B22'),
('B23'),
('B24'),
('B25');

INSERT INTO puestos (nombre)
VALUES
('A1'),
('A2'),
('B1'),
('B2');


INSERT INTO incidencias (id_aula, id_user, id_tipo, id_puesto, descripcion, estado, fecha_cierre, solucion)
VALUES(1, 12, 1, 1, 'Descripción de la incidencia relacionada con el aula y el puesto', 'Pendiente', NULL, NULL);

CREATE USER ad_proyecto IDENTIFIED BY "1234";
GRANT ALL ON BD_Proyecto.* TO ad_proyecto;