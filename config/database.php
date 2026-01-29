<?php

class DB {
    // ----------------------------------------------------------------
    // SMW Database Connection (for sys_users)
    // ----------------------------------------------------------------
    private static $hostSmw      = "192.168.50.7";
    private static $portSmw      = "5432";
    private static $dbnameSmw    = "smw";
    private static $userSmw      = "smw";
    private static $passSmw      = "SmwReporting1234!";

    private static $connSmw  = null;

    // ---------------------------------------------------------------
    // SMW Connection
    // ---------------------------------------------------------------
    public static function getConnSmw() {
        if (self::$connSmw === null) {
            if (!function_exists('pg_connect')) {
                throw new \Exception("Extensia PHP 'pgsql' nu este activă. Te rog activează extension=pgsql în php.ini și repornește serverul.");
            }
            $connectionString = sprintf(
                "host=%s port=%s dbname=%s user=%s password=%s",
                self::$hostSmw,
                self::$portSmw,
                self::$dbnameSmw,
                self::$userSmw,
                self::$passSmw
            );
            $conn = @pg_connect($connectionString);
            if (!$conn) {
                throw new \Exception("Conexiunea la DB SMW a eșuat: " . print_r(error_get_last(), true));
            }
            self::$connSmw = $conn;
        }
        return self::$connSmw;
    }

    public static function closeConnSmw() {
        if (self::$connSmw !== null) {
            pg_close(self::$connSmw);
            self::$connSmw = null;
        }
    }
}
