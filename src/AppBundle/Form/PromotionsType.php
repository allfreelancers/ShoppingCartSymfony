<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $percentages = [];
        $percentages[' -Please select- '] = '';
        for ($i = 1; $i <= 100; $i++) {
            $percentages[$i] = $i;
        }

        $builder->add('promotionName', TextType::class, ['required' => false])
            ->add('percentages', ChoiceType::class, [
                'choices' => $percentages,
                'preferred_choices' => [' -Please select- '],
                'required' => false
            ])
            ->add('dateFrom', DateType::class)
            ->add('dateTo', DateType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Promotions'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_promotions';
    }
}
