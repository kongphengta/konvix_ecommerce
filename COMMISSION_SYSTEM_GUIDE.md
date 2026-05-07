# Système de Commissions Vendeurs — Guide Complet

**Date**: 7 mai 2026 | **Env**: Development & Production | **Taux commission**: 10% (fixe)

---

## 📋 Résumé de l'implémentation

### Ce qui a été fait
Le système de commissions vendeurs a été implémenté de zéro. À chaque commande payée (Stripe ou PayPal), une commission de 10% est calculée par vendeur, enregistrée dans la BD, et un solde vendeur se constitue. L'admin peut voir tous les virements à faire et les marquer comme effectués.

---

## 🗄️ **BASE DE DONNÉES** (Migration appliquée)

### Migration: `migrations/Version20260507082448.php`

**Nouvelle table `seller_earning`:**
```sql
CREATE TABLE seller_earning (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  order_id INT NOT NULL,
  gross_amount DOUBLE NOT NULL,          -- Montant brut de la vente par ce vendeur
  commission_amount DOUBLE NOT NULL,     -- 10% de gross_amount
  net_amount DOUBLE NOT NULL,            -- gross_amount - commission_amount
  commission_rate DOUBLE NOT NULL,       -- Taux stocké (actuellement 0.10)
  created_at DATETIME NOT NULL,          -- Timestamp de la commande
  status VARCHAR(20) NOT NULL,           -- 'pending' | 'paid'
  paid_at DATETIME DEFAULT NULL,         -- Quand le virement a été effectué
  FOREIGN KEY (seller_id) REFERENCES seller(id),
  FOREIGN KEY (order_id) REFERENCES `order`(id)
)
```

**Colonnes ajoutées à `seller`:**
- `iban VARCHAR(34)` — IBAN du vendeur pour les virements (optionnel, peut être NULL)
- `commission_rate DOUBLE` — Taux de commission (actuellement tous à 0.10 = 10%)

### Vérification BD
```bash
# Vérifier que tout existe
mysql -u konvixadmin -p'MonteeBelleFrance' konvix_ecommerce \
  -e "SHOW COLUMNS FROM seller_earning; SHOW COLUMNS FROM seller LIKE 'iban';"
```

---

## 💾 **ENTITÉS** (src/Entity/)

### 1. `Seller.php` — Modifiée
**Ajouts:**
```php
public const COMMISSION_RATE = 0.10;  // Taux fixe à 10%

#[ORM\Column(length: 34, nullable: true)]
private ?string $iban = null;

#[ORM\Column(type: 'float', nullable: true)]
private ?float $commissionRate = self::COMMISSION_RATE;

// Getters/Setters
public function getIban(): ?string { return $this->iban; }
public function setIban(?string $iban): self { $this->iban = $iban; return $this; }
public function getCommissionRate(): ?float { return $this->commissionRate; }
public function setCommissionRate(?float $commissionRate): self { $this->commissionRate = $commissionRate; return $this; }
```

### 2. `SellerEarning.php` — Nouvelle entité
Enregistre chaque gain d'un vendeur par commande.

**Propriétés:**
- `seller` (ManyToOne) → Seller
- `order` (ManyToOne) → Order
- `grossAmount` → Montant brut (ex: 100€)
- `commissionAmount` → Commission (ex: 10€)
- `netAmount` → Net vendeur (ex: 90€)
- `commissionRate` → 0.10
- `createdAt` → DateTimeImmutable
- `status` → 'pending' | 'paid'
- `paidAt` → DateTimeImmutable (NULL tant que pending)

**Utilité**: Historique complet, traçabilité, interface admin

---

## 🔌 **REPOSITORIES** (src/Repository/)

### `SellerEarningRepository.php` — Nouvelle

**Méthodes clés:**

