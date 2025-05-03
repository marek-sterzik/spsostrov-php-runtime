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
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

class AssignmentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('a', ChoiceType::class, [
                'choices' => [
                    'moje' => false,
                    'všechny' => true,
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    "class" => "btn-group me-2 autosubmit",
                    "role" => "group",
                ],
                'label_attr' => ['class' => 'btn btn-outline-dark'],
                'choice_attr' => function ($choice, string $key, mixed $value) {
                    return ['class' => 'btn-check'];
                },
                "label" => false,
            ])
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
                "attr" => ["class" => "btn btn-primary me-3"]
            ])
        ;
        if ($options['add_link_url'] !== null) {
            $builder->add('add', ButtonType::class, [
                "label" => "<i class=\"bi bi-plus-square me-1\"></i> nové zadání",
                "label_html" => true,
                "attr" => ["class" => "btn btn-success", "data-link-href" => $options['add_link_url']]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "attr" => [
                "class" => "btn-toolbar justify-content-center mb-3 mt-4",
                "role" => "toolbar",
            ],
            "add_link_url" => null,
        ]);
    }
}
