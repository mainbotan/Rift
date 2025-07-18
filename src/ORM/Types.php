<?php

namespace Rift\Core\ORM;

class Types
{
    // ********************** BASE TYPES **********************
    public const SERIAL = 'SERIAL';
    public const BIGSERIAL = 'BIGSERIAL';
    public const UUID = 'UUID';
    public const BOOLEAN = 'BOOLEAN';
    public const TEXT = 'TEXT';
    public const XML = 'XML';
    public const JSON = 'JSON';
    public const JSONB = 'JSONB';
    public const DATE = 'DATE';
    public const TIME = 'TIME';
    public const TIMESTAMP = 'TIMESTAMP';
    public const TIMESTAMPTZ = 'TIMESTAMP WITH TIME ZONE';
    public const INTERVAL = 'INTERVAL';
    public const MONEY = 'MONEY';
    public const BYTEA = 'BYTEA';
    public const TSVECTOR = 'TSVECTOR';
    public const UUID_ARRAY = 'UUID[]';
    public const TEXT_ARRAY = 'TEXT[]';
    public const INT_ARRAY = 'INTEGER[]';
    public const JSON_ARRAY = 'JSON[]';

    // ********************** NUMERIC TYPES **********************
    public const SMALLINT = 'SMALLINT';
    public const INTEGER = 'INTEGER';
    public const BIGINT = 'BIGINT';
    public const DECIMAL = 'DECIMAL(%d, %d)';
    public const NUMERIC = 'NUMERIC(%d, %d)';
    public const REAL = 'REAL';
    public const FLOAT = 'FLOAT';
    public const DOUBLE = 'DOUBLE PRECISION';
    public const SERIAL_PRIMARY = 'SERIAL PRIMARY KEY';
    public const BIGSERIAL_PRIMARY = 'BIGSERIAL PRIMARY KEY';

    // ********************** STRING TYPES **********************
    public const CHAR = 'CHAR(%d)';
    public const VARCHAR = 'VARCHAR(%d)';
    public const CITEXT = 'CITEXT';
    public const ENUM = 'ENUM(%s)';
    public const INET = 'INET';
    public const CIDR = 'CIDR';
    public const MACADDR = 'MACADDR';
    public const MACADDR8 = 'MACADDR8';
    public const BIT = 'BIT(%d)';
    public const VARBIT = 'VARBIT(%d)';

    // ********************** GEOMETRIC TYPES **********************
    public const POINT = 'POINT';
    public const LINE = 'LINE';
    public const LSEG = 'LSEG';
    public const BOX = 'BOX';
    public const PATH = 'PATH';
    public const POLYGON = 'POLYGON';
    public const CIRCLE = 'CIRCLE';

    // ********************** SPECIAL TYPES WITH DEFAULTS **********************
    public const UUID_DEFAULT = 'UUID DEFAULT gen_random_uuid()';
    public const UUID_PK = 'UUID PRIMARY KEY DEFAULT gen_random_uuid()';
    public const TIMESTAMP_DEFAULT = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
    public const TIMESTAMP_UPDATE = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
    public const TIMESTAMPTZ_DEFAULT = 'TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP';
    public const TIMESTAMPTZ_UPDATE = 'TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
    public const CREATED_AT = 'TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP';
    public const UPDATED_AT = 'TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

    // ********************** TYPE GENERATORS **********************

    /**
     * String types
     */
    public static function varchar(int $length = 255): string
    {
        return sprintf(self::VARCHAR, $length);
    }

    public static function char(int $length = 36): string
    {
        return sprintf(self::CHAR, $length);
    }

    public static function bit(int $length = 1): string
    {
        return sprintf(self::BIT, $length);
    }

    public static function varbit(int $length = 50): string
    {
        return sprintf(self::VARBIT, $length);
    }

    /**
     * Numeric types
     */
    public static function decimal(int $precision = 10, int $scale = 2): string
    {
        return sprintf(self::DECIMAL, $precision, $scale);
    }

    public static function numeric(int $precision = 10, int $scale = 2): string
    {
        return sprintf(self::NUMERIC, $precision, $scale);
    }

    public static function money(int $precision = 19, int $scale = 4): string
    {
        return self::decimal($precision, $scale);
    }

    /**
     * Enum type (MySQL/PG implementation)
     */
    public static function enum(array $values): string
    {
        $quoted = array_map(fn($v) => "'".addslashes($v)."'", $values);
        return sprintf(self::ENUM, implode(',', $quoted));
    }

    /**
     * Primary keys
     */
    public static function id(string $type = 'serial'): string
    {
        return match(strtolower($type)) {
            'big', 'bigserial' => self::BIGSERIAL_PRIMARY,
            'uuid' => self::UUID_PK,
            default => self::SERIAL_PRIMARY,
        };
    }

    /**
     * Foreign keys
     */
    public static function foreignKey(string $type = 'integer'): string
    {
        return match(strtolower($type)) {
            'big', 'bigint' => self::BIGINT,
            'uuid' => self::UUID,
            'small' => self::SMALLINT,
            default => self::INTEGER,
        };
    }

    /**
     * Network types
     */
    public static function ipAddress(): string
    {
        return self::INET;
    }

    public static function macAddress(bool $extended = false): string
    {
        return $extended ? self::MACADDR8 : self::MACADDR;
    }

    // ********************** VALIDATION METHODS **********************

    public static function validateType(string $type, $value): bool
    {
        return match(true) {
            str_starts_with($type, 'VARCHAR') => is_string($value),
            $type === self::UUID => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value),
            $type === self::INET => filter_var($value, FILTER_VALIDATE_IP),
            $type === self::CIDR => self::validateCidr($value),
            $type === self::JSON => json_validate($value),
            default => true,
        };
    }

    private static function validateCidr(string $cidr): bool
    {
        // CIDR validation logic
        return (bool)preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(\/([0-9]|[1-2][0-9]|3[0-2]))?$/', $cidr);
    }

    // ********************** DATABASE-SPECIFIC TYPES **********************

    /**
     * MySQL specific types
     */
    public static function mysqlEnum(array $values): string
    {
        return self::enum($values);
    }

    public static function mysqlSet(array $values): string
    {
        $quoted = array_map(fn($v) => "'".addslashes($v)."'", $values);
        return sprintf('SET(%s)', implode(',', $quoted));
    }

    /**
     * PostgreSQL specific types
     */
    public static function pgRange(string $subtype): string
    {
        return $subtype.'RANGE';
    }

    public static function pgHstore(): string
    {
        return 'HSTORE';
    }

    public static function pgLtree(): string
    {
        return 'LTREE';
    }

    // ********************** SPATIAL TYPES (PostGIS) **********************
    public static function geometry(string $type = 'GEOMETRY', int $srid = null): string
    {
        return $srid ? "GEOMETRY($type,$srid)" : "GEOMETRY($type)";
    }

    public static function geography(string $type = 'GEOMETRY', int $srid = 4326): string
    {
        return "GEOGRAPHY($type,$srid)";
    }
}