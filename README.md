# Seberih

Sitio web del complejo deportivo Seberih. La reserva requiere una cuenta con nombre y correo; los turnos se bloquean por cancha y se confirman únicamente después de verificar la seña en Mercado Pago.

## Requisitos

- PHP 8.0 o superior con `curl`, `pdo_mysql`, `openssl` y `iconv`.
- MariaDB o MySQL.
- Una URL HTTPS pública para usar los pagos y notificaciones reales de Mercado Pago.

## Preparar el entorno local

1. Copiá `.env.example` a `.env` y completá los datos de la base de datos. El archivo `.env` es local y no se publica en Git. Para probar reservas sin cobrar en esta PC, poné `APP_ENV=local` y `BOOKING_DEMO_MODE=true`.
2. Creá una base de datos llamada `seberih` y un usuario con permisos para crear tablas dentro de esa base.
3. Para activar pagos, cargá `MERCADOPAGO_ACCESS_TOKEN` y `MERCADOPAGO_WEBHOOK_SECRET` de la aplicación del complejo. En Mercado Pago configurá notificaciones de pago a `https://tu-dominio/webhook.php` y usá ese mismo dominio en `APP_URL`.
4. Iniciá el sitio desde la raíz del repositorio:

   ```bash
   php -S localhost:8000 -t public
   ```

5. Abrí `http://localhost:8000`. La primera visita crea las tablas requeridas. En un equipo local, las cuentas y disponibilidad funcionan, pero Mercado Pago necesita credenciales válidas y una dirección HTTPS públicamente accesible para completar los pagos y recibir las notificaciones.

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
