# 🐳 Guide Docker - Blog Personnel

## 📋 Prérequis

- Docker Desktop installé
- Docker Compose installé

## 🚀 Démarrage rapide

### Option 1: Script automatique (recommandé)
```bash
# Sur Windows PowerShell
.\start-docker.sh

# Sur Linux/Mac
./start-docker.sh
```

### Option 2: Commandes manuelles
```bash
# Construire et démarrer les services
docker-compose up -d --build

# Exécuter les migrations
docker-compose exec laravel php artisan migrate --force

# Générer la clé d'application
docker-compose exec laravel php artisan key:generate

# Installer Passport
docker-compose exec laravel php artisan passport:install
```

## 🌐 Services disponibles

| Service | URL | Description |
|---------|-----|-------------|
| **Laravel API** | http://localhost:8000 | Backend de l'application |
| **pgAdmin** | http://localhost:8080 | Interface d'administration PostgreSQL |
| **PostgreSQL** | localhost:5432 | Base de données |

## 🔑 Identifiants par défaut

### pgAdmin
- **Email**: admin@blog.com
- **Mot de passe**: admin

### Base de données PostgreSQL
- **Base de données**: blog_personnel
- **Utilisateur**: blog_user
- **Mot de passe**: blog_password
- **Port**: 5432

## 📝 Commandes utiles

### Voir les logs
```bash
# Tous les services
docker-compose logs -f

# Service spécifique
docker-compose logs -f laravel
docker-compose logs -f postgres
```

### Accéder au conteneur Laravel
```bash
docker-compose exec laravel bash
```

### Exécuter des commandes Laravel
```bash
# Migrations
docker-compose exec laravel php artisan migrate

# Seeders
docker-compose exec laravel php artisan db:seed

# Cache
docker-compose exec laravel php artisan cache:clear
docker-compose exec laravel php artisan config:clear

# Passport
docker-compose exec laravel php artisan passport:install
```

### Arrêter l'application
```bash
docker-compose down
```

### Arrêter et supprimer les volumes
```bash
docker-compose down -v
```

## 🔧 Configuration

### Variables d'environnement
Les variables d'environnement sont configurées dans le `docker-compose.yml` :

```yaml
environment:
  DB_CONNECTION: pgsql
  DB_HOST: postgres
  DB_PORT: 5432
  DB_DATABASE: blog_personnel
  DB_USERNAME: blog_user
  DB_PASSWORD: blog_password
  APP_ENV: local
  APP_DEBUG: true
```

### Volumes
- `./back-end:/var/www/html` : Code source Laravel
- `postgres_data:/var/lib/postgresql/data` : Données PostgreSQL

## 🐛 Dépannage

### Problème de permissions
```bash
docker-compose exec laravel chown -R www-data:www-data /var/www/html/storage
```

### Redémarrer un service
```bash
docker-compose restart laravel
```

### Reconstruire un service
```bash
docker-compose up -d --build laravel
```

### Vérifier l'état des services
```bash
docker-compose ps
```

## 📁 Structure des fichiers

```
projet_blogs/
├── docker-compose.yml          # Configuration Docker
├── start-docker.sh            # Script de démarrage
├── back-end/
│   ├── Dockerfile             # Image Laravel
│   └── .dockerignore          # Fichiers exclus
└── front-end/                 # Frontend (optionnel)
```

## 🆘 Support

Si vous rencontrez des problèmes :

1. Vérifiez que Docker Desktop est démarré
2. Vérifiez les logs : `docker-compose logs`
3. Redémarrez les services : `docker-compose restart`
4. Reconstruisez les images : `docker-compose up -d --build` 