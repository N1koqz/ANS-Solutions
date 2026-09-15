CREATE DATABASE IF NOT EXISTS mgfut CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mgfut;

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'club') NOT NULL DEFAULT 'club',
    club VARCHAR(20) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS club (
    id_club INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    ciudad VARCHAR(50) NOT NULL,
    fundacion YEAR NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jugadores (
    ci INT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    posicion VARCHAR(30) NOT NULL,
    dorsal INT NOT NULL,
    id_club INT NOT NULL,
    id_categoria INT NOT NULL,
    masa INT NULL,
    altura INT NULL,
    fuerza_peso INT NULL,
    CONSTRAINT fk_jugador_club FOREIGN KEY (id_club) REFERENCES club(id_club),
    CONSTRAINT fk_jugador_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria),
    CONSTRAINT chk_dorsal CHECK (dorsal BETWEEN 1 AND 99)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS partidos (
    id_partido INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    estadio VARCHAR(50) NOT NULL,
    id_club_local INT NOT NULL,
    id_club_visitante INT NOT NULL,
    goles_local INT NOT NULL DEFAULT 0,
    goles_visitante INT NOT NULL DEFAULT 0,
    velocidad_pelota DECIMAL(6,2) NULL,
    CONSTRAINT fk_partido_local FOREIGN KEY (id_club_local) REFERENCES club(id_club),
    CONSTRAINT fk_partido_visitante FOREIGN KEY (id_club_visitante) REFERENCES club(id_club),
    CONSTRAINT chk_clubes_distintos CHECK (id_club_local <> id_club_visitante),
    CONSTRAINT chk_goles_no_negativos CHECK (goles_local >= 0 AND goles_visitante >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estadisticas (
    id_estadistica INT AUTO_INCREMENT PRIMARY KEY,
    id_partido INT NOT NULL,
    ci_jugador INT NOT NULL,
    goles INT NOT NULL DEFAULT 0,
    asistencias INT NOT NULL DEFAULT 0,
    amarillas INT NOT NULL DEFAULT 0,
    rojas INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_estadistica_partido_jugador (id_partido, ci_jugador),
    FOREIGN KEY (id_partido) REFERENCES partidos(id_partido) ON DELETE CASCADE,
    FOREIGN KEY (ci_jugador) REFERENCES jugadores(ci)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carnet_salud (
    id_carnet INT AUTO_INCREMENT PRIMARY KEY,
    ci_jugador INT NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE NOT NULL,
    subido_por INT NOT NULL,
    FOREIGN KEY (ci_jugador) REFERENCES jugadores(ci) ON DELETE CASCADE,
    FOREIGN KEY (subido_por) REFERENCES usuario(id_usuario),
    INDEX idx_carnet_jugador_vencimiento (ci_jugador, fecha_vencimiento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sanciones (
    id_sancion INT AUTO_INCREMENT PRIMARY KEY,
    ci_jugador INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    boletin_pdf VARCHAR(255) NULL,
    creada_por INT NOT NULL,
    FOREIGN KEY (ci_jugador) REFERENCES jugadores(ci) ON DELETE CASCADE,
    FOREIGN KEY (creada_por) REFERENCES usuario(id_usuario),
    CONSTRAINT chk_sancion_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    id_registro INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE', 'UPLOAD') NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

INSERT INTO categorias (id_categoria, nombre) VALUES
(1, '2013'), (2, '2014'), (3, '2015'), (4, '2016'),
(5, '2017'), (6, '2018'), (7, '2019'), (8, 'Femenino Única')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO usuario (nombre, email, contraseña, rol)
SELECT 'Admin', 'admin@gmail.com', '1234', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE email = 'admin@gmail.com');
