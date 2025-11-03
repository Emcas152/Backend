# 🏥 CRM Spa Médico - Backend API

Backend RESTful API construido con Laravel 11 para gestión de spa médico con sistema de roles, autenticación segura y protección contra XSS.

## 🚀 Características

### Funcionalidades Principales

- ✅ **Gestión de Pacientes** - CRUD completo con fotos, documentos y QR codes
- ✅ **Sistema de Citas** - Calendario con validación de conflictos de horarios
- ✅ **Productos e Inventario** - Gestión de stock con alertas de bajo inventario
- ✅ **Punto de Venta (POS)** - Sistema de ventas con múltiples items
- ✅ **Programa de Lealtad** - Puntos acumulables y canjeables
- ✅ **Documentos Firmables** - Subida y firma digital de consentimientos
- ✅ **Dashboard con Estadísticas** - Métricas de ventas, pacientes y desempeño

### Sistema de Roles

- 👨‍💼 **Admin** - Acceso completo al sistema
- 👨‍⚕️ **Doctor** - Acceso filtrado solo a sus pacientes asignados
- 👥 **Staff** - Gestión de citas, ventas y pacientes
- 🧑 **Patient** - Portal personal con historial y citas

### 🔐 Seguridad Implementada

- ✅ **Protección XSS** - Sanitización automática de entradas
- ✅ **SQL Injection** - Eloquent ORM con prepared statements
- ✅ **CSRF Protection** - Laravel Sanctum
- ✅ **Headers de Seguridad** - CSP, X-XSS-Protection, HSTS
- ✅ **Validación Estricta** - Regex patterns y límites
- ✅ **Autenticación Token-based** - Laravel Sanctum
- ✅ **Subida Segura de Archivos** - MIME validation

📖 **[Ver documentación completa de seguridad](SECURITY.md)**

## 📋 Requisitos

- PHP 8.1 o superior
- Composer
- MySQL 8.0 o MariaDB 10.3+
- Extensiones PHP:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD (para QR codes)

## 🛠️ Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/Emcas152/Backend.git
cd Backend
```

### 2. Instalar Dependencias

```bash
composer install
```

### 3. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar Base de Datos

Edita el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_spa_medico
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 5. Ejecutar Migraciones y Seeders

```bash
php artisan migrate --seed
```

Esto creará:
- Estructura de base de datos (10 tablas)
- Usuario administrador por defecto
- Datos de prueba (staff members, appointments, sales)

### 6. Crear Enlace Simbólico para Storage

```bash
php artisan storage:link
```

### 7. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

El servidor estará disponible en: `http://localhost:8000`

## 🔑 Credenciales por Defecto

**Administrador:**
- Email: `admin@crmmedico.com`
- Password: `admin123`

**Doctor:**
- Email: `doctor@crmmedico.com`
- Password: `doctor123`

**Staff:**
- Email: `staff@crmmedico.com`
- Password: `staff123`

**⚠️ Cambiar estas contraseñas en producción**

## 📚 API Endpoints

### Autenticación

```
POST   /api/login              - Iniciar sesión
POST   /api/logout             - Cerrar sesión
GET    /api/me                 - Usuario actual
POST   /api/register-patient   - Registro de paciente
POST   /api/register-staff     - Registro de staff (admin only)
```

### Pacientes

```
GET    /api/patients                           - Listar pacientes
POST   /api/patients                           - Crear paciente
GET    /api/patients/{id}                      - Ver paciente
PUT    /api/patients/{id}                      - Actualizar paciente
DELETE /api/patients/{id}                      - Eliminar paciente
POST   /api/patients/{id}/upload-photo         - Subir foto (before/after)
POST   /api/patients/{id}/upload-document      - Subir documento
POST   /api/patients/{id}/documents/{doc}/sign - Firmar documento
POST   /api/patients/{id}/loyalty/add          - Añadir puntos
POST   /api/patients/{id}/loyalty/redeem       - Canjear puntos
```

### Productos

