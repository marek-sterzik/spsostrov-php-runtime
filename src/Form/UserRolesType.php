<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserRolesType extends AbstractType
{
    const DEFAULT_ROLE_LABEL = "implicitní hodnota z SSO (%s)";
    const DEFAULT_ROLE_LABEL_NO_ROLE = "implicitní hodnota z SSO";
    const ROLES = User::ROLES;
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->getRoleChoices($options['superadmin'], $options['default_role']);
        $builder
            ->add('effectiveRole', ChoiceType::class, [
                "label" => "role:",
                "choices" => $choices,
                "choice_attr" => fn($choice) => ["data-rqcls" => ($choice  === 'ROLE_STUDENT') ? 'true' : 'false'],
            ])
            ->add('effectiveStudentClass', TextType::class, [
                "label" => "třída:",
                "required" => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'superadmin' => false,
            'default_role' => null,
        ]);
    }

    private function getRoleChoices(bool $superadmin, ?string $defaultRole): array
    {
        $defaultLabel = $this->getDefaultRoleLabel($defaultRole);
        $choices = [
            $defaultLabel => null,
        ];
        foreach (self::ROLES as $role => $label) {
            if ($role !== 'ROLE_OTHER' && ($role !== 'ROLE_SUPERADMIN' || $superadmin)) {
                $choices[$label] = $role;
            }
        }
        return $choices;
    }

    private function getDefaultRoleLabel(?string $defaultRole): string
    {
        $role = isset($defaultRole) ? (self::ROLES[$defaultRole] ?? null) : null;
        return isset($role) ? sprintf(self::DEFAULT_ROLE_LABEL, $role) : self::DEFAULT_ROLE_LABEL_NO_ROLE;
    }
}