```php
// Tous les gains en attente pour un vendeur
public function findPendingBySeller(Seller $seller): array

// Somme totale (net) des gains en attente pour un vendeur
public function getTotalPendingBySeller(Seller $seller): float

// Tous les gains en attente, groupés par vendeur (retourne array d'objets avec clés:
// grossTotal, commissionTotal, netTotal, count, seller)
public function findAllPendingGroupedBySeller(): array
```

---

## ⚙️ **CONTRÔLEURS** (src/Controller/)

### 1. `CheckoutController.php` — Modifié

**Import ajouté:**
```php
use App\Entity\SellerEarning;
```

**Méthode privée ajoutée:**
```php
private function createSellerEarnings(
    array $items,
    \App\Entity\Order $order,
    EntityManagerInterface $em
): void
```

**Logique:**
- Parcourt les items du panier
- Regroupe par vendeur et totalise les montants
- Pour chaque vendeur: calcule commission (gross × 0.10), net (gross - commission)
- Crée et persiste un `SellerEarning` avec status='pending'
- Flush tout

**Appels ajoutés:**
- Dans `success()` (Stripe) — après flush OrderItems
- Dans `successPaypal()` (PayPal) — après flush OrderItems

**Flux complet:**
```
1. Client valide paiement (Stripe/PayPal)
2. Commande créée, OrderItems créés, stock diminué, flush
3. createSellerEarnings() — crée les SellerEarning (1 par vendeur)
4. Emails vendeurs/client envoyés
5. Panier vidé
```

### 2. `SellerController.php` — Modifié

**Nouvelles routes:**

#### `/seller/earnings` (GET) — `seller_earnings`
Affiche le dashboard vendeur : solde en attente + historique.

```php
public function earnings(\App\Repository\SellerEarningRepository $earningRepository): Response
```

**Template reçoit:**
- `earnings` → liste des SellerEarning du vendeur (triée par date DESC)
- `totalPending` → somme des netAmount où status='pending'

**Affichage:**
- Grande carte avec solde net en attente
- Tableau: Date | N° Commande | Brut | Commission | Net | Statut

#### `/seller/iban` (GET/POST) — `seller_iban`
Formulaire pour renseigner/modifier son IBAN.

```php
public function updateIban(Request $request, EntityManagerInterface $em): Response
```

**POST:**
- Validation IBAN: regex `^[A-Z]{2}[0-9A-Z]{13,32}$` (FR76...)
- Sauvegarde dans `seller.iban`
- Flash success + redirect vers earnings

### 3. `Admin/SellerPayoutsController.php` — Nouveau

#### `/admin/seller-payouts` (GET) — `admin_seller_payouts`
Dashboard admin pour les virements en attente.

```php
public function index(SellerEarningRepository $earningRepository): Response
```

**Template reçoit:**
- `pendingGrouped` → array de [{seller, grossTotal, commissionTotal, netTotal, count}]

**Affichage:**
- Tableau: Vendeur | IBAN | Ventes brutes | Commission Konvix | Net à virer | # Commandes | Bouton
- IBAN rouge si manquant
- Bouton "Virement effectué" disabled si pas d'IBAN

#### `/admin/seller-payouts/{sellerId}/mark-paid` (POST) — `admin_seller_payouts_mark_paid`
Marque tous les gains d'un vendeur comme payés.

```php
public function markPaid(
    int $sellerId,
    Request $request,
    SellerEarningRepository $earningRepository,
    EntityManagerInterface $em
): Response
```

**Action:**
- Cherche tous les `SellerEarning` avec status='pending' du vendeur
- Change status='paid', setPaidAt(now)
- Flush
- Flash success + redirect

**Sécurité:**
- CSRF token `payout_{sellerId}`
- Confirmation JS (affiche montant + nom vendeur)

---

## 🎨 **TEMPLATES** (templates/)

### 1. `seller/earnings.html.twig` — Nouveau

**Route:** `/seller/earnings` (vendeur connecté)

