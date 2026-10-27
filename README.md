# Ruta Norte · venta de boletos

Aplicación PHP/MySQL agregada al portafolio existente. Abre `/boletos/` para el buscador y el counter. Si copiaste los archivos directamente a `htdocs`, entra al dominio raíz y conserva la carpeta `assets` junto a `index.php`.

## Puesta en marcha

1. Crea una base MySQL desde el panel del hosting y selecciónala en phpMyAdmin antes de importar `schema.sql`. En InfinityFree el nombre de la base incluye el prefijo de tu cuenta; el script no intenta crear una base por su cuenta.
2. Copia `config.example.php` como `config.local.php` y configura el host, nombre completo de la base, usuario y contraseña que muestra el panel. `config.local.php` está pensado para credenciales locales y no se incluye en el repositorio.
3. Abre `/boletos/`. El script carga tres buses, cuatro rutas y diez viajes, con asientos distribuidos por bus.
4. Programa `php /ruta/al/proyecto/boletos/expire_holds.php` para ejecutarse cada minuto. La pantalla del mapa también libera reservas vencidas al consultarse.

## Flujos incluidos

- Búsqueda por ciudad y fecha; mapa por piso y tipo de asiento.
- Reserva de 10 minutos con transacción y bloqueo de fila en MySQL InnoDB. La venta ocupa una plaza única por viaje y asiento.
- Registro del pasajero, boleto imprimible con QR, manifiesto y validación de embarque.
- Ocupación e ingresos por viaje; política base de penalidades editable en `post_sale_policies`.
- Diagrama ER en `diagrama-er.md`.

El boleto se imprime desde el navegador; el diálogo de impresión permite escoger impresora térmica de 58 mm o guardar como PDF. La lectura QR del manifiesto acepta el valor del código ingresado por un lector que funcione como teclado.

## Alcance de demostración

El flujo principal de counter, reserva concurrente y embarque está implementado. La configuración del administrador para crear buses/rutas/viajes, venta de varios asientos en una sola operación, cambio/anulación con cobro de penalidad, gestión de usuarios/roles y pagos en línea requieren completar su interfaz e integración. El counter guarda el método y monto del pago al emitir cada boleto. El QR de la impresión usa la biblioteca QRCode.js desde CDN, por lo que necesita acceso a internet al abrir el boleto.

- Simulador en simulador.php: guarda y borra datos ficticios en demo_records, separados de boletos y pagos reales.


- Registro de pasajeros en pasajeros.php: crea, lista y borra personas; no permite borrar a alguien que tenga boletos vinculados.


## Impresión central

Al emitir un boleto, el servidor PHP puede encolarlo automáticamente en Central de impresión. Configura las tres constantes `CENTRAL_PRINT_*` en `config.local.php`; nunca expongas los tokens en el navegador. El envío usa el identificador del boleto como clave idempotente. La cola central recibe texto y el código QR como valor de texto; para imprimir un QR escaneable desde la térmica, el agente central debe soportar el comando QR ESC/POS.
