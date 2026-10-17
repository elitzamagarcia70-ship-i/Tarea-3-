-- Solo si ya tenías la base creada con la versión anterior: renombra las columnas al modelo del laboratorio.
USE gestion_docs;
ALTER TABLE documentos
  CHANGE id id_documento INT AUTO_INCREMENT,
  CHANGE archivo archivo_pdf VARCHAR(255) NULL;
