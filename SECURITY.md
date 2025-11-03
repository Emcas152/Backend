# 🔐 Medidas de Seguridad Implementadas - CRM Spa Médico

Este documento describe todas las medidas de seguridad implementadas en el backend para proteger contra XSS, inyección SQL, CSRF y otros ataques comunes.

## 📋 Índice

1. [Protección XSS (Cross-Site Scripting)](#protección-xss)
2. [Protección SQL Injection](#protección-sql-injection)
3. [Validación de Entrada](#validación-de-entrada)
4. [Headers de Seguridad](#headers-de-seguridad)
5. [Autenticación y Autorización](#autenticación-y-autorización)
6. [Sanitización de Datos](#sanitización-de-datos)
7. [Protección CSRF](#protección-csrf)
8. [Subida de Archivos Segura](#subida-de-archivos-segura)

---

## 🛡️ Protección XSS

### Middleware de Sanitización (`SanitizeInput`)

**Ubicación:** `app/Http/Middleware/SanitizeInput.php`

**Función:** Sanitiza automáticamente todas las entradas del usuario antes de procesarlas.

**Características:**
- ✅ Convierte caracteres especiales a entidades HTML
- ✅ Elimina scripts (`<script>`, `<iframe>`)
- ✅ Elimina eventos JavaScript inline (`onclick`, `onload`, etc.)
- ✅ Elimina protocolos peligrosos (`javascript:`, `vbscript:`)
- ✅ Elimina todas las etiquetas HTML con `strip_tags()`
- ✅ Excluye campos sensibles como contraseñas

```php
// Campos excluidos de sanitización
protected array $except = [
    'password',
    'password_confirmation',
    'current_password',
];
```

**Aplicación:** Se aplica globalmente a todas las rutas API en `bootstrap/app.php`:

```php
$middleware->appendToGroup('api', [
    \App\Http\Middleware\SanitizeInput::class,
]);
```

---

## 🔒 Protección SQL Injection

Laravel utiliza **Eloquent ORM** y **Query Builder** que automáticamente protegen contra inyección SQL mediante:

### Prepared Statements

Todas las consultas usan prepared statements:

```php
// ✅ SEGURO - Laravel usa prepared statements
User::where('email', $request->email)->first();

// ❌ INSEGURO - NO usar
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### Validación de IDs

Todos los IDs se validan con reglas `exists`:

```php
'patient_id' => 'required|exists:patients,id',
'staff_member_id' => 'nullable|exists:staff_members,id',
```

---

## ✅ Validación de Entrada

### Reglas Estrictas en Todos los Controladores

#### AuthController

**Login:**
```php
'email' => 'required|email:rfc,dns|max:255',
'password' => 'required|string|min:6|max:255',
```

**Registro de Paciente:**
```php
'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
'email' => 'required|email:rfc,dns|max:255|unique:users,email',
'password' => 'required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
'birthday' => 'nullable|date|before:today',
```

**Requisitos de contraseña:**
- Mínimo 8 caracteres
- Al menos 1 minúscula
- Al menos 1 mayúscula
- Al menos 1 número

#### PatientController

**Crear/Actualizar Paciente:**
```php
'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
'email' => 'required|email:rfc,dns|unique:patients,email|max:255',
'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
'address' => 'nullable|string|max:500',
```

**Subida de Fotos:**
```php
'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB
'type' => 'required|in:before,after,other',
'notes' => 'nullable|string|max:1000',
```

**Subida de Documentos:**
```php
'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB
'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
'type' => 'required|in:consent,contract,prescription,lab_result,other',
```

**Firma de Documentos:**
```php
'signature' => 'required|file|image|mimes:png,jpg,jpeg|max:2048',
```

**Puntos de Lealtad:**
```php
'points' => 'required|integer|min:1|max:10000',
```

#### ProductController

**Crear/Actualizar Producto:**
```php
'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
'sku' => 'nullable|string|max:100|regex:/^[A-Z0-9\-]+$/|unique:products,sku',
'description' => 'nullable|string|max:2000',
'price' => 'required|numeric|min:0|max:999999.99',
'stock' => 'nullable|integer|min:0|max:999999',
'low_stock_alert' => 'nullable|integer|min:0|max:9999',
'type' => 'required|in:product,service',
```

**Ajustar Stock:**
```php
'quantity' => 'required|integer|min:-999999|max:999999',
'type' => 'required|in:add,subtract,set',
```

Validación de límites:
- ✅ No permite stock negativo en operación `subtract`
- ✅ No permite exceder 999,999 unidades
- ✅ Verifica que no se intente ajustar stock de servicios

#### AppointmentController

**Crear/Actualizar Cita:**
```php
'patient_id' => 'required|exists:patients,id',
'staff_member_id' => 'nullable|exists:staff_members,id',
'appointment_date' => 'required|date|after_or_equal:today',
'appointment_time' => 'required|date_format:H:i:s',
'service' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled',
'notes' => 'nullable|string|max:2000',
```

**Validación de Conflictos:**
```php
// Verifica que no haya citas duplicadas en el mismo horario
$existingAppointment = Appointment::where('staff_member_id', $staffMemberId)
    ->where('appointment_date', $date)
    ->where('appointment_time', $time)
    ->where('status', '!=', 'cancelled')
    ->first();
```

---

## 🔐 Headers de Seguridad

### Middleware de Headers (`SecurityHeaders`)

**Ubicación:** `app/Http/Middleware/SecurityHeaders.php`

**Headers Implementados:**

```php
// Protección XSS del navegador
'X-XSS-Protection' => '1; mode=block'

// Prevenir MIME sniffing
'X-Content-Type-Options' => 'nosniff'

// Prevenir clickjacking
'X-Frame-Options' => 'SAMEORIGIN'

// Content Security Policy
'Content-Security-Policy' => 
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
    "style-src 'self' 'unsafe-inline'; " .
    "img-src 'self' data: https:; " .
    "font-src 'self' data:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'self'"

// HSTS (solo si HTTPS está habilitado)
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'

// Referrer Policy
'Referrer-Policy' => 'strict-origin-when-cross-origin'

// Permissions Policy
'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
```

**Aplicación:** Se aplica globalmente a todas las respuestas en `bootstrap/app.php`:

```php
$middleware->append(\App\Http\Middleware\SecurityHeaders::class);
```

---

## 🔑 Autenticación y Autorización

### Laravel Sanctum

**Token-based authentication:**
- ✅ Tokens seguros generados por Sanctum
- ✅ Tokens almacenados con hash en BD
- ✅ Expiración automática de tokens
- ✅ Revocación manual con `logout()`

### Roles y Permisos

**Roles disponibles:**
- `admin` - Acceso completo
- `doctor` - Acceso filtrado a sus pacientes
- `staff` - Acceso completo a gestión
- `patient` - Solo sus propios datos

**Middleware de Roles:**

En `routes/api.php`:
```php
Route::middleware('auth:sanctum')->group(function () {
    // Rutas protegidas por autenticación
});
```

**Verificación de roles en controladores:**
```php
// Solo admin puede crear staff
if (!$request->user()->isAdmin()) {
    return response()->json(['message' => 'No autorizado'], 403);
}
```

### Filtrado por Doctor

**Trait FiltersByDoctor:**

Automáticamente filtra datos para que los doctores solo vean pacientes asignados:

```php
use App\Traits\FiltersByDoctor;

// En el modelo
class Appointment extends Model
{
    use FiltersByDoctor;
}

// En el controlador
$appointments = Appointment::filterByDoctor($user)->get();
```

---

## 🧹 Sanitización de Datos

### Mutators en Modelos

Todos los modelos implementan mutators (Attribute casts) para sanitizar datos antes de guardar en BD.

#### User Model

```php
protected function name(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value,
        set: fn ($value) => strip_tags(trim($value)),
    );
}

protected function email(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value,
        set: fn ($value) => strtolower(strip_tags(trim($value))),
    );
}
```

#### Patient Model

```php
protected function name(): Attribute
{
    return Attribute::make(
        set: fn ($value) => strip_tags(trim($value)),
    );
}

protected function phone(): Attribute
{
    return Attribute::make(
        set: fn ($value) => $value ? strip_tags(trim($value)) : null,
    );
}

protected function address(): Attribute
{
    return Attribute::make(
        set: fn ($value) => $value ? strip_tags(trim($value)) : null,
    );
}
```

#### Product Model

```php
protected function name(): Attribute
{
    return Attribute::make(
        set: fn ($value) => strip_tags(trim($value)),
    );
}

protected function sku(): Attribute
{
    return Attribute::make(
        set: fn ($value) => $value ? strtoupper(strip_tags(trim($value))) : null,
    );
}

protected function description(): Attribute
{
    return Attribute::make(
        set: fn ($value) => $value ? strip_tags(trim($value)) : null,
    );
}
```

#### Appointment Model

```php
protected function service(): Attribute
{
    return Attribute::make(
        set: fn ($value) => strip_tags(trim($value)),
    );
}

protected function notes(): Attribute
{
    return Attribute::make(
        set: fn ($value) => $value ? strip_tags(trim($value)) : null,
    );
}
```

---

## 🛡️ Protección CSRF

Laravel incluye protección CSRF automática para:

### Rutas Web

```php
// CSRF token automático en formularios
@csrf
```

### API con Sanctum

```php
// Sanctum valida dominios autorizados
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
```

En `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,tudominio.com,app.tudominio.com
```

---

## 📁 Subida de Archivos Segura

### Validación de MIME Types

**Fotos de Pacientes:**
```php
'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120' // 5MB
```

**Documentos:**
```php
'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240' // 10MB
```

**Firmas:**
```php
'signature' => 'required|file|image|mimes:png,jpg,jpeg|max:2048' // 2MB
```

### Almacenamiento Seguro

**Ubicación:** `/storage/app/public/`

Estructura:
```
storage/app/public/
├── patients/
│   ├── {patient_id}/
│   │   ├── photos/
│   │   ├── documents/
│   │   └── signatures/
```

**Configuración en `config/filesystems.php`:**

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Nombres de Archivo Únicos

Laravel genera automáticamente nombres únicos con hash:

```php
$path = $request->file('photo')->store("patients/{$id}/photos", 'public');
// Resultado: patients/123/photos/aBcDeF123456.jpg
```

---

## 📊 Resumen de Seguridad

### ✅ Protecciones Implementadas

| Amenaza | Protección | Estado |
|---------|------------|--------|
| XSS | Middleware SanitizeInput + Mutators | ✅ Completo |
| SQL Injection | Eloquent ORM + Prepared Statements | ✅ Completo |
| CSRF | Sanctum Stateful Domains | ✅ Completo |
| Clickjacking | X-Frame-Options | ✅ Completo |
| MIME Sniffing | X-Content-Type-Options | ✅ Completo |
| MITM | HSTS (HTTPS) | ✅ Completo |
| File Upload | MIME validation + Size limits | ✅ Completo |
| Mass Assignment | $fillable en modelos | ✅ Completo |
| Brute Force | Rate Limiting (Sanctum) | ✅ Completo |
| Session Hijacking | Sanctum Tokens | ✅ Completo |

### 🔐 Niveles de Protección

**Nivel 1 - Entrada (Request):**
1. Middleware `SanitizeInput` sanitiza todo input
2. Validación estricta con Laravel Validator
3. Regex patterns para formatos específicos

**Nivel 2 - Procesamiento:**
1. Eloquent ORM previene SQL injection
2. Verificación de roles y permisos
3. Filtrado por doctor (trait FiltersByDoctor)

**Nivel 3 - Persistencia (Database):**
1. Mutators sanitizan antes de guardar
2. Casts automáticos (integer, boolean, date)
3. Timestamps automáticos

**Nivel 4 - Salida (Response):**
1. Headers de seguridad en todas las respuestas
2. JSON encoding automático (previene XSS)
3. CORS configurado correctamente

---

## 🚨 Recomendaciones Adicionales

### En Producción

1. **Habilitar HTTPS:**
   ```env
   APP_URL=https://tudominio.com
   SANCTUM_STATEFUL_DOMAINS=tudominio.com,app.tudominio.com
   ```

2. **Configurar APP_DEBUG:**
   ```env
   APP_DEBUG=false
   APP_ENV=production
   ```

3. **Rate Limiting:**
   Laravel incluye rate limiting por defecto:
   ```php
   // 60 requests por minuto
   Route::middleware('throttle:60,1')->group(function () {
       // ...
   });
   ```

4. **Backup Regular:**
   - Base de datos: diario
   - Archivos: semanal
   - Código: Git

5. **Monitoreo de Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

6. **Actualizar Dependencias:**
   ```bash
   composer update
   ```

7. **Firewall de Aplicación (WAF):**
   - ModSecurity en servidor
   - Cloudflare (opcional)

---

## 📞 Contacto de Seguridad

Si encuentras una vulnerabilidad de seguridad, por favor repórtala a:

**Email:** security@tudominio.com

**No reportar vulnerabilidades públicamente** hasta que sean corregidas.

---

## 📝 Changelog de Seguridad

### v1.0.0 (Noviembre 2025)

- ✅ Implementación inicial de seguridad XSS
- ✅ Middleware SanitizeInput
- ✅ Middleware SecurityHeaders
- ✅ Validaciones estrictas en todos los controladores
- ✅ Mutators en todos los modelos
- ✅ Protección de subida de archivos
- ✅ Filtrado por roles (doctor)
- ✅ Documentación completa

---

**Última actualización:** 3 de Noviembre, 2025  
**Versión del documento:** 1.0.0
