<?php

class Environment {
    
    const DEV = 0;
    const TEST = 1;
    const PROD = 2;

    private static $_current = self::DEV;

    public static function set(int $env) {
        $res = array_search($env, (new ReflectionClass(__CLASS__))->getConstants(), true);
        if ($res === false) {
            throw new InvalidArgumentException('The environment cannot hold such value.');
        }
        self::$_current = $env;
    }

    public static function get(): int {
        return self::$_current;
    }

    public static function isDev(): bool {
        return self::$_current === self::DEV;
    }

    public static function isTest(): bool {
        return self::$_current === self::TEST;
    }

    public static function isProd(): bool {
        return self::$_current === self::PROD;
    }

}