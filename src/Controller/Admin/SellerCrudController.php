<?php

namespace App\Controller\Admin;

use App\Entity\Seller;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class SellerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        // Retourne la classe de l'entité Seller
        return Seller::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // Configure les champs à afficher dans l'interface d'administration pour l'entité Seller
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextEditorField::new('description'),
            AssociationField::new('user')
                ->setRequired(true)
                ->setFormTypeOption('choice_label', 'email'),
        ];
    }
}
