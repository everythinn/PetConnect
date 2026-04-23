# PetConnect - Backend API

Une application web de simulation d'animaux de compagnie virtuels développée avec **Symfony 7.4**, **PostgreSQL**, et **JWT Authentication**.

## 🎯 Objectifs réalisés

✅ **Phase 1** : Configuration du projet Symfony  
✅ **Phase 2** : Entités Doctrine et schéma de base de données  
✅ **Phase 3** : Authentification JWT (register/login)  
✅ **Phase 4** : API REST complète (Pet/Care/Delegation management)  

---

## 📦 Installation & Configuration

### Prérequis
- PHP 8.2+
- Composer
- PostgreSQL 15+
- Git

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   cd /path/to/project
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   
   Éditer `.env` et configurer `DATABASE_URL` :
   ```
   DATABASE_URL="postgresql://user:password@127.0.0.1:5432/petconnect?serverVersion=15&charset=utf8"
   ```

4. **Créer la base de données et exécuter les migrations**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Générer les clés JWT** (déjà faites, mais au besoin)
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

6. **Démarrer le serveur Symfony**
   ```bash
   php -S localhost:8000 -t public/
   ```
   Ou avec le CLI Symfony :
   ```bash
   symfony server:start
   ```

---

## 🔐 Authentification (JWT)

### POST `/api/auth/register`

Créer un nouveau compte utilisateur.

**Payload** :
```json
{
  "email": "user@example.com",
  "username": "username",
  "password": "password123",
  "confirmPassword": "password123"
}
```

**Réponse (201)** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "userId": 1,
  "email": "user@example.com",
  "username": "username"
}
```

### POST `/api/auth/login`

Se connecter et obtenir un JWT token.

**Payload** :
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse (200)** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "userId": 1,
  "email": "user@example.com",
  "username": "username"
}
```

---

## 🐾 API Endpoints

Tous les endpoints API (sauf `/api/auth/register` et `/api/auth/login`) requièrent un JWT token dans le header :
```
Authorization: Bearer {token}
```

### Gestion des Animaux (Pets)

#### POST `/api/pets` - Adopter un animal
```json
{
  "name": "Max",
  "species": "dog"  // dog, cat, rabbit, hamster
}
```

#### GET `/api/pets` - Lister mes animaux
Retourne la liste de tous les animaux de l'utilisateur.

#### GET `/api/pets/{id}` - Détails d'un animal
Affiche les stats d'un animal : hunger, happiness, health, energy, xp, level, etc.

#### DELETE `/api/pets/{id}` - Supprimer un animal

---

### Soins (Care Actions)

#### POST `/api/pets/{id}/feed` - Nourrir l'animal
Réduit la faim, augmente XP.

#### POST `/api/pets/{id}/play` - Jouer avec l'animal
Augmente le bonheur, réduit l'énergie et la faim, augmente XP.

#### POST `/api/pets/{id}/heal` - Soigner l'animal
Augmente la santé, augmente XP.

#### POST `/api/pets/{id}/sleep` - Laisser dormir l'animal
Augmente l'énergie, augmente XP.

#### POST `/api/pets/{id}/bathe` - Laver l'animal
Augmente la santé et le bonheur, réduit l'énergie, augmente XP.

---

### Délégation (Delegation)

#### POST `/api/delegations` - Créer une délégation (propriétaire)
Transférer temporairement un animal à un autre utilisateur.

**Payload** :
```json
{
  "petId": 1,
  "caretakerId": 2,
  "startDate": "2026-04-02 10:00:00",
  "endDate": "2026-04-09 10:00:00"
}
```

#### GET `/api/delegations` - Lister mes délégations
Liste les délégations où l'utilisateur est propriétaire ou soigneur.

#### PATCH `/api/delegations/{id}/accept` - Accepter une délégation (soigneur)
Accepter une délégation en attente et devenir soigneur.

#### PATCH `/api/delegations/{id}/revoke` - Révoquer une délégation (propriétaire)
Annuler une délégation active.

---

## 🏗️ Architecture

### Structure des fichiers

