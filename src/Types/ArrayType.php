<?php

declare(strict_types = 1);

namespace DBALClickHouse\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use function array_key_exists;
use function sprintf;
use function strtolower;

abstract class ArrayType extends Type implements ClickHouseType
{
    protected const ARRAY_TYPES = [
        'array(int8)'     => ArrayInt8Type::class,
        'array(int16)'    => ArrayInt16Type::class,
        'array(int32)'    => ArrayInt32Type::class,
        'array(int64)'    => ArrayInt64Type::class,
        'array(uint8)'    => ArrayUInt8Type::class,
        'array(uint16)'   => ArrayUInt16Type::class,
        'array(uint32)'   => ArrayUInt32Type::class,
        'array(uint64)'   => ArrayUInt64Type::class,
        'array(float32)'  => ArrayFloat32Type::class,
        'array(float64)'  => ArrayFloat64Type::class,
        'array(string)'   => ArrayStringType::class,
        'array(datetime)' => ArrayDateTimeType::class,
        'array(date)'     => ArrayDateType::class,
    ];

    /**
     * Register Array types to the type map.
     */
    public static function registerArrayTypes(AbstractPlatform $platform): void
    {
        foreach (self::ARRAY_TYPES as $typeName => $className) {
            if (self::hasType($typeName)) {
                continue;
            }

            self::addType($typeName, $className);
            foreach (Type::getType($typeName)->getMappedDatabaseTypes($platform) as $dbType) {
                $platform->registerDoctrineTypeMapping($dbType, $typeName);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [$this->getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $this->getDeclaration($fieldDeclaration);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return strtolower($this->getDeclaration());
    }

    /**
     * @param mixed[] $fieldDeclaration
     */
    protected function getDeclaration(array $fieldDeclaration = []): string
    {
        return sprintf(
            array_key_exists(
                'notnull',
                $fieldDeclaration
            ) && false === $fieldDeclaration['notnull'] ? 'Array(Nullable(%s%s%s))' : 'Array(%s%s%s)',
            $this instanceof UnsignedNumericalClickHouseType ? 'U' : '',
            $this->getBaseClickHouseType(),
            $this instanceof BitNumericalClickHouseType ? $this->getBits() : ''
        );
    }
}
