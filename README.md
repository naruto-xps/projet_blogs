# Blog Personnel - ISEP_P5

## Description du projet

Ce projet consiste à créer une application de blog personnel en utilisant le modèle MVC. Le backend est développé avec Laravel et le frontend sera développé avec React.

## Fonctionnalités principales

### 1. Authentification avec OTP
- Inscription avec nom complet, nom d'utilisateur, numéro de téléphone et mot de passe
- Validation OTP (code "1111" en développement, aléatoire en production)
- Connexion avec nom d'utilisateur et mot de passe

### 2. Gestion des articles
- Création, modification, suppression d'articles
- Articles privés/publics
- Publication programmée
- Gestion des commentaires

### 3. Gestion des amis
- Recherche d'utilisateurs par nom d'utilisateur ou numéro de téléphone
- Envoi de demandes d'amitié
- Acceptation/rejet des demandes
- Blocage d'utilisateurs

### 4. Fil d'actualité
- Affichage des articles publics des amis
- Gestion de la visibilité

## Structure du projet

```
projet_blogs/
├── back-end/                 # API Laravel
│   ├── app/
│   │   ├── Http/Controllers/ # Contrôleurs API
│   │   ├── Models/           # Modèles Eloquent
│   │   └── swagger/          # Documentation Swagger
│   ├── database/
│   │   └── migrations/       # Migrations de base de données
│   └── routes/
│       └── api.php           # Routes API
├── docker-compose.yml        # Configuration Docker
└── front-end/                # Application React (à développer)
```

## Installation et configuration

### Prérequis
- PHP 8.2+
- Composer
- Docker et Docker Compose

### Backend (Laravel)

1. **Cloner le projet**
```bash
git clone <repository-url>
cd projet_blogs
```

2. **Démarrer la base de données avec Docker**
```bash
docker-compose up -d
```

3. **Installer les dépendances**
```bash
cd back-end
composer install
```

4. **Configuration de l'environnement**
```bash
cp env.example .env
php artisan key:generate
```

5. **Exécuter les migrations**
```bash
php artisan migrate
```

6. **Démarrer le serveur**
```bash
php artisan serve
```

Le serveur sera accessible sur `http://localhost:8000`
La base de données sera accessible sur `http://localhost:8080` (pgAdmin)

## API Documentation

### Authentification

#### Inscription
```http
POST /api/auth/register
Content-Type: application/json

{
    "full_name": "John Doe",
    "username": "johndoe",
    "phone_number": "+221701234567",
    "password": "password123"
}
```

#### Envoi d'OTP
```http
POST /api/auth/send-otp
Content-Type: application/json

{
    "phone_number": "+221701234567"
}
```

#### Vérification OTP
```http
POST /api/auth/verify-otp
Content-Type: application/json

{
    "phone_number": "+221701234567",
    "code": "1111"
}
```

#### Connexion
```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "johndoe",
    "password": "password123"
}
```

### Articles

#### Récupérer le fil d'actualité
```http
GET /api/articles
Authorization: Bearer {token}
```

#### Créer un article
```http
POST /api/articles
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Mon premier article",
    "content": "Contenu de l'article...",
    "is_public": true,
    "allow_comments": true,
    "published_at": "2024-01-01T12:00:00Z"
}
```

#### Modifier un article
```http
PUT /api/articles/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Titre modifié",
    "content": "Contenu modifié",
    "is_public": false
}
```

#### Supprimer un article
```http
DELETE /api/articles/{id}
Authorization: Bearer {token}
```

### Commentaires

#### Ajouter un commentaire
```http
POST /api/articles/{article_id}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Excellent article !"
}
```

#### Supprimer un commentaire
```http
DELETE /api/comments/{id}
Authorization: Bearer {token}
```

### Gestion des amis

#### Rechercher des utilisateurs
```http
GET /api/users/search?query=john
Authorization: Bearer {token}
```

#### Envoyer une demande d'amitié
```http
POST /api/friends/request
Authorization: Bearer {token}
Content-Type: application/json

{
    "friend_id": 2
}
```

#### Accepter une demande d'amitié
```http
POST /api/friends/accept
Authorization: Bearer {token}
Content-Type: application/json

{
    "friend_id": 2
}
```

#### Rejeter une demande d'amitié
```http
POST /api/friends/reject
Authorization: Bearer {token}
Content-Type: application/json

{
    "friend_id": 2
}
```

#### Supprimer un ami
```http
DELETE /api/friends/{friend_id}
Authorization: Bearer {token}
```

#### Bloquer un utilisateur
```http
POST /api/friends/block
Authorization: Bearer {token}
Content-Type: application/json

{
    "friend_id": 2
}
```

## Base de données

### Tables principales

1. **users** - Utilisateurs
   - id, full_name, username, phone_number, phone_verified_at, password, timestamps

2. **articles** - Articles
   - id, user_id, title, content, is_public, allow_comments, published_at, timestamps

3. **comments** - Commentaires
   - id, user_id, article_id, content, timestamps

4. **friendships** - Relations d'amitié
   - id, user_id, friend_id, status (pending/accepted/blocked), timestamps

5. **otp_codes** - Codes OTP
   - id, phone_number, code, expires_at, used, timestamps

## Développement

### Code OTP en développement
En mode développement, le code OTP est toujours "1111" pour faciliter les tests.

### Variables d'environnement importantes
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blog_personnel
DB_USERNAME=blog_user
DB_PASSWORD=blog_password
```

## Tests

Pour exécuter les tests :
```bash
php artisan test
```

## Documentation Swagger

La documentation de l'API est disponible via Swagger à l'adresse :
`http://localhost:8000/api/documentation`

## Prochaines étapes

1. Développement du frontend React
2. Tests unitaires et d'intégration
3. Déploiement en production
4. Optimisations de performance

## Technologies utilisées

- **Backend**: Laravel 12, PHP 8.2+
- **Base de données**: PostgreSQL 15 (Docker)
- **Authentification**: Laravel Sanctum
- **Documentation API**: Swagger/OpenAPI
- **Frontend**: React (à développer)
- **UI Framework**: Material-UI (à implémenter)
- **Conteneurisation**: Docker & Docker Compose

## Auteur

Projet développé dans le cadre du cours ISEP_P5. 