**Affichage:**
- En-tête avec lien vers `/seller/iban`
- Carte grande avec solde net pending (vert, gros)
- Tableau historique:
  - Colonne "Statut" : badge vert "Versé le DD/MM/YYYY" ou orange "En attente"
  - Montants formatés (2 décimales, séparateur français)

**Messages:**
- Si aucun gain : "Aucun gain enregistré pour le moment."

### 2. `seller/iban.html.twig` — Nouveau

**Route:** `/seller/iban` (vendeur connecté)

**Formulaire:**
- Label: "IBAN"
- Input: placeholder="FR76XXXXXXXXXXXXXXXXXXXX", maxlength="34"
- Helper text: "Format : FR76... (sans espaces)"
- Bouton: "Enregistrer"
- Lien retour vers earnings

**POST:**
- Si validation KO → flash danger "Format IBAN invalide"
- Si OK → enregistre + flash success + redirect earnings

### 3. `admin/seller_payouts.html.twig` — Nouveau

**Route:** `/admin/seller-payouts` (admin)

**Affichage:**
- Titre: "Commissions vendeurs à virer"
- Tableau dark:
  - Vendeur (nom + email)
  - IBAN (code) — ⚠️ rouge "Non renseigné" si NULL
  - Ventes brutes (text-right)
  - Commission Konvix (text-right, red)
  - Net à virer (text-right, bold, green)
  - # Commandes
  - Bouton "Virement effectué"

**Bouton action:**
- Form POST → `/admin/seller-payouts/{sellerId}/mark-paid`
- Confirmation JS: "Confirmer le virement de XXX € vers NOM ?"
- Disabled si pas d'IBAN (title="IBAN manquant")
- CSRF token: `payout_{sellerId}`

**Messages:**
- Si aucun pending : "Aucun virement en attente."
- Flash success après virement marqué

---

## 🔗 **ROUTES AJOUTÉES**

| Route | Méthode | Nom | Contrôleur | Accès | Descrition |
|-------|---------|-----|-----------|-------|-----------|
| `/seller/earnings` | GET | `seller_earnings` | SellerController::earnings() | ROLE_SELLER | Dashboard gains |
| `/seller/iban` | GET/POST | `seller_iban` | SellerController::updateIban() | ROLE_SELLER | Formulaire IBAN |
| `/admin/seller-payouts` | GET | `admin_seller_payouts` | SellerPayoutsController::index() | ROLE_ADMIN | Liste virements à faire |
| `/admin/seller-payouts/{sellerId}/mark-paid` | POST | `admin_seller_payouts_mark_paid` | SellerPayoutsController::markPaid() | ROLE_ADMIN | Marquer virement effectué |

---

## 📊 **FLUX COMPLET D'UNE COMMANDE**

### Exemple: Commande 3 articles (2 vendeurs différents)

**Données initiales:**
- Article 1 (Vendeur A, 50€) × 2 = 100€
- Article 2 (Vendeur B, 30€) × 1 = 30€
- Frais livraison: 10€
- **Total commande: 140€**

**Après paiement Stripe/PayPal validé:**

1. **Création Order** (Order.php):
   - user = Client
   - total = 130€ (100 + 30, livraison enregistrée séparément)
   - status = 'payé'
   - transporteur = 'Colissimo'

2. **Création OrderItems** (3 items):
   - Item 1: Article 1, qty 2, price 50€
   - Item 2: Article 2, qty 1, price 30€
   - Item 3: (livraison enregistrée)

3. **Mise à jour stocks** (Product.php):
   - Article 1: stock -= 2
   - Article 2: stock -= 1

4. **Création SellerEarnings** (SellerEarning.php):

   **Pour Vendeur A:**
   - grossAmount = 100€
   - commissionRate = 0.10
   - commissionAmount = 10€
   - netAmount = 90€
   - status = 'pending'
   - createdAt = now

   **Pour Vendeur B:**
   - grossAmount = 30€
   - commissionRate = 0.10
   - commissionAmount = 3€
   - netAmount = 27€
   - status = 'pending'
   - createdAt = now

