# 🎮 Wiki Games

Wiki Games est une plateforme web pour répertorier et explorer des jeux vidéo, construite avec PHP 8, MySQL et un design cyberpunk dark.

## ✨ Fonctionnalités

- 🔐 **Authentification** — Inscription / Connexion sécurisée avec CSRF tokens et sessions protégées
- 📚 **Catalogue** — Grille de jeux avec recherche, filtres par genre et pagination
- 🎯 **Fiche jeu** — Page détail avec description, genre, plateforme, note, prix
- 🛡️ **Panel Admin** — Dashboard avec stats, CRUD complet (ajout/modification/suppression)
- 🖼️ **Upload d'images** — Téléchargement de fichiers ou URL externe
- 🎨 **Design dark gaming** — Style cyberpunk avec effets de néon, scanlines et animations

## 🔒 Sécurité

- Requêtes préparées PDO partout (0 injection SQL possible)
- Mots de passe hashés avec bcrypt (cost 12)
- Protection CSRF sur tous les formulaires POST
- Session regénérée à la connexion
- Cookies httpOnly + SameSite
- Credentials dans `.env` (hors du dépôt Git)
- Validation et sanitisation côté serveur
- Vérification MIME type pour les uploads

## 🚀 Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/ton-user/wiki-games.git
   cd wiki-games
   ```

2. **Configurer la base de données**
   - Importer `database.sql` dans MySQL/phpMyAdmin
   - Créer un fichier `.env` basé sur `.env.example`
   ```
   DB_HOST=localhost
   DB_NAME=wiki_games
   DB_USER=root
   DB_PASS=ton_mot_de_passe
   APP_URL=http://localhost/wiki-games
   APP_SECRET=une_chaine_aleatoire_de_32_caracteres
   ```

3. **Compte admin par défaut**
   - Username : `admin`
   - Password : `Admin@1234`
   - ⚠️ **Changez ce mot de passe immédiatement !**

4. **Droits d'upload**
   ```bash
   chmod 755 uploads/
   ```

## 📁 Structure

```
wiki-games/
├── admin/
│   ├── index.php     # Dashboard admin
│   ├── add.php       # Formulaire ajout jeu
│   └── edit.php      # Formulaire modification jeu
├── assets/
│   └── style.css     # CSS global (design cyberpunk)
├── config/
│   ├── config.php    # Constantes de l'app
│   └── database.php  # Connexion PDO
├── includes/
│   ├── auth.php      # Auth, CSRF, sanitisation
│   ├── header.php    # Header HTML partagé
│   └── footer.php    # Footer HTML partagé
├── pages/
│   └── game.php      # Page détail d'un jeu
├── process/
│   └── delete_game.php # Suppression sécurisée
├── uploads/          # Images uploadées
├── index.php         # Catalogue principal
├── login.php         # Connexion
├── register.php      # Inscription
├── logout.php        # Déconnexion
├── 404.php           # Page d'erreur
├── database.sql      # Schéma BDD + données exemple
├── .env.example      # Template de configuration
└── .htaccess         # Configuration Apache
```

## 🛠️ Stack technique

- **Backend** : PHP 8.0+ (PDO, sessions natives)
- **Base de données** : MySQL 5.7+ / MariaDB
- **Frontend** : HTML5, CSS3, JavaScript vanilla
- **Fonts** : Orbitron, Rajdhani, Exo 2 (Google Fonts)
- **Serveur** : Apache (XAMPP, WAMP, MAMP, ou hébergement web)

## 👤 Auteur

Divhthoth
