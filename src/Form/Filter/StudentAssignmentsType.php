<?php

namespace App\Form\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class StudentAssignmentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                "label" => false,
                "required" => false,
                "attr" => [
                    "placeholder" => "hledat zadání...",
                    "class" => "form-control"
                ],
                "row_attr" => ["class" => "me-2"],
            ])
            ->add('s', SubmitType::class, [
                "label" => "<i class=\"bi bi-search\"></i>",
                "label_html" => true,
                "attr" => ["class" => "btn btn-primary"]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "attr" => [
                "class" => "btn-toolbar justify-content-center mb-3 mt-4",
                "role" => "toolbar",
            ],
        ]);
    }
}
