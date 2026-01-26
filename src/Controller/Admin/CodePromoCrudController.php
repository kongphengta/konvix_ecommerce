<?php

namespace App\Controller\Admin;

use App\Entity\CodePromo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\PromoMailerService;

class CodePromoCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        $sendPromo = Action::new('sendPromo', 'Envoyer par email', 'fa fa-envelope')
            ->linkToCrudAction('sendPromoEmail')
            ->displayIf(static function ($entity) {
                return $entity instanceof CodePromo;
            });
        return $actions
            ->add(Action::INDEX, Action::DETAIL)
            ->add(Action::DETAIL, $sendPromo)
            ->add(Action::INDEX, $sendPromo);
    }

    public function sendPromoEmail(Request $request, PromoMailerService $promoMailerService, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('entityId');
        $promo = $entityManager->getRepository(CodePromo::class)->find($id);
        $email = $request->request->get('email');
        $sent = false;
        if ($request->isMethod('POST') && $email) {
            $promoMailerService->sendPromoCode(
                $email,
                $promo->getCode(),
                $promo->getDescription(),
                $promo->getDateFin() ? $promo->getDateFin()->format('d/m/Y') : null
            );
            $this->addFlash('success', 'Code promo envoyé à ' . $email);
            $sent = true;
        }
        return $this->render('admin/code_promo/send_email.html.twig', [
            'promo' => $promo,
            'sent' => $sent
        ]);
    }
    public static function getEntityFqcn(): string
    {
        return CodePromo::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code')->setLabel('Code'),
            TextareaField::new('description')->setLabel('Description')->hideOnIndex(),
            ChoiceField::new('type')->setLabel('Type')->setChoices([
                'Pourcentage' => 'pourcentage',
                'Montant' => 'montant',
            ]),
            NumberField::new('valeur')->setLabel('Valeur'),
            DateTimeField::new('dateDebut')->setLabel('Début'),
            DateTimeField::new('dateFin')->setLabel('Fin'),
            BooleanField::new('actif')->setLabel('Actif'),
            NumberField::new('utilisationMax')->setLabel('Utilisations max'),
            NumberField::new('utilisations')->setLabel('Utilisations')->onlyOnIndex(),
        ];
    }
}
