<?php

namespace App\Lock;

use Exception;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

class LockManager
{
    private static array $perThreadLocks = [];

    private EntityManager $entityManager;
    private ResultSetMapping $rsm;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->rsm = new ResultSetMapping();
        $this->rsm->addScalarResult('x', 'x', 'integer');
    }

    public function lock(string $lockId): void
    {
        if (!isset(self::$perThreadLocks[$lockId])) {
            self::$perThreadLocks[$lockId] = 0;
        }

        if (self::$perThreadLocks[$lockId] == 0) {
            $query = $this->entityManager->createNativeQuery('SELECT GET_LOCK(?, ?) AS x', $this->rsm);
            $query->setParameter(1, $this->getLockName($lockId));
            $query->setParameter(2, 0xffffff);

            $result = (int)$query->getSingleScalarResult();

            if (!$result) {
                throw new Exception(sprintf('Cannot acquire lock %s', $this->getLockName($lockId)));
            }
        }

        self::$perThreadLocks[$lockId]++;
    }

    public function unlock(string $lockId): void
    {
        if (!isset(self::$perThreadLocks[$lockId]) || self::$perThreadLocks[$lockId] <= 0) {
            throw new Exception(
                sprintf('Trying to release a lock which was not acquired: %s', $this->getLockName($lockId))
            );
        }
        self::$perThreadLocks[$lockId]--;
        if (self::$perThreadLocks[$lockId] <= 0) {
            unset(self::$perThreadLocks[$lockId]);
            $query = $this->entityManager->createNativeQuery('SELECT RELEASE_LOCK(?) AS x', $this->rsm);
            $query->setParameter(1, $this->getLockName($lockId));
            $query->getSingleScalarResult();
        }
    }

    private function getLockName(string $lockId): string
    {
        if (strlen($lockId) < 64) {
            return $lockId;
        }
        return sha1($lockId);
    }
}
