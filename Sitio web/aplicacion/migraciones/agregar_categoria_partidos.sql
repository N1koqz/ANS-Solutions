ALTER TABLE partidos
    ADD COLUMN id_categoria INT NULL,
    ADD CONSTRAINT fk_partidos_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria);
