<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function findByKey(string $key): ?Setting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.key = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getValue(string $key, string $default = null): ?string
    {
        $setting = $this->findByKey($key);
        return $setting ? $setting->getValue() : $default;
    }

    public function setBooleanValue(string $key, bool $value, string $description = null): void
    {
        $this->setValue($key, $value ? '1' : '0', $description);
    }

    public function setValue(string $key, string $value, string $description = null): void
    {
        $setting = $this->findByKey($key);
        
        if (!$setting) {
            $setting = new Setting();
            $setting->setKey($key);
            if ($description) {
                $setting->setDescription($description);
            }
            $this->getEntityManager()->persist($setting);
        }
        
        $setting->setValue($value);
        $this->getEntityManager()->flush();
    }

    public function getBooleanValue(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key);
        return $value !== null ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $default;
    }

    public function getIntegerValue(string $key, int $default = 0): int
    {
        $value = $this->getValue($key);
        return $value !== null ? (int) $value : $default;
    }

    public function getFloatValue(string $key, float $default = 0.0): float
    {
        $value = $this->getValue($key);
        return $value !== null ? (float) $value : $default;
    }

    public function getArrayValue(string $key, array $default = []): array
    {
        $value = $this->getValue($key);
        return $value !== null ? json_decode($value, true) ?? $default : $default;
    }
} 