```
src/
├── Controller/
│   ├── AuthController.php          # Endpoints d'authentification
│   ├── PetController.php           # Gestion des animaux (CRUD)
│   ├── CareController.php          # Soins des animaux
│   └── DelegationController.php    # Gestion des délégations
├── Entity/
│   ├── User.php
│   ├── Pet.php
│   ├── Item.php
│   ├── CareAction.php
│   ├── Delegation.php
│   └── Inventory.php
├── Enum/
│   ├── SpeciesEnum.php             # DOG, CAT, RABBIT, HAMSTER
│   ├── ActionTypeEnum.php          # FEED, PLAY, HEAL, SLEEP, BATHE
│   ├── ItemTypeEnum.php            # FOOD, TOY, MEDICINE, COSMETIC
│   ├── ItemEffectEnum.php          # HUNGER, HAPPINESS, HEALTH, ENERGY
│   └── DelegationStatusEnum.php    # PENDING, ACTIVE, EXPIRED, REVOKED
├── Service/
│   ├── PetService.php              # Logique metier des animaux
│   ├── CareActionService.php       # Logging des actions
│   ├── InventoryService.php        # Gestion de l'inventaire
│   └── DelegationService.php       # Logique des délégations
├── Repository/
│   └── (Auto-generated repositories)
├── DTO/
│   ├── RegisterDTO.php
│   ├── LoginDTO.php
│   ├── AuthResponseDTO.php
│   ├── AdoptPetDTO.php
│   ├── PetResponseDTO.php
│   ├── CareActionDTO.php
│   └── DelegationRequestDTO.php
├── Security/
│   ├── JwtAuthenticator.php        # Authentification JWT
│   └── Voter/
│       ├── PetVoter.php            # Contrôle d'accès aux Pets (ownership + delegations)
│       └── DelegationVoter.php     # Contrôle d'accès aux Délégations
└── Kernel.php

config/
├── packages/
│   ├── security.yaml               # Configuration sécurité JWT
│   ├── doctrine.yaml               # Configuration ORM
│   └── lexik_jwt_authentication.yaml # Configuration JWT
└── routes.yaml                       # Routes (auto-découverte par attributs)

migrations/
└── Version20260402000001.php       # Migration initiale (créer toutes les tables)

tests/
├── Unit/                           # Tests unitaires
└── Functional/                     # Tests fonctionnels (APIs)
```

---

## 🗄️ Base de Données

Les 6 tables principales :

| Table | Rôle |
|-------|------|
| `user` | Utilisateurs (email, password, roles, createdAt) |
| `pet` | Animaux virtuels (owner_id, stats, health, born_at, is_alive) |
| `item` | Objets shop (name, type, effect, effectValue) |
| `care_action` | Historique des actions (pet_id, performer_id, actionType, xpEarned) |
| `delegation` | Délégations temporaires (pet_id, owner_id, caretaker_id, status, dates) |
| `inventory` | Inventaire utilisateur (user_id, items JSON) |

---

## 🔒 Sécurité

### Authentification
- **Hachage des mots de passe** : bcrypt
- **JWT Token** : RS256 (RSA avec clés privée/publique en `/config/jwt/`)
- **Rôles** : ROLE_USER obligatoire pour accéder à l'API

### Autorisation (RBAC + ABAC via Voters)

La sécurité au niveau des ressources est implémentée via **2 Voters Symfony** :

#### 1. **PetVoter** (`src/Security/Voter/PetVoter.php`)
Contrôle l'accès aux Pets :
- **Owner** : Peut TOUT faire (view, edit, delete, feed, play, heal, sleep, bathe)
- **Caretaker** : Peut faire des soins SEULEMENT SI délégation ACTIVE couvre la date actuelle
- **Autres** : Accès refusé

#### 2. **DelegationVoter** (`src/Security/Voter/DelegationVoter.php`)
Contrôle l'accès aux Délégations :
- **Owner** : Peut voir et révoquer la délégation
- **Caretaker** : Peut voir et accepter la délégation
- **Autres** : Accès refusé

**Documentation complète** : Voir [SECURITY_VOTERS.md](SECURITY_VOTERS.md)

---

## 🧪 Tests

Les tests unitaires et fonctionnels sont à développer dans le dossier `tests/`.

Exemple pour lancer les tests :
```bash
php bin/phpunit
```

---

## 🚀 Prochaines étapes

1. **Ajouter une Shop** : Créer des items shoppeables, implémenter l'achat
2. **Stats dégradation** : Faire viellir les pets si non interactifs
3. **Websockets** : Notifications en temps réel pour les délégations
4. **Frontend SPA** : Développer un frontend React/Vue qui consomme cette API
5. **Tests** : Compléter les test suites unitaires et fonctionnels

---

## 📞 Support

Pour toute question sur l'architecture ou l'utilisation des endpoints, consultez le code des contrôleurs et services.

---

**Version** : 1.0.0  
**Dernière mise à jour** : 02-04-2026