5. **Emails envoyés:**
   - Vendeur A: "Nouvelle commande à préparer" (2 articles)
   - Vendeur B: "Nouvelle commande à préparer" (1 article)
   - Client: "Confirmation de commande" (détails facture)

6. **Panier vidé** (CartService):
   - Session cart = []

---

## 📱 **INTERFACE VENDEUR** (`/seller/earnings`)

**Ce que le vendeur voit:**

```
┌─────────────────────────────────────────────────┐
│  Mes gains          [Mon IBAN]                   │
├─────────────────────────────────────────────────┤
│ ╔═════════════════════════════════════════════╗ │
│ ║  Solde en attente de virement               ║ │
│ ║              117 €                          ║ │
│ ║  Net après commission Konvix (10%)          ║ │
│ ╚═════════════════════════════════════════════╝ │
├─────────────────────────────────────────────────┤
│ Date    │ N° Cde │ Brut │ Comission │ Net │ Statu
│ 07/05   │ #1234  │ 100€ │    -10€   │ 90€ │ ⏳
│ 06/05   │ #1233  │ 30€  │    -3€    │ 27€ │ ✅ Versé 05/05
└─────────────────────────────────────────────────┘
```

**Lien:** `/seller` → Carte "Mes gains" → `/seller/earnings`

---

## 👨‍💼 **INTERFACE ADMIN** (`/admin/seller-payouts`)

**Ce que l'admin voit:**

```
┌─────────────────────────────────────────────────────────────────────┐
│ Commissions vendeurs à virer                                         │
├─────────────────────────────────────────────────────────────────────┤
│ Vendeur          │ IBAN         │ Ventes │ Comission │ Net │ Cdes │
│ Jean Dupont      │ FR76XXXX...  │ 100€   │    10€    │ 90€ │  1  │
│ john@email.com   │              │        │           │     │     │
│                  │              │        │           │     │[Virement]
├─────────────────────────────────────────────────────────────────────┤
│ Marie Martin     │ ⚠️ Non rensei│ 30€    │    3€     │ 27€ │  1  │
│ marie@email.com  │              │        │           │     │     │
│                  │              │        │           │     │[Virement]
│                  │              │        │           │     │(DISABLED)
└─────────────────────────────────────────────────────────────────────┘
```

**Action:** Clic sur "Virement" → Confirmation → Status = "paid", paidAt = now → Interface vendeur montre "Versé le 07/05"

---

## ✅ **CHECKLIST D'UTILISATION**

### Pour les vendeurs:

- [ ] Aller sur `/seller` → Voir la carte "Mes gains"
- [ ] Cliquer "Voir mes gains" → Voir solde et historique
- [ ] Cliquer "Mon IBAN" → Entrer son IBAN (ex: `FR7612345678901234567890123`)
- [ ] Revenir aux gains → Voir "Solde en attente: XXX €"

### Pour l'admin:

- [ ] Aller sur `/admin/seller-payouts`
- [ ] Voir liste de tous les vendeurs avec gains en attente
- [ ] Vérifier que chaque vendeur a un IBAN (sinon: ⚠️)
- [ ] Cliquer "Virement" → Confirmation → Valider
- [ ] Dashboard vendeur affiche: "Versé le DD/MM"

### Tests manuels (Dev):

```bash
# 1. Vérifier que la table existe
mysql -u konvixadmin -p'MonteeBelleFrance' konvix_ecommerce \
  -e "SELECT * FROM seller_earning LIMIT 1;"

# 2. Vérifier les routes
php bin/console debug:router | grep seller_earnings

# 3. Vérifier pas d'erreurs
php bin/console cache:clear
php bin/console lint:twig templates/

# 4. Test paiement en dev
# → Payer une commande Stripe/PayPal (montant test)
# → Vérifier SellerEarning créé en BD
# → Vérifier dashboard vendeur affiche le solde
```

