<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\User;
use App\Utility\CurrentSchoolYear;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AssignmentEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $markdown = "<a href=\"https://cs.wikipedia.org/wiki/Markdown\" target=\"_blank\">markdown</a>";
        $builder
            ->add('caption', TextType::class, [
                "label" => "nadpis:",
                "required" => true,
            ])
            ->add('description', TextareaType::class, [
                "label" => "popis:",
                "required" => false,
                "help" => "V popisu lze používat $markdown syntaxi.",
                "help_html" => true,
            ])
            ->add('classes', TextType::class, [
                "label" => "třídy, pro které je zadání určeno:",
                "help" => "zadejte čárkami oddělený seznam tříd, lze také používat hvězdičku <code>*</code> ".
                "pro libovolné znaky, např. <code>I*</code> pro všechny třídy <code>I1</code> až <code>I4</code>.",
                "help_html" => true,
            ])
            ->add('softDeadline', DateTimeType::class, [
                'widget' => 'single_text',
                "required" => false,
                'label' => "termín odevzdání:",
                'help' => 'po tomto termínu bude odevzdaná práce označena jako pozdní',
            ])
            ->add('hardDeadline', DateTimeType::class, [
                'widget' => 'single_text',
                "required" => false,
                'label' => "nepřekročitelný termín odevzdání:",
                'help' => 'po tomto termínu nebude možné odevzdat práci',
            ])
            ->add('public', ChoiceType::class, [
                'label' => "typ zadání:",
                'choices' => [
                    "soukromé (vidím ho jenom já)" => false,
                    "veřejné (vidí ho všichni učitelé)" => true,
                ]
            ])
            ->add('schoolYear', ChoiceType::class, [
                "label" => "školní rok:",
                "choices" => $this->getSchoolYearChoices(),
            ])
        ;
    }

    private function getSchoolYearChoices(): array
    {
        $currentSchoolYear = CurrentSchoolYear::get();
        $choices = [
            sprintf("%d/%d (tento školní rok)", $currentSchoolYear, $currentSchoolYear + 1) => $currentSchoolYear,
            sprintf("%d/%d (příští školní rok)", $currentSchoolYear + 1, $currentSchoolYear + 2) => $currentSchoolYear + 1,
        ];
        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignment::class,
        ]);
    }
}
