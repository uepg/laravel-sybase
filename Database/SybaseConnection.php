<?php namespace Uepg\LaravelSybase\Database;

use Closure;
use Exception;
use Doctrine\DBAL\Driver\PDOSqlsrv\Driver as DoctrineDriver;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Mainginski\SybaseEloquent\Database\Query\SybaseGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as SchemaGrammar;
use Illuminate\Database\Connection;

class SybaseConnection extends Connection {
                
    
        // All types without quotes in Sybase's query
        private $without_quotes = ['int' , 'numeric', 'bigint', 'integer' , 'smallint', 'tinyint', 'decimal', 'double', 'float', 'real', 'bit', 'binary', 'varbinary', 'timestamp'];
	/**
	 * Execute a Closure within a transaction.
	 *
	 * @param  \Closure  $callback
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function transaction(Closure $callback)
	{
		if ($this->getDriverName() == 'sqlsrv')
		{
			return parent::transaction($callback);
		}

		$this->pdo->exec('BEGIN TRAN');

		// We'll simply execute the given callback within a try / catch block
		// and if we catch any exception we can rollback the transaction
		// so that none of the changes are persisted to the database.
		try
		{
			$result = $callback($this);

			$this->pdo->exec('COMMIT TRAN');
		}

		// If we catch an exception, we will roll back so nothing gets messed
		// up in the database. Then we'll re-throw the exception so it can
		// be handled how the developer sees fit for their applications.
		catch (Exception $e)
		{
			$this->pdo->exec('ROLLBACK TRAN');

			throw $e;
		}

		return $result;
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \Illuminate\Database\Query\Grammars\SqlServerGrammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new QueryGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return \Illuminate\Database\Schema\Grammars\SqlServerGrammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new SchemaGrammar);
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return \Illuminate\Database\Query\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new SqlServerProcessor;
	}

	/**
	 * Get the Doctrine DBAL Driver.
	 *
	 * @return \Doctrine\DBAL\Driver\PDOSqlsrv\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new DoctrineDriver;
	}

        
        /**
         * Set new bindings with specified column types to Sybase
         * 
         * @param  string  $query
	 * @param  array   $bindings
         * @return mixed   $new_binds
         */
        private function compileBindings($query, $bindings)
        {
            if(count($bindings)<=0){
                return [];
            }
            
            $bindings = $this->prepareBindings($bindings);
            $new_format = [];
            
            // * Temporary
            $query_type = explode(' ', $query);
            
            switch($query_type[0]){
                case "select":
                    $tables = explode('from', $query);
                    $tables = explode('where', $tables[1]);
                break;
                case "insert":
                    $tables = explode('(', $query, 2);
                    $tables = explode('values', $tables[0]);
                break;
                case "update":
                     $tables = explode('set', $query);
                break;
                case "delete":
                    $tables = explode('where', $query);
                break;
            }
            unset($query_type);
            // Temporary *
            
            $tables = $tables[0];
			
            preg_match_all("/\[([^\]]*)\]/", $query, $arrQuery);
            if(count($arrQuery[1]) == 0){
                return $bindings;
            }
            preg_match_all("/\[([^\]]*)\]/", $tables, $arrTables);
            $arrQuery = $arrQuery[1];
            $arrTables = $arrTables[1];
            
            $ind = 0;
            foreach($arrQuery as $campos){
                if(in_array($campos, $arrTables)){
                    $table = $campos;
                    if(!array_key_exists($campos, $new_format)){
                        $queryRes = $this->getPdo()->query("select b.name, c.name AS type from sysobjects a noholdlock JOIN syscolumns b noholdlock ON  a.id = b.id JOIN systypes c noholdlock ON b.usertype = c.usertype and a.name = '".$campos."'");
                        $types[$campos] = $queryRes->fetchAll(\PDO::FETCH_ASSOC); //Pega os campos e seus tipos
                        for($k = 0; $k < count($types[$campos]); $k++){
                            $types[$campos][$types[$campos][$k]['name']] = $types[$campos][$k];
                            unset($types[$campos][$k]);
                        }
                        $new_format[$campos] = [];
                    }
                }else{
                    if(count($bindings)>$ind){
                        array_push($new_format[$table], ['campo' => $campos, 'binding' => $ind]);
                        if(in_array(strtolower($types[$table][$campos]['type']), $this->without_quotes)){
                            $new_binds[$ind] = $bindings[$ind]/1;
                        }else{
                            $new_binds[$ind] = (string)$bindings[$ind];
                        }
                    }else{
                        array_push($new_format[$table], ['campo' => $campos]);
                    }
                    $ind++;
                }
            }

                        
            return $new_binds;
	}
        /**
         * Set new bindings with specified column types to Sybase
         * 
         * @param  string  $query
	 * @param  array  $bindings
         * @return string $query
        */
        // Poderia compilar novamente dos bindings usando os PDO::PARAM, porém, não tem nenhuma constante que lide
        // com decimais, logo, a única maneira seria colocando PDO::PARAM_STR, que colocaria plicas.
        // Detalhes: http://stackoverflow.com/questions/2718628/pdoparam-for-type-decimal
        private function compileNewQuery($query, $bindings)
        {
            $bindings = $this->compileBindings($query, $bindings);

            $newQuery = ""; 
            $partQuery = explode("?", $query);
            for($i = 0; $i<count($partQuery); $i++){
                    $newQuery .= $partQuery[$i];
                    if($i<count($bindings)){
                        if(is_string($bindings[$i])){
                            $newQuery .= "'".$bindings[$i]."'";
                        }else{
                            $newQuery .= $bindings[$i];
                        }
                    }
            }
			
            return $newQuery;    
        }
        
        /**
	 * Run a select statement against the database.
	 *
	 * @param  string  $query
	 * @param  array  $bindings
	 * @param  bool  $useReadPdo
	 * @return array
	*/
	public function select($query, $bindings = array(), $useReadPdo = true)
	{
		return $this->run($query, $bindings, function($me, $query, $bindings) use ($useReadPdo)
		{
                    if ($me->pretending()) return array();
                    return $this->getPdo()->query($this->compileNewQuery($query, $bindings))->fetchAll($me->getFetchMode());
		});
	}
        
        /** 
        * @param  string  $query
        * @param  mixed array   $bindings
        * @return bool
        */
        public function statement($query, $bindings = array()) {
            
            return $this->run($query, $bindings, function($me, $query, $bindings)
            {
                if ($me->pretending()) return true;
                return $this->getPdo()->query($this->compileNewQuery($query, $bindings));
            });
        }

        public function affectingStatement($query, $bindings = array())
        {   
            return $this->run($query, $bindings, function($me, $query, $bindings)
            {
                if ($me->pretending()) return 0;
                return $this->getPdo()->query($this->compileNewQuery($query, $bindings))->rowCount();
            });
        }
}
