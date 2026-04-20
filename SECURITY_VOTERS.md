# 🔒 Sécurité RBAC/ABAC - Voters Documentation

## Vue d'ensemble

La sécurité de PetConnect est implémentée avec **deux Voters** (Symfony Authorization Voters) qui gèrent les contrôles d'accès au niveau attribute (ABAC).

### Voters Implémentés

1. **PetVoter** : Gère l'accès aux ressources Pet (ownership + delegations)
2. **DelegationVoter** : Gère l'accès aux délégations

---

## 🐾 PetVoter

**Fichier** : `src/Security/Voter/PetVoter.php`

### Attributes Disponibles

```php
const DELETE = 'DELETE';   // Suppression : Owner SEULEMENT
const VIEW = 'VIEW';       // Lecture : Owner OU caretaker ACTIF
const EDIT = 'EDIT';       // Modification : Owner SEULEMENT
const FEED = 'FEED';       // Action soin : Owner OU caretaker ACTIF
const PLAY = 'PLAY';       // Action soin : Owner OU caretaker ACTIF
const HEAL = 'HEAL';       // Action soin : Owner OU caretaker ACTIF
const SLEEP = 'SLEEP';     // Action soin : Owner OU caretaker ACTIF
const BATHE = 'BATHE';     // Action soin : Owner OU caretaker ACTIF
```

### Logique de Décision

```
Owner du pet = user.id == pet.owner_id
    → Vote YES pour tous les attributes

Autre user
    → Si attribute ∈ {FEED, PLAY, HEAL, SLEEP, BATHE}:
        → Cherche délégation(pet, user, status=ACTIVE)
        → Vérifie: now >= startDate && now <= endDate
        → Vote YES si trouvée et valide, NON sinon
    → Sinon: Vote NON
```

### Utilisation dans les Controllers

#### Exemple 1 : Voir un pet
```php
#[Route('/{id}', methods: ['GET'])]
public function getPet(int $id, #[CurrentUser] $user): JsonResponse
{
    $pet = $this->petRepository->find($id);
    if (!$pet) {
        return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
    }
    
    // Voter vérifie : user.id == pet.owner.id OU user est caretaker ACTIF
    $this->denyAccessUnlessGranted(PetVoter::VIEW, $pet);
    
    // Retourner pet...
}
```

#### Exemple 2 : Nourrir un pet
```php
#[Route('/{id}/feed', methods: ['POST'])]
public function feedPet(int $id, #[CurrentUser] $user): JsonResponse
{
    $pet = $this->petRepository->find($id);
    
    // Voter vérifie :
    // - user.id == pet.owner.id OU
    // - Il existe une délégation ACTIVE couvrant maintenant
    $this->denyAccessUnlessGranted(PetVoter::FEED, $pet);
    
    $this->petService->feedPet($pet, $user);
}
```

#### Exemple 3 : Supprimer un pet
```php
#[Route('/{id}', methods: ['DELETE'])]
public function deletePet(int $id, #[CurrentUser] $user): JsonResponse
{
    $pet = $this->petRepository->find($id);
    
    // Voter vérifie : user.id == pet.owner.id UNIQUEMENT
    $this->denyAccessUnlessGranted(PetVoter::DELETE, $pet);
    
    $this->petService->deletePet($pet);
}
```

---

## 🤝 DelegationVoter

**Fichier** : `src/Security/Voter/DelegationVoter.php`

### Attributes Disponibles

```php
const VIEW = 'VIEW';       // Voir une délégation : Owner OU caretaker
const REVOKE = 'REVOKE';   // Révoquer : Owner SEULEMENT
const ACCEPT = 'ACCEPT';   // Accepter : Caretaker SEULEMENT
```

### Logique de Décision

```
VIEW:
    → Vote YES si user.id == delegation.owner_id OU user.id == delegation.caretaker_id
    → Sinon: Vote NON

REVOKE:
    → Vote YES si user.id == delegation.owner_id
    → Sinon: Vote NON

ACCEPT:
    → Vote YES si user.id == delegation.caretaker_id
    → Sinon: Vote NON
```

### Utilisation dans les Controllers

#### Exemple 1 : Accepter une délégation
```php
#[Route('/{id}/accept', methods: ['PATCH'])]
public function acceptDelegation(int $id, #[CurrentUser] $user): JsonResponse
{
    $delegation = $this->delegationRepository->find($id);
    
    // Voter vérifie : user.id == delegation.caretaker_id
    $this->denyAccessUnlessGranted(DelegationVoter::ACCEPT, $delegation);
    
    $this->delegationService->acceptDelegation($delegation);
}
```

#### Exemple 2 : Révoquer une délégation
```php
#[Route('/{id}/revoke', methods: ['PATCH'])]
public function revokeDelegation(int $id, #[CurrentUser] $user): JsonResponse
{
    $delegation = $this->delegationRepository->find($id);
    
    // Voter vérifie : user.id == delegation.owner_id
    $this->denyAccessUnlessGranted(DelegationVoter::REVOKE, $delegation);
    
    $this->delegationService->revokeDelegation($delegation);
}
```

