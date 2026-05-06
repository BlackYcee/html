# Galería de Imágenes

Sistema CRUD de galería con soporte para S3 y RDS (MySQL).

## Requisitos

- PHP 8.0+
- MySQL 5.7+
- Composer

## Instalación

1. **Instalar dependencias:**
```bash
composer install
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
# Editar .env con tus credenciales
```

3. **Crear base de datos:**
```bash
mysql -u root -p < database.sql
```

O inicializar desde el navegador:
```
http://tu-servidor/index.php?action=initDb
```

4. **Permisos para uploads:**
```bash
chmod 755 uploads/
```

## Configuración (.env)

```env
# APP
APP_ENV=development
DEBUG_MODE=true

# RDS (MySQL)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gallery_db
DB_USER=root
DB_PASSWORD=tu_password

# S3 (opcional - dejar vacío para modo local)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_REGION=us-east-1
AWS_S3_BUCKET=
AWS_S3_FOLDER=imagenes

# Upload local
UPLOAD_DIR=./uploads/
MAX_FILE_SIZE=5242880
```

## Endpoints

| Acción | URL | Método | Descripción |
|--------|-----|--------|-------------|
| Galería | `/` | GET | Página principal |
| Listar | `?action=list` | GET | JSON con todas las imágenes |
| Subir | `?action=upload` | POST | Upload de imagen (multipart) |
| Editar | `?action=update` | POST | Actualizar título/descripción |
| Eliminar | `?action=delete` | POST | Eliminar imagen |
| Status | `?action=status` | GET | Estado de conexiones |
| Init DB | `?action=initDb` | GET | Crear tabla |

## AWS Integration

Para activar S3:
1. Completar credenciales en `.env`
2. Crear bucket en S3
3. Carpetas: `imagenes/`
4. Las imágenes se subirán a S3 automáticamente

## Deployment

1. Instalar en instancia EC2
2. Verificar funcionalidad
3. Crear AMI
4. Usar AMI en Launch Template para Auto Scaling