<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Category;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('category', EntityType::class, [
                'required' => true,
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('mainImageFile', FileType::class, [
                'label' => 'Image principale',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('imageFolder', TextType::class, [
                'label' => 'Dossier image',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
        ;
        // NE PAS AJOUTER NI RETIRER DE CHAMP 'seller' DANS CE FORMULAIRE
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
