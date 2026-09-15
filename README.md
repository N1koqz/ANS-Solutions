## MG Fut

### Puesta en marcha

1. Crear/importar la base de datos ejecutando `Sitio web/schema.sql` en MySQL.
2. Configurar `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` y `DB_PORT` si las credenciales no son las predeterminadas (`127.0.0.1`, `root`, sin contraseña, `mgfut`, `3306`).
3. Servir `Sitio web/` desde Apache/PHP con la extensión `mysqli` habilitada.
4. Iniciar con `admin@gmail.com` y `1234`; la contraseña se convierte a hash al primer acceso.

El rol `admin` registra y edita clubes, jugadores, partidos y sanciones. El rol `club` puede consultar la información y subir únicamente carnets de salud PDF; cada subida queda como historial en `carnet_salud`. La tabla `auditoria` registra las modificaciones relevantes.
Probando acceso al repositorio.
