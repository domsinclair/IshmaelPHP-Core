<?php
    declare(strict_types=1);

    namespace Ishmael\Core\DatabaseAdapters;

    use PDO;

    interface DatabaseAdapterInterface
    {
        public function connect(array $config): PDO;
    }