---

## 🚀 **DÉPLOIEMENT PROD** (VPS konvix.fr)

### À faire après commit:

```bash
# Sur le VPS:
cd /var/www/konvix_ecommerce
git pull origin main
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

**Aucune config ajoutée** (pas de variables env nouvelles)

---

## 📝 **FICHIERS MODIFIÉS/CRÉÉS**

| Fichier | Type | État |
|---------|------|------|
| `src/Entity/Seller.php` | Modifié | ✅ |
| `src/Entity/SellerEarning.php` | Créé | ✅ |
| `src/Repository/SellerEarningRepository.php` | Créé | ✅ |
| `src/Controller/CheckoutController.php` | Modifié | ✅ |
| `src/Controller/SellerController.php` | Modifié | ✅ |
| `src/Controller/Admin/SellerPayoutsController.php` | Créé | ✅ |
| `templates/seller/earnings.html.twig` | Créé | ✅ |
| `templates/seller/iban.html.twig` | Créé | ✅ |
| `templates/admin/seller_payouts.html.twig` | Créé | ✅ |
| `migrations/Version20260507082448.php` | Créé | ✅ Appliquée |

---

## ❓ **FAQ & TROUBLESHOOTING**

### Q: Un vendeur voit "0 €" de solde, pourquoi?
**R:** Soit aucune commande payée, soit toutes les gains sont status='paid'. Vérifier en BD:
```bash
mysql -e "SELECT * FROM seller_earning WHERE seller_id = X AND status = 'pending';"
```

### Q: Un bouton "Virement" est désactivé?
**R:** L'IBAN du vendeur est NULL. L'admin doit d'abord dire au vendeur de renseigner son IBAN.

### Q: Les commissions n'apparaissent pas après paiement?
**R:** Vérifier que `CheckoutController::createSellerEarnings()` est appelé. Chercher dans le code: si une exception est levée avant ce code, les earnings ne seront pas créées.

### Q: Comment modifier le taux de commission (passer de 10% à 12%)?
**R:** 
1. Modifier `Seller::COMMISSION_RATE = 0.12`
2. Optionnel: ajouter une route `/admin/seller/{id}/commission` pour gérer par vendeur

### Q: Comment vérifier les virements effectués?
**R:** Dashboard vendeur → Si "Versé le DD/MM" s'affiche, c'est que status='paid' et paidAt est set.

---

## 🔐 **Sécurité**

- ✅ Routes protégées par `#[IsGranted('ROLE_SELLER')]` ou `ROLE_ADMIN`
- ✅ CSRF tokens sur formulaires POST
- ✅ Confirmation JS avant virement (prévient accidents)
- ✅ IBAN non modifiable par autre que le vendeur (CheckoutController SellerController)
- ⚠️ **À FAIRE**: Audit logs de qui a marqué quoi comme payé (tracabilité admin)

---

## 📞 **Contrats**

**Taux fixe**: 10% (commission_rate sur seller)

**Modèle de commission**: Par commande, par vendeur, calculée au moment du paiement

**Fréquence virements**: Manuel (admin décide quand virer)

**Devise**: EUR (€)

**Minimum avant virement**: Aucune limite (peut virer même 0,50€)

---

## 📚 **Documentation additionnelle**

- Voir `/copilot-instructions.md` pour contexte projet
- Voir migrations pour trace des changements BD
- Voir `.env.prod.local` (VPS) pour clés prod (Stripe live)

---

**Status**: ✅ Production-ready (7 mai 2026)

**Prochaines étapes potentielles**:
- Dashboard statistiques vendeur (ventes/mois, trending)
- Automatiser virements (API bancaire)
- Rapports fiscaux (export données commissions)
- Alertes email vendeur quand solde > X€

