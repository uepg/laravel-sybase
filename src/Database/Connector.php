<?php

namespace Uepg\LaravelSybase\Database;

use Illuminate\Database\Connectors\SqlServerConnector;

class Connector extends SqlServerConnector
{
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        $connection = $this->createConnection($this->getDsn($config), $config, $options);

        if(isset($config['charset'])) {
            $connection->prepare("set char_convert '{$config['charset']}'")->execute();
        }

        if (isset($config['isolation_level'])) {
            $connection->prepare(
                "SET TRANSACTION ISOLATION LEVEL {$config['isolation_level']}"
            )->execute();
        }

        return $connection;
    }
}
