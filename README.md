# Seberih

Sitio web del complejo deportivo Seberih. La reserva requiere una cuenta con nombre y correo; los turnos se bloquean por cancha y se confirman únicamente después de verificar la seña en Mercado Pago.

## Requisitos

- PHP 8.1 o superior con `curl`, `pdo_mysql`, `openssl` e `iconv`.
- MariaDB o MySQL.
- Una URL HTTPS pública para usar los pagos y notificaciones reales de Mercado Pago.
- Docker Engine y Docker Compose para el entorno contenerizado y los servicios de operación.

## Preparar el entorno local

1. Copiá `.env.example` a `.env` y completá los datos de la base de datos. El archivo `.env` es local y no se publica en Git. Para probar reservas sin cobrar en esta PC, poné `APP_ENV=local` y `BOOKING_DEMO_MODE=true`.
2. Creá una base de datos llamada `seberih` y un usuario con permisos para crear tablas dentro de esa base.
3. Para activar pagos, cargá `MERCADOPAGO_ACCESS_TOKEN` y `MERCADOPAGO_WEBHOOK_SECRET` de la aplicación del complejo. En Mercado Pago configurá notificaciones de pago a `https://tu-dominio/webhook.php` y usá ese mismo dominio en `APP_URL`.
4. Iniciá el sitio desde la raíz del repositorio:

   ```bash
   php -S localhost:8000 -t public src/dev-server-router.php
   ```

5. Abrí `http://localhost:8000`. La primera visita crea las tablas requeridas. En un equipo local, las cuentas y disponibilidad funcionan, pero Mercado Pago necesita credenciales válidas y una dirección HTTPS públicamente accesible para completar los pagos y recibir las notificaciones.

## Entorno con Docker Compose

El entorno separa Apache/PHP de MariaDB. El puerto de la base de datos no se publica al host; los servicios se conectan por una red privada y los datos sobreviven a los reinicios en el volumen nombrado `mariadb_data`. El archivo `storage/reservations.json` continúa en una carpeta del host. Para mostrar las tablas se incluye Adminer, una interfaz web de administración que solo se activa con el perfil local `database-ui`.

1. Si todavía no existe, copiá `.env.example` a `.env` y definí contraseñas propias para `DB_PASSWORD` y `DB_ROOT_PASSWORD`. No subas `.env` a Git.
2. Revisá la configuración sin imprimir secretos: `docker compose config -q`.
3. Para iniciar la página y la interfaz de base de datos: `docker compose --profile database-ui up --build -d`.
4. Abrí `http://localhost:8080` (o el puerto configurado en `WEB_PORT`) para el sitio y `http://localhost:8081` (o `ADMINER_PORT`) para Adminer.
5. En Adminer, seleccioná el motor MySQL, usá `db` como servidor, el valor `DB_NAME` como base y las credenciales `DB_USER` / `DB_PASSWORD` de `.env`.
6. Para detenerlos conservando los datos: `docker compose --profile database-ui down`. No uses `docker compose down -v` salvo que quieras borrar también la base de datos.

Adminer solo publica el puerto en `127.0.0.1` (esta computadora). El acceso se hace con las credenciales de MariaDB; no compartas la contraseña root con el cliente. El acceso directo de Windows inicia el perfil de demostración y abre el sitio y Adminer. El servicio no se inicia en los comandos normales de producción, a menos que se active expresamente el perfil.

MariaDB inicializa `database/schema.sql` al crear el volumen por primera vez. Los errores de PHP y Apache y los registros de acceso quedan en `storage/logs/`.

### Abrir en Windows con doble clic

- Instala y abre Docker Desktop antes de usar el proyecto por primera vez.
- Haz doble clic en `Abrir Seberih.bat`. El archivo inicia Docker Compose en modo local de prueba y abre el sitio en el navegador; la primera vez puede tardar mientras se descargan las imágenes.
- Al copiar el proyecto a otra computadora, copia la carpeta completa para conservar `storage/reservations.json`. Las cuentas se almacenan en el volumen local de MariaDB, así que cada computadora tendrá su propia base de usuarios. GitHub no incluye los datos locales ni el volumen de Docker.

## Capas de la aplicación

- `src/Domain/`: reglas puras de cuenta y reservas, sin acceso a HTTP ni a PDO.
- `src/Application/`: casos de uso, como registrar y autenticar una cuenta.
- `src/Infrastructure/`: adaptadores de persistencia, actualmente el repositorio PDO de usuarios.
- `src/Controller/`: punto de entrada HTTP y controladores.
- `src/Views/`: vistas de inicio y cuenta.
- `public/`: punto de entrada, rutas HTTP y recursos estáticos.