---

## 📋 Matrice d'Accès

### Scénarios d'Accès Pets

| Utilisateur | Action | Pet Owner | Délégation Status | Résultat |
|-------------|--------|-----------|-------------------|----------|
| Owner | VIEW | Lui-même | N/A | ✅ YES |
| Owner | FEED | Lui-même | N/A | ✅ YES |
| Owner | DELETE | Lui-même | N/A | ✅ YES |
| Caretaker | VIEW | Autre | ACTIVE + in_time | ✅ YES |
| Caretaker | FEED | Autre | ACTIVE + in_time | ✅ YES |
| Caretaker | FEED | Autre | PENDING | ❌ NO |
| Caretaker | FEED | Autre | ACTIVE + expired | ❌ NO |
| Caretaker | DELETE | Autre | ACTIVE + in_time | ❌ NO |
| Random User | VIEW | Autre | None | ❌ NO |

### Scénarios d'Accès Délégations

| Utilisateur | Action | Role | Résultat |
|-------------|--------|------|----------|
| User1 | ACCEPT | Caretaker | ✅ YES |
| User1 | ACCEPT | Owner | ❌ NO |
| User1 | REVOKE | Owner | ✅ YES |
| User1 | REVOKE | Caretaker | ❌ NO |
| User1 | VIEW | Owner | ✅ YES |
| User1 | VIEW | Caretaker | ✅ YES |
| User2 | VIEW | Neither | ❌ NO |

---

## 🔐 Flux de Délégation Complet

### Scenario : User1 délègue le Pet à User2

```
1. User1 crée délégation (POST /api/delegations)
   → PetVoter vérifie que User1 est owner du pet ✅
   → Délégation créée avec status=PENDING

2. User2 reçoit notification
   → GET /api/delegations retourne la délégation
   → DelegationVoter vérifie User2 est caretaker ✅

3. User2 accepte (PATCH /api/delegations/{id}/accept)
   → DelegationVoter::ACCEPT vérifie User2 est caretaker ✅
   → Status change de PENDING → ACTIVE

4. User2 nourrit le pet (POST /api/pets/{id}/feed)
   → PetVoter vérifie :
     - Cherche délégation(Pet, User2, ACTIVE)
     - Vérifie startDate <= now <= endDate
     → Vote YES ✅

5. User1 revoque (PATCH /api/delegations/{id}/revoke)
   → DelegationVoter::REVOKE vérifie User1 est owner ✅
   → Status change de ACTIVE → REVOKED

6. User2 tente nourrir le pet (POST /api/pets/{id}/feed)
   → PetVoter cherche délégation(Pet, User2, ACTIVE)
   → Délégation a status=REVOKED (pas ACTIVE)
   → Vote NON ❌
   → Retourne 403 Forbidden
```

---

## 🛡️ Sécurité Garantie

Grâce aux Voters, la sécurité est maintenant :

✅ **Centralisée** : Logique d'accès en un seul endroit (les Voters)
✅ **Cohérente** : Même logique appliquée partout
✅ **Testable** : Les Voters peuvent être testés indépendamment
✅ **Extensible** : Facile d'ajouter de nouveaux attributes
✅ **Maintenable** : Pas de duplication de logique d'autorisation

### Changements Apportés

**Avant (Unsafe)**:
```php
// Controllers avaient du code like this:
if ($pet->getOwner()->getId() !== $user->getId()) {
    return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
}
```

**Après (Safe)**:
```php
// Controllers maintenant utilisent:
$this->denyAccessUnlessGranted(PetVoter::DELETE, $pet);
// Si vote=NO, Symfony lance automatiquement une AccessDeniedException (403)
```

---

## 📊 Performance

Les Voters sont appelés uniquement quand nécessaire :

- **Cache**: Symfony cache les votes par défaut
- **Lazy loading**: Les délégations sont chargées via `findBy()` avec des indexes
- **Timeouts**: Pas de requête DB supplémentaire, tout repose sur Doctrine

---

## 🧪 Testing Your Voters

### Unit Test Example

```php
public function testPetVoterAllowsOwner(): void
{
    $owner = new User();
    $owner->setId(1);
    
    $pet = new Pet();
    $pet->setOwner($owner);
    
    $voter = new PetVoter($delegationRepository);
    $token = $this->createTokenWithUser($owner);
    
    $result = $voter->vote($token, $pet, [PetVoter::DELETE]);
    
    $this->assertEquals(Voter::ACCESS_GRANTED, $result);
}
```

---

## 📚 Resources

- [Symfony Security Voters Documentation](https://symfony.com/doc/current/security/voters.html)
- [Symfony Authorization](https://symfony.com/doc/current/security/authorization.html)
- [ABAC Pattern](https://www.cloudflare.com/learning/access-management/abac-attribute-based-access-control/)
