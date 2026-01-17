# Assiette idéale

## Assiette idéale ## lateforme web dédiée à la nutrition et au suivi de plats, développée avec **Symfony** et un stack **fullstack moderne**, pensée pour être maintenable, évolutive et facilement déployable.

🔗 Site en production : https://fc-nutrition.com

---

## 🚀 Objectifs du projet

- Proposer une interface claire pour consulter et gérer des plats nutritionnels
- Mettre en place une architecture **fullstack moderne**
- Utiliser **Docker** pour un environnement de développement reproductible
- Appliquer de bonnes pratiques Symfony (services, bundles, migrations, UX)

---

## 🧱 Stack technique

### Backend
- PHP 8.2
- Symfony 6
- Doctrine ORM
- MySQL
- Redis
- Twig

### Frontend
- Symfony UX
- Stimulus
- Turbo
- Webpack Encore
- Tailwind CSS
- JavaScript (ES6+)

### DevOps / Environnement
- Docker & Docker Compose

---

## 🛠️ Installation (DEV avec Docker)

### Prérequis
- Docker + Docker Compose
- Git

### Cloner le projet
```bash
git clone https://github.com/florentcussatlegras/assiette-ideale
cd assiette-ideale
```

### Installer les dépendances PHP
Assurez-vous d’avoir PHP et Composer installés, puis :
```bash
composer install
```

### Configuration de l’environnement
Copiez le fichier d’exemple et adaptez-le si nécessaire (base de données, etc.) :
```bash
cp .env .env.local
```
Modifiez ensuite .env.local selon votre configuration (DATABASE_URL, APP_ENV, etc.).

### Installer les dépendances front (si Webpack Encore est utilisé)
```bash
npm install
```

### Lancer le serveur en développement
Avec le serveur Symfony (recommandé)
```bash
symfony serve
```

### Compiler les assets en mode développement
```bash
npm run dev
```

### Ouvrez l’application dans votre navigateur
http://localhost:8000

### Compiler pour la production
Compiler les assets front
```bash
npm run build
```
Préparer Symfony pour la production
```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console assets:install
```

---

## 🌐 Démo en ligne

https://fc-nutrition.com

---

## ⚖️ Licence

Ce projet est open source (Licence MIT)
