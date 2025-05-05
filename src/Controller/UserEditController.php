<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\UserRolesType;
use App\Utility\RoleComparator;
use App\Validator\StudentClassValidator;
use App\StudentClass\StudentClass;

class UserEditController extends AbstractController
{
    public function __construct(private StudentClass $studentClass)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route("/user/{user}", name: "user")]
    public function index(User $user): Response
    {
        $this->enableModule('user-edit');
        $superadmin = $this->isGranted('ROLE_SUPERADMIN');
        $restorableRole = $this->getRestorableRole($user, $superadmin);
        $userMayBeSuperadmin = $user->getEffectiveRole() === 'ROLE_SUPERADMIN' ||
            $user->getRestorableRole() === 'ROLE_SUPERADMIN';
        $options = [
            "superadmin" => $superadmin || $userMayBeSuperadmin,
            "default_role" => $user->getOriginalRole()
        ];
        return $this->form(UserRolesType::class, $user, $options)
            ->action("Uložit", function (User $user) use ($restorableRole) {
                if ($user->getEffectiveRole() !== 'ROLE_STUDENT') {
                    $user->setEffectiveStudentClass(null);
                } else {
                    $studentClass = $user->getEffectiveStudentClass();
                    if ($studentClass !== null) {
                        $studentClass = $this->studentClass->normalizeStudentClass($studentClass);
                    }
                    if ($studentClass !== null) {
                        $user->setEffectiveStudentClass($studentClass);
                    }
                }
                $user->setRestorableRole($restorableRole);
                $this->getEntityManager()->flush();
                return $this->redirectBack(true);
            })
            ->action("Zrušit", function (User $user) {
                return $this->redirectBack(true);
            }, type: 'btn-secondary', validated: false)
            ->caption(sprintf("nastavit roli uživateli \"%s\"", $user->getName()))
            ->handle()
        ;
    }

    private function getRestorableRole(User $user, bool $superadmin): ?string
    {
        $userMaxRole = $user->getRealRole();
        if ($user->getRestorableRole() !== null) {
            $userMaxRole = RoleComparator::max($userMaxRole, $user->getRestorableRole());
        }
        
        if (($user->getRealRole() === 'ROLE_SUPERADMIN' && !$superadmin) || $user === $this->getUser()->getUserData()) {
            return $userMaxRole;
        }

        return null;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("users");
    }
}
