<?php

namespace App\Database;


class Connection
{
  private static ?\PDO $pdo = null;

  public static function connect(bool $test = false): \PDO
  {
    if (self::$pdo === null) {
      $dbpath = $test ? '/database.test.sqlite' : '/database.sqlite';
      self::$pdo = new \PDO('sqlite:' . __DIR__ . $dbpath);
      self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      self::createTables();
    }
    return self::$pdo;
  }

  private static function createTables(): void
  {
    self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL
            )
        ");
  }
}
