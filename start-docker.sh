#!/bin/bash

echo "🚀 Démarrage de l'application Blog Personnel..."

# Arrêter les conteneurs existants
echo "📦 Arrêt des conteneurs existants..."
docker-compose down

# Construire les images
echo "🔨 Construction des images Docker..."
docker-compose build

# Démarrer les services
echo "🌟 Démarrage des services..."
docker-compose up -d

# Attendre que PostgreSQL soit prêt
echo "⏳ Attente que PostgreSQL soit prêt..."
sleep 10

# Exécuter les migrations Laravel
echo "🗄️ Exécution des migrations..."
docker-compose exec laravel php artisan migrate --force

# Générer la clé d'application
echo "🔑 Génération de la clé d'application..."
docker-compose exec laravel php artisan key:generate

# Installer les dépendances Passport
echo "🔐 Configuration de Passport..."
docker-compose exec laravel php artisan passport:install

# Nettoyer le cache
echo "🧹 Nettoyage du cache..."
docker-compose exec laravel php artisan config:clear
docker-compose exec laravel php artisan cache:clear

echo "✅ Application démarrée avec succès!"
echo ""
echo "📱 Services disponibles:"
echo "   - Laravel API: http://localhost:8000"
echo "   - pgAdmin: http://localhost:8080"
echo "   - PostgreSQL: localhost:5432"
echo ""
echo "🔑 Identifiants pgAdmin:"
echo "   - Email: admin@blog.com"
echo "   - Mot de passe: admin"
echo ""
echo "📋 Pour voir les logs: docker-compose logs -f"
echo "🛑 Pour arrêter: docker-compose down" 