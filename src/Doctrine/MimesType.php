<?php

namespace Akyos\UXFileManager\Doctrine;

use Akyos\UXFileManager\Enum\Mimes;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MimesType extends Type
{
    public const NAME = 'ux_filemanager_mime';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] ??= 255;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Mimes
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Mimes) {
            return $value;
        }

        return Mimes::coerce((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Mimes) {
            return $value->value;
        }

        return Mimes::coerce((string) $value)->value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
