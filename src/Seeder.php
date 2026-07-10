<?php

namespace Ace;

abstract class Seeder
{
    /**
     * @var \PDO The database connection
     */
    protected \PDO $db;

    /**
     * Seeder constructor.
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run the database seeds.
     */
    abstract public function run(): void;

    /**
     * Run another seeder class.
     *
     * @param string $seederClass Fully qualified seeder class name
     */
    protected function call(string $seederClass): void
    {
        if (class_exists($seederClass)) {
            $seeder = new $seederClass($this->db);
            $seeder->run();
        } else {
            Logger::error("Seeder class '{$seederClass}' not found.");
        }
    }
}