El registro e inicio de sesión usan `fetch()` con respuestas JSON y conservan el envío HTML de respaldo. Los flujos de reserva y disponibilidad ya usan JSON; el almacenamiento de reservas existente se mantuvo para no cambiar la demo.

## Operación en Linux

Los scripts de mantenimiento están en `scripts/`:

- `backup_database.sh`: genera volcados comprimidos de MariaDB y copia el archivo local de reservas demo; conserva 30 días por defecto (`BACKUP_RETENTION_DAYS`).
- `audit_resources.sh`: agrega uso de disco y estado/consumo de Docker a `storage/logs/resource-audit.log`.
- `monitor_security.sh`: busca patrones de solicitudes sospechosas en el log de acceso y genera un informe sin guardar credenciales.
- `provision/init_system.sh`: instala unidades de usuario de systemd para iniciar Compose al arrancar y auditar recursos cada 15 minutos; configura logrotate para conservar 14 rotaciones y activa `loginctl linger`.
- `provision/install_backup_cron.sh`: instala, para el usuario técnico no root, el respaldo diario a las 02:00 con retención de 30 días. Requiere Docker Compose, `crontab` y `.env`; revisar y probar restauración y copia externa antes de usar en producción.

Los documentos académicos se generan con `scripts/generate_egreso_pdf.py` y `scripts/generate_management_pdf.py`; sus salidas están en `output/pdf/`. El modelo conceptual 3D de la marca está en `assets/3d/matecodeuy-logo.scad`; es un archivo digital pendiente de impresión física.

En un servidor Linux con systemd, logrotate, sudo y Docker rootless configurado, prepará `.env` y ejecutá `./provision/init_system.sh` como el usuario técnico dedicado (nunca como root). Después de actualizar código, reconstruí e iniciá con `docker compose up --build -d`. El instalador usa `sudo` solo para instalar la regla de logrotate y habilitar el inicio sin sesión interactiva. Configurá `APP_ENV=production`, `BOOKING_DEMO_MODE=false`, `SESSION_COOKIE_SECURE=true`, `APP_URL` con HTTPS y las credenciales reales antes de publicar pagos.

## Reglas de reserva

- Tarifa inicial: $ 1.200 UYU por hora y por cancha.
- Seña inicial: 15% ($ 180 UYU por una hora).
- Las cuentas quedan en la base MariaDB local; las reservas de prueba se guardan en el archivo `storage/reservations.json`. Ninguno de esos datos se sube a GitHub.
- Una cuenta autenticada puede pedir de una a ocho horas seguidas en una de dos canchas, sujeto al horario de apertura. Cada bloque queda ocupado y la base de datos impide cruzarlo con otra reserva.
- El turno queda pendiente durante 15 minutos mientras se paga. El sitio verifica la firma de las notificaciones y consulta el pago a Mercado Pago; no usa la página de retorno como prueba de pago.
- En modo local de prueba (`APP_ENV=local` y `BOOKING_DEMO_MODE=true`), las reservas se guardan en `storage/reservations.json`, ocupan el horario y no generan un cobro. La pantalla explica la seña que se deberá pagar para confirmar una reserva real.
- El propietario puede revisar las reservas y cancelar una para liberar su horario en `/admin.php`, solo desde la misma computadora. Para llevarlas en un pendrive, copiá ese archivo junto con el proyecto. Una descarga nueva desde GitHub no incluye los datos locales. Mantené `BOOKING_DEMO_MODE=false` en producción.
- Cada usuario con sesión puede consultar sus reservas de prueba en `/mis-reservas.php`, cambiar cancha, fecha, duración u horario, o cancelar y liberar el turno antes de que comience. Las reservas con pago se muestran, pero sus cambios deben coordinarse con el complejo hasta definir la política de señas y devoluciones.
- Se puede elegir desde hoy hasta la misma fecha de aquí a tres meses. Hay accesos rápidos a los próximos siete días y un calendario mensual para el resto del período.
- Horarios iniciales (hora de comienzo): 09:00, 10:00, 11:00, 12:00 y 14:00 a 21:00. Son provisionales y deben ajustarse al horario real del complejo.

Las credenciales se obtienen de la aplicación de Seberih en Mercado Pago Developers. La integración sigue la [guía oficial para crear preferencias de Checkout Pro](https://www.mercadopago.com.uy/developers/es/docs/checkout-pro-preferences/create-payment-preference) y la [guía oficial de notificaciones y firma de Webhooks](https://www.mercadopago.com.uy/developers/es/docs/checkout-pro-preferences/additional-content/notifications/webhooks).
