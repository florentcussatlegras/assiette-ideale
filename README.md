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

### 1️⃣ Cloner le projet
```bash
git clone https://github.com/florentcussatlegras/assiette-ideale
cd assiette-ideale
```

### Installer les dépendances
```bash
composer install
npm install
```

### Lancer en développement
```bash
npm run dev
```

### Ouvrez http://localhost:3000 dans votre navigateur.

### Compiler pour la production
```bash
npm run build
npm start
```

---

## 🎯 Utilisation

- Saisissez une url dans le champs de saisie de la page d'accueil

- Accédez en détails aux résultats d'audit de l'url

- Suivez vos activités d'audit depuis votre page de profil

---

## 🌐 Démo en ligne

https://fc-nutrition.com

---

## ⚖️ Licence

Ce projet est open source (Licence MIT)