```
GET    /api/products                    - Listar productos
POST   /api/products                    - Crear producto
GET    /api/products/{id}               - Ver producto
PUT    /api/products/{id}               - Actualizar producto
DELETE /api/products/{id}               - Eliminar producto
POST   /api/products/{id}/adjust-stock  - Ajustar inventario
```

### Ventas

```
GET    /api/sales             - Listar ventas
POST   /api/sales             - Crear venta
GET    /api/sales/{id}        - Ver venta
GET    /api/sales-statistics  - Estadísticas de ventas
```

### Citas

```
GET    /api/appointments              - Listar citas
POST   /api/appointments              - Crear cita
GET    /api/appointments/{id}         - Ver cita
PUT    /api/appointments/{id}         - Actualizar cita
DELETE /api/appointments/{id}         - Eliminar cita
PATCH  /api/appointments/{id}/status  - Cambiar estado
```

### Dashboard

```
GET    /api/dashboard/stats   - Estadísticas del dashboard
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter=PatientTest
```

### API Testing con Postman

Importa la colección desde: `API_TESTING.md`

## 🔐 Middlewares de Seguridad

### SanitizeInput

Sanitiza automáticamente todas las entradas de usuario:

```php
// Ubicación: app/Http/Middleware/SanitizeInput.php
// Se aplica a todas las rutas API
```

**Funciones:**
- Convierte caracteres especiales a HTML entities
- Elimina scripts y etiquetas peligrosas
- Elimina eventos JavaScript inline
- Strip tags de todo HTML

### SecurityHeaders

Agrega headers de seguridad a todas las respuestas:

```php
// Ubicación: app/Http/Middleware/SecurityHeaders.php
```

**Headers incluidos:**
- `X-XSS-Protection: 1; mode=block`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Content-Security-Policy`
- `Strict-Transport-Security` (en HTTPS)
- `Referrer-Policy`
- `Permissions-Policy`

## 📁 Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── PatientController.php
│   │       ├── ProductController.php
│   │       ├── SaleController.php
│   │       ├── AppointmentController.php
│   │       └── DashboardController.php
│   └── Middleware/
│       ├── SanitizeInput.php
│       └── SecurityHeaders.php
├── Models/
│   ├── User.php
│   ├── Patient.php
│   ├── Product.php
│   ├── Sale.php
│   ├── Appointment.php
│   └── ...
└── Traits/
    └── FiltersByDoctor.php

database/
├── migrations/
└── seeders/

routes/
├── api.php
└── web.php

storage/
└── app/
    └── public/
        └── patients/
            └── {id}/
                ├── photos/
                ├── documents/
                └── signatures/
```

## 🌐 Despliegue en Producción

### Hostinger

Ver guía completa: [DEPLOY_HOSTINGER.md](../DEPLOY_HOSTINGER.md)

### Hostalia

Ver guía completa: [DEPLOY_HOSTALIA.md](../DEPLOY_HOSTALIA.md)

### Configuración General

1. **Variables de Entorno:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

SANCTUM_STATEFUL_DOMAINS=tudominio.com,app.tudominio.com
```

2. **Optimizar Aplicación:**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

3. **Permisos:**

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

## 🐛 Troubleshooting

### Error: "No application encryption key"

```bash
php artisan key:generate
```

### Error: Storage not linked

```bash
php artisan storage:link
```

### Error 500 - Internal Server Error

```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Error de conexión a base de datos

1. Verificar credenciales en `.env`
2. Verificar que el servicio MySQL esté corriendo
3. Crear la base de datos manualmente:

```sql
CREATE DATABASE crm_spa_medico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📞 Soporte

- **Documentación:** [Laravel 11 Docs](https://laravel.com/docs/11.x)
- **Issues:** [GitHub Issues](https://github.com/Emcas152/Backend/issues)
- **Seguridad:** Reportar vulnerabilidades a `security@tudominio.com`

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo `LICENSE` para más detalles.

## 🙏 Agradecimientos

- [Laravel](https://laravel.com) - Framework PHP
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API authentication
- [SimpleSoftwareIO/simple-qrcode](https://github.com/SimpleSoftwareIO/simple-qrcode) - Generación QR codes

---

**Desarrollado con ❤️ usando Laravel 11**
