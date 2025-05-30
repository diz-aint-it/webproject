<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingName', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Enter your full name']
            ])
            ->add('shippingEmail', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter your email address']
            ])
            ->add('shippingPhone', TelType::class, [
                'label' => 'Phone Number',
                'attr' => ['placeholder' => 'Enter your phone number']
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping Address',
                'attr' => [
                    'placeholder' => 'Enter your complete shipping address',
                    'rows' => 3
                ]
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'City',
                'attr' => ['placeholder' => 'Enter your city']
            ])
            ->add('shippingState', TextType::class, [
                'label' => 'State/Province',
                'attr' => ['placeholder' => 'Enter your state or province']
            ])
            ->add('shippingPostalCode', TextType::class, [
                'label' => 'Postal Code',
                'attr' => ['placeholder' => 'Enter your postal code']
            ])
            ->add('shippingCountry', TextType::class, [
                'label' => 'Country',
                'attr' => ['placeholder' => 'Enter your country']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order Notes (Optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Add any special instructions or notes for your order',
                    'rows' => 3
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
} 