<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('product.name', 'Produit'),
            TextField::new('user.email', 'Utilisateur'),
            NumberField::new('rating', 'Note'),
            TextEditorField::new('comment', 'Commentaire'),
            \EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField::new('createdAt', 'Date')->hideOnForm(),
            \EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::new('isValidated', 'Validé'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isValidated', 'Validé'));
    }
}
