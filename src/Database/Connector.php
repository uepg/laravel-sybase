<?php

namespace Uepg\LaravelSybase\Database;

use Illuminate\Database\Connectors\SqlServerConnector;

class Connector extends SqlServerConnector
{
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        $connection = $this->createConnection($this->getDsn($config), $config, $options);

        if(array_key_exists('charset', $config) && $config['charset'] != '') {
            $connection->prepare("set char_convert '{$config['charset']}'")->execute();
        }

        return $connection;
    }
}
