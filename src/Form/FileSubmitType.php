<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class FileSubmitType extends AbstractType
{
    const LIMITS = ["file_limit", "size_limit", "upload_limit"];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $attrs = [];
        foreach (self::LIMITS as $limit) {
            $attr = "data-" . str_replace("_", "-", $limit);
            if ($options[$limit] !== null) {
                $attrs[$attr] = $options[$limit];
            }
        }
        $builder
            ->add('file', FileType::class, [
                'multiple' => ($options['file_limit'] === null || $options['file_limit'] > 1),
                'label' => 'přidat soubory:',
                'attr' => $attrs,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "file_limit" => null,
            "size_limit" => null,
            "upload_limit" => null,
        ]);
    }
}
