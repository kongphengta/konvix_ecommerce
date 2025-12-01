<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextEditorField::new('description'),
            NumberField::new('price'),
            DateTimeField::new('createdAt'),
            IntegerField::new('stock'),
            Field::new('mainImageFile')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
            \EasyCorp\Bundle\EasyAdminBundle\Field\ImageField::new('mainImagePath')
                ->setBasePath('/uploads/product_images/')
                ->onlyOnIndex()
                ->onlyOnDetail(),
            TextField::new('imageFolder'),
            AssociationField::new('category'),
            AssociationField::new('seller'),
            // Ajoute ici d'autres champs si besoin
        ];
    }
}
