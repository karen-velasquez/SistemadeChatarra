# Sistema de Compra y Venta de Chatarra

Aplicación web desarrollada en **Laravel 10** para gestionar todo el ciclo
de un negocio de compra y venta de chatarra: proveedores, clientes,
contratos, transporte, pagos, cobros y tesorería.

---

## 🧩 Módulos del sistema

| Módulo | Qué hace |
|---|---|
| **Dashboard** | Resumen de indicadores clave del negocio. |
| **Contratos** | Compra/venta de chatarra, asignación de camiones, tramos y liquidación de envíos. |
| **Proveedores** | Registro de proveedores, historial de pagos y pago masivo. |
| **Transporte** | Camiones, propietarios/conductores, asignaciones, seguimiento de cargas y pago de fletes. |
| **Clientes** | Registro de clientes y cobros (individuales y masivos). |
| **Tesorería** | Movimientos, empresas y cuentas, préstamos internos y lotes de pago. |
| **Bancos y Cuentas** | Catálogo de bancos y cuentas bancarias de terceros. |
| **Empleados** | Gestión del personal de la empresa. |
| **Gastos Extra** | Gastos adicionales asociados a los contratos. |
| **Reportes** | Reportes de utilidad, capital y operativos, exportables a Excel. |
| **Parámetros** | Catálogos del sistema (países, monedas, cargos, etc.). |
| **Administración** | Usuarios, roles y permisos. |

---

## 🧭 Guías interactivas

El sistema incluye **guías paso a paso** integradas en cada pantalla y
formulario. Se activan con el botón de ayuda (❓) que aparece en la cabecera
de cada módulo.

- Librería usada: **[Shepherd.js](https://shepherdjs.dev) 11.2.0** (licencia MIT,
  apta para uso comercial), cargada por CDN.
- Las guías se definen con un atributo `data-steps` en cada botón
  `.btn-iniciar-tour`. Un *listener* global lee ese atributo y lanza el tour.
- La infraestructura está en:
  - `resources/views/layouts/partials/styles.blade.php` (estilos)
  - `resources/views/layouts/partials/scripts.blade.php` (lógica)

### Cómo agregar una guía a una vista nueva

Basta con un botón con la clase `.btn-iniciar-tour` y el atributo `data-steps`:

```html
<button type="button" class="btn btn-outline-primary btn-sm btn-iniciar-tour"
        data-steps='[
            {"intro":"Bienvenido a esta pantalla."},
            {"element":"#mi-tabla","intro":"Aquí ves los registros.","position":"top"}
        ]'>
    <i class="bi bi-question-circle"></i>
</button>
```

Para guías dentro de un modal de Bootstrap, agrega `data-tour-modal="#idDelModal"`.

---

## 🛠️ Tecnologías

- **Laravel 10** (PHP ^8.1)
- **MySQL** como base de datos
- **Bootstrap 5** + Bootstrap Icons (interfaz)
- **DataTables** (tablas con búsqueda y paginación)
- **Shepherd.js** (guías interactivas)
- Paquetes destacados:
  - `spatie/laravel-permission` — roles y permisos
  - `barryvdh/laravel-dompdf` — generación de PDFs (notas de entrega, etc.)
  - `maatwebsite/excel` — exportación a Excel
  - `spatie/laravel-html`

---

## 🚀 Instalación (entorno local)

```bash
# 1. Clonar el repositorio
git clone https://github.com/Daniel300498/Sistema-de-compra-y-venta-de-chatarra-LARAVEL.git
cd Sistema-de-compra-y-venta-de-chatarra-LARAVEL

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias de assets (opcional, solo si editas app.js/app.css)
npm install

# 4. Copiar el archivo de entorno y generar la clave
cp .env.example .env
php artisan key:generate

# 5. Configurar la base de datos en .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 6. Ejecutar migraciones y seeders (datos iniciales)
php artisan migrate --seed

# 7. Levantar el servidor de desarrollo
php artisan serve
```

El sistema quedará disponible en `http://127.0.0.1:8000`.

---

## 📦 Despliegue (Hostinger)

> **Nota:** el plan **Single** de Hostinger no incluye SSH/SFTP, y el FTP
> queda bloqueado para conexiones automáticas desde servicios externos
> (como GitHub Actions). Por eso el despliegue se realiza **manualmente
> por FTP** con un cliente como **FileZilla**.

### Subir cambios con FileZilla

1. Conéctate por FTP:
   - **Host:** la IP del FTP de Hostinger
   - **Usuario / Contraseña:** los de tu cuenta FTP
   - **Puerto:** `21`
2. En el servidor, navega a: `public_html/chatarra/`
3. Sube **solo los archivos que cambiaron** (respetando la estructura de carpetas).
   No subas `vendor/`, `node_modules/` ni `.env` (ya están configurados en el servidor).
4. Si cambiaste vistas Blade y no se reflejan, borra los `.php` de
   `storage/framework/views/` en el servidor (caché de vistas; Laravel los regenera).

---

## 👥 Roles y permisos

El acceso a cada módulo está controlado por **roles y permisos**
(`spatie/laravel-permission`). Un usuario solo ve en el menú los módulos
para los que tiene permiso. Los roles se gestionan desde
**Administración → Roles**.

---

## 📄 Licencia

Proyecto privado. Construido sobre el framework Laravel, que es software
de código abierto bajo licencia [MIT](https://opensource.org/licenses/MIT).
