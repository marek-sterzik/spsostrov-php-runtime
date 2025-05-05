<?php

namespace App\Framework;

use DateTimeImmutable;
use SPSOstrov\SSOBundle\SSOUserDataProviderInterface;
use SPSOstrov\SSOBundle\SSORoleDeciderInterface;
use SPSOstrov\SSOBundle\SSOUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Entity\User;
use App\StudentClass\StudentClass;

class UserDataProvider implements SSOUserDataProviderInterface, SSORoleDeciderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManager $entityManager,
        private StudentClass $studentClass,
    ) {
    }

    public function getUserData(SSOUser $user): mixed
    {
        $loginAt = (new DateTimeImmutable())->setTimestamp($user->getLoginTimestamp());
        $userEntity = $this->userRepository->findOneBy(["username" => $user->getLogin()]);
        $changed = false;
        if ($userEntity === null) {
            $firstUser = ($this->userRepository->countUsers() === 0) ? true : false;
            $userEntity = new User($user->getLogin());
            $this->entityManager->persist($userEntity);
            if ($firstUser) {
                $userEntity->setEffectiveRole('ROLE_SUPERADMIN');
            }
            $changed = true;
            $update = true;
        } else {
            $update = ($userEntity->getLastLoginAt() === null || $userEntity->getLastLoginAt() <= $loginAt);
        }

        if ($update) {
            if ($userEntity->getLastLoginAt()?->getTimestamp() !== $loginAt->getTimestamp()) {
                $userEntity->setLastLoginAt($loginAt);
                $changed = true;
            }
            if ($userEntity->getName() !== $user->getName()) {
                $userEntity->setName($user->getName());
                $changed = true;
            }
            
            $originalRole = $user->isTeacher() ? 'ROLE_TEACHER' : ($user->isStudent() ? 'ROLE_STUDENT' : 'ROLE_OTHER');
            if ($userEntity->getOriginalRole() !== $originalRole) {
                $userEntity->setOriginalRole($originalRole);
                $changed = true;
            }

            
            $studentClass = ($originalRole === 'ROLE_STUDENT') ? $this->getStudentClass($user) : null;
            if ($userEntity->getOriginalStudentClass() !== $studentClass) {
                $userEntity->setOriginalStudentClass($studentClass);
                $changed = true;
            }
        }

        if ($changed) {
            $this->entityManager->flush();
        }

        return $userEntity;
    }

    private function getStudentClass(SSOUser $user): ?string
    {
        $studentClass = $user->getClass();
        if ($studentClass !== null) {
            $studentClass = $this->studentClass->normalizeStudentClass($studentClass);
        }
        return $studentClass;
    }

    public function decideRoles(SSOUser $user): array
    {
        $userEntity = $user->getUserData();
        assert($userEntity instanceof User);

        return $userEntity->getFundamentalRoles();
    }
}
