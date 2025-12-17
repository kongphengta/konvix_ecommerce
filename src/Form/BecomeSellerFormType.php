<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BecomeSellerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shopName', TextType::class, [
                'label' => 'Nom de la boutique',
                'required' => true,
            ])
            ->add('shopDescription', TextareaType::class, [
                'label' => 'Description de la boutique',
                'required' => false,
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact',
                'required' => true,
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Pas d'entité liée pour une simple demande
        ]);
    }
}
