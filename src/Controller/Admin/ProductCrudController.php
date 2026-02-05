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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;

#[IsGranted('ROLE_SELLER')]
class ProductCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        $validate = Action::new('validate', 'Valider', 'fa fa-check')
            ->linkToCrudAction('validateProduct')
            ->displayIf(fn(Product $product) => !$product->isValidated());
        return $actions
            ->add('index', $validate)
            ->add('detail', $validate);
    }

    public function validateProduct(AdminContext $context): RedirectResponse
    {
        /** @var Product $product */
        $product = $context->getEntity()->getInstance();
        $product->setIsValidated(true);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Produit validé avec succès.');
        $url = $this->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl();
        return $this->redirect($url);
    }

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
            BooleanField::new('isValidated', 'Validé'),

            IntegerField::new('stock')
                ->setLabel('Alerte stock')
                ->formatValue(function ($value, $entity) {
                    $stock = $entity->getStock();
                    $threshold = method_exists($entity, 'getCriticalThreshold') ? $entity->getCriticalThreshold() : 2; // Valeur par défaut si la méthode n'existe pas
                    if ($stock < 0) {
                        return '<span class="badge bg-dark">Stock négatif</span>';
                    } elseif ($stock == 0) {
                        return '<span class="badge bg-warning text-dark">Rupture</span>';
                    } elseif ($stock <= $threshold) {
                        return '<span class="badge bg-danger">Stock critique</span>';
                    } else {
                        return '<span class="badge bg-success">OK</span>';
                    }
                })
                ->onlyOnIndex(),
        ];
    }
}
