# Task Manager - Backend

## 📌 Descripción
Task Manager es una plataforma para la gestión de tareas con asignaciones, historial de cambios y autenticación segura. Este repositorio contiene el backend desarrollado en Laravel.

---

## 1️⃣ Instalación y Configuración

### ✅ Requisitos previos
- PHP 8.x
- Composer
- PostgreSQL
- Node.js (para WebSockets si usas Laravel Echo)

### ⚙️ Instalación
```bash
git clone https://github.com/usuario/task-manager.git
cd task-manager/backend
composer install
cp .env.example .env
php artisan key:generate
```

### ⚙️ Configuración
- Edita el archivo `.env` con las credenciales de tu base de datos PostgreSQL.

### 📦 Migraciones y Seeders
```bash
php artisan migrate --seed
```

### 🚀 Iniciar servidor
```bash
php artisan serve
```

---

## 2️⃣ Decisiones Técnicas y Tecnologías Utilizadas

### 🔹 Backend
- **Laravel 10** – Framework PHP robusto.
- **PostgreSQL** – Base de datos optimizada para consultas avanzadas.
- **Sanctum** – Autenticación segura con tokens.
- **WebSockets con Laravel Echo** – Comunicación en tiempo real.
- **Eloquent & Query Builder** – Consultas eficientes en la base de datos.

---

## 3️⃣ Endpoints del API

### 🔹 Autenticación
| Método | Endpoint        | Descripción                    |
|--------|------------------|--------------------------------|
| POST   | /api/login       | Inicia sesión y retorna token |
| POST   | /api/logout      | Cierra sesión del usuario     |
| POST   | /api/register    | Registra un nuevo usuario     |

### 🔹 Gestión de Usuarios
| Método | Endpoint        | Descripción                         |
|--------|------------------|-------------------------------------|
| GET    | /api/users       | Lista todos los usuarios           |
| GET    | /api/me          | Obtiene perfil del usuario autenticado |

### 🔹 Gestión de Tareas
| Método | Endpoint                  | Descripción                    |
|--------|----------------------------|--------------------------------|
| GET    | /api/tasks                 | Obtiene todas las tareas      |
| POST   | /api/tasks                | Crea una nueva tarea          |
| PUT    | /api/tasks/{id}          | Edita una tarea existente     |
| DELETE | /api/tasks/{id}          | Elimina una tarea             |
| POST   | /api/tasks/{id}/assign   | Asigna múltiples usuarios     |

### 🔹 Historial de Tareas
| Método | Endpoint            | Descripción                       |
|--------|----------------------|-----------------------------------|
| GET    | /api/task-history    | Obtiene el historial de tareas   |

---

## 📌 Contacto y Contribuciones

Si deseas colaborar, reportar errores o mejorar funcionalidades, abre un issue o envía un pull request en el repositorio.

---
