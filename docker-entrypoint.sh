#!/bin/bash
set -e

echo "🚀 Démarrage de l'application Symfony..."

echo "📦 Construction des assets TailwindCSS..."
php bin/console tailwind:build --minify || echo "⚠️  Attention: Impossible de construire TailwindCSS"

echo "🗂️  Compilation des asset mappings..."
php bin/console asset-map:compile || echo "⚠️  Attention: Impossible de compiler les asset mappings"

echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/var || true
chmod -R 755 /var/www/html/var || true

echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ Application prête ! Démarrage d'Apache..."

exec apache2-foreground 