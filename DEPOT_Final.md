# Fonctionnalité : Notification Email Automatique au Vendeur lors d’une Commande

## Objectif
Permettre l’envoi automatique d’un email professionnel au vendeur concerné à chaque nouvelle commande passée sur la plateforme, afin de l’informer rapidement et de garantir un traitement efficace des commandes.

## Description Fonctionnelle
- Lorsqu’un client valide une commande contenant un ou plusieurs produits, chaque vendeur concerné reçoit un email personnalisé.
- L’email contient :
  - Un bandeau professionnel avec le logo Konvix
  - Le détail des articles à expédier (numéro, nom, quantité)
  - La date limite d’expédition (2 jours après la commande)
  - Les coordonnées du client
  - Un message d’alerte en cas de retard
- L’email est envoyé en HTML, avec une mise en page claire et responsive.

## Fonctionnement Technique
1. **Déclenchement** :
   - À la validation du paiement, le contrôleur `CheckoutController` regroupe les produits par vendeur.
2. **Préparation des données** :
   - Pour chaque vendeur, on récupère :
     - Les articles concernés (ID, nom, quantité)
     - Les coordonnées du client
     - La date de la commande
     - La date limite d’expédition (commande + 2 jours)
3. **Génération de l’email** :
   - Utilisation d’un template Twig HTML (`templates/email/seller_order_notification.html.twig`)
   - Insertion dynamique des données (logo, tableau produits, deadline, client)
4. **Envoi** :
   - Utilisation du composant Mailer de Symfony (`Symfony\Component\Mailer\MailerInterface`)
   - Envoi à l’adresse email du vendeur

## Exemple de code (extrait du contrôleur)
```php
// ...
$htmlVendeur = $this->renderView('email/seller_order_notification.html.twig', [
    'sellerName' => $sellerName,
    'produits' => $produits,
    'clientName' => $user->getFirstName() . ' ' . $user->getLastName(),
    'clientAddress' => $address['address'] . ', ' . $address['zip'] . ' ' . $address['city'] . ', ' . $address['country'],
    'clientPhone' => $address['phone'],
    'deadline' => $deadline,
]);
$emailVendeur = (new Email())
    ->from('no-reply@konvix.fr')
    ->to($sellerEmail)
    ->subject('Nouvelle commande à préparer sur Konvix')
    ->html($htmlVendeur);
$mailer->send($emailVendeur);
// ...
```

## Exemple de template HTML (extrait)
```twig
<div style="background:#0d1a4a; border-radius:12px; display:flex; align-items:center; padding:16px 24px; margin-bottom:24px;">
    <img src="https://konvix.fr/uploads/product_images/konvixlogo.png" alt="Konvix" style="height:48px; margin-right:18px;">
    <span style="color:#fff; font-size:1.4em; font-weight:bold;">Nouvelle commande à préparer</span>
</div>
<table class="order-table">
    <thead>
    <tr>
        <th>Numéro de l'article</th>
        <th>Produit</th>
        <th>Quantité</th>
    </tr>
    </thead>
    <tbody>
    {% for item in produits %}
        <tr>
            <td>{{ item.product.id }}</td>
            <td>{{ item.product.name }}</td>
            <td>{{ item.quantity }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>
```

## Emplacement des fichiers
- Contrôleur : `src/Controller/CheckoutController.php`
- Template email : `templates/email/seller_order_notification.html.twig`

## Points forts
- Email professionnel, clair et responsive
- Délai d’expédition mis en avant (2 jours)
- Identification précise des articles (numéro, nom)
- Alerte en cas de retard
- Automatisation totale, pas d’action manuelle requise

## À insérer ici :
- _Capture d’écran de l’email reçu par le vendeur (bandeau, tableau, deadline)_
- _Capture d’écran du workflow de commande côté client_
- _Capture d’écran de la configuration du vendeur dans l’admin_

---

Cette fonctionnalité améliore la réactivité des vendeurs, la satisfaction client et la fiabilité du traitement des commandes sur la plateforme Konvix.

