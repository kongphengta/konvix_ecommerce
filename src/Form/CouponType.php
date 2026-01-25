<?php

namespace App\Form;

use App\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code promo',
                'attr' => [
                    'placeholder' => 'Ex: NOEL2024',
                    'class' => 'form-control text-uppercase',
                    'maxlength' => 50
                ],
                'help' => 'Le code sera automatiquement converti en majuscules',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code promo ne peut pas être vide']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le code doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le code ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9_-]+$/i',
                        'message' => 'Le code ne peut contenir que des lettres, chiffres, tirets et underscores'
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réduction',
                'choices' => [
                    'Pourcentage (%)' => 'percentage',
                    'Montant fixe (€)' => 'fixed'
                ],
                'attr' => ['class' => 'form-control'],
                'expanded' => true,
                'help' => 'Choisissez le type de réduction à appliquer'
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Montant de la réduction',
                'attr' => [
                    'placeholder' => 'Ex: 10',
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01'
                ],
                'help' => 'Pour un pourcentage, entrez un nombre entre 1 et 100. Pour un montant fixe, entrez le montant en euros.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant ne peut pas être vide']),
                    new Assert\Positive(['message' => 'Le montant doit être positif'])
                ]
            ])
            ->add('expiresAt', DateTimeType:: class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Laissez vide pour un code sans date d\'expiration'
            ])
            ->add('usageLimit', IntegerType::class, [
                'label' => 'Limite d\'utilisation',
                'attr' => [
                    'placeholder' => 'Ex: 100',
                    'class' => 'form-control',
                    'min' => '1'
                ],
                'required' => false,
                'help' => 'Nombre maximum d\'utilisations.  Laissez vide pour illimité.',
                'constraints' => [
                    new Assert\Positive(['message' => 'La limite doit être positive'])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Code actif',
                'attr' => ['class' => 'form-check-input'],
                'required' => false,
                'help' => 'Décochez pour désactiver temporairement ce code'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coupon:: class,
        ]);
    }
}