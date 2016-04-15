<?php namespace Uepg\LaravelSybase\Database;
use Closure;
use Exception;
use Doctrine\DBAL\Driver\PDOSqlsrv\Driver as DoctrineDriver;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Uepg\LaravelSybase\Database\Query\SybaseGrammar as QueryGrammar;
use Uepg\LaravelSybase\Database\Schema\SybaseGrammar as SchemaGrammar;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
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
        private function compileForSelect(Builder $builder, $bindings) {
           
            
            
            $arrTables = [];
            array_push($arrTables, $builder->from);
            if(!empty($builder->joins)){
                foreach($builder->joins as $join){
                    
                    array_push($arrTables, $join->table);
                }
            }
            $new_format = [];
            foreach($arrTables as $tables){
                    preg_match("/(?:(?'table'.*)(?: as )(?'alias'.*))|(?'tables'.*)/", $tables, $alias);
                    if(empty($alias['alias'])){
                        $tables = $alias['tables'];
                    }else{
                        $tables = $alias['table'];
                    }
                    
                    $explicitDB = explode('..', $tables);
                    if(isset($explicitDB[1])){
                        $queryRes = $this->getPdo()->query("select b.name, c.name AS type from ".$explicitDB[0]."..sysobjects a noholdlock JOIN ".$explicitDB[0]."..syscolumns b noholdlock ON  a.id = b.id JOIN ".$explicitDB[0]."..systypes c noholdlock ON b.usertype = c.usertype and a.name = '".$explicitDB[1]."'");
                    }else{
                        $queryRes = $this->getPdo()->query("select a.name, b.name AS type FROM syscolumns a noholdlock JOIN systypes b noholdlock ON a.usertype = b.usertype and object_name(a.id) = '".$tables."'");
                    }
                    
                    $types[$tables] = $queryRes->fetchAll(\PDO::FETCH_NAMED); 
     
                    foreach ($types[$tables] as &$row) {
                        $tipos[$row['name']] = $row['type'];
                        $tipos[$tables.'.'.$row['name']] = $row['type'];
                        if(!empty($alias['alias'])){
                            $tipos[$alias['alias'].'.'.$row['name']] = $row['type'];
                        }
                    }
                    
                   $new_format[$tables] = [];
            }
            $wheres = (array)$builder->wheres;
            $i = 0;
            for($ind = 0; $ind < count($wheres); $ind++ ){
                if(isset($wheres[$ind]['value'])){
                    if(in_array(strtolower($tipos[$wheres[$ind]['column']]), $this->without_quotes)){
                        $new_binds[$i] = $bindings[$i]/1;
                    }else{
                        $new_binds[$i] = (string)$bindings[$i];
                    }
                    $i++;
                }
            }
            
            return $new_binds;
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
           
            if(count($bindings)==0){
                return [];
            }
            
            $bindings = $this->prepareBindings($bindings);
            $new_format = [];
            
            switch(explode(' ', $query)[0]){
                case "select":
                    $builder = $this->queryGrammar->getBuilder();
                    if($builder != NULL && $builder->wheres != NULL){
                        return $this->compileForSelect($builder, $bindings);
                    }else{
                        return $bindings;
                    }
                case "insert":
                    preg_match("/(?'tables'.*) \((?'attributes'.*)\) values/i" ,$query, $matches);
                break;
                case "update":
                    preg_match("/(?'tables'.*) set (?'attributes'.*)/i" ,$query, $matches);
                break;
                case "delete":
                    preg_match("/(?'tables'.*) where (?'attributes'.*)/i" ,$query, $matches);
                break;
            }
            
            $desQuery = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));
            
            if(is_array($desQuery['tables'])){
                $desQuery['tables'] = implode($desQuery['tables'], ' ');
            }
            if(is_array($desQuery['attributes'])){
                $desQuery['attributes'] = implode($desQuery['attributes'], ' ');
            }
            
            unset($matches);
            unset($query_type);
            preg_match_all("/\[([^\]]*)\]/", $desQuery['attributes'], $arrQuery);
            preg_match_all("/\[([^\]]*)\]/", $desQuery['tables'], $arrTables);
            
            $arrQuery = $arrQuery[1];
            $arrTables = $arrTables[1];
            $ind = 0;
            $numTables = count($arrTables);
            
            if($numTables == 1){
                $table = $arrTables[0];
            }else if($numTables == 0){
                return $bindings;
            }
            
            foreach($arrQuery as $key=>$campos){
                $itsTable = in_array($campos, $arrTables);
                
                if($itsTable || ($numTables  == 1 && isset($table) && $key == 0)){
                    if($numTables > 1){
                        $table = $campos;
                    }
                    if(!array_key_exists($table, $new_format)){
                        $queryRes = $this->getPdo()->query("select a.name, b.name AS type FROM syscolumns a noholdlock JOIN systypes b noholdlock ON a.usertype = b.usertype and object_name(a.id) = '".$table."'");
                        $types[$table] = $queryRes->fetchAll(\PDO::FETCH_ASSOC);
                        for($k = 0; $k < count($types[$table]); $k++){
                            $types[$table][$types[$table][$k]['name']] = $types[$table][$k];
                            unset($types[$table][$k]);
                        }
                        $new_format[$table] = [];
                    }
                }
                
                if(!$itsTable){
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
            $newQuery = ""; 
            $bindings = $this->compileBindings($query, $bindings);
            $partQuery = explode("?", $query);
            for($i = 0; $i<count($partQuery); $i++){
                    $newQuery .= $partQuery[$i];
                    if($i<count($bindings)){
                        if(is_string($bindings[$i])){
                            $bindings[$i] = str_replace( "'", "''", $bindings[$i] );
                            $newQuery .= "'".$bindings[$i]."'";
                        }else{
                            $newQuery .= $bindings[$i];
                        }
                    }
            }
            $newQuery = str_replace( "[]", '' ,$newQuery);
            return $newQuery;  
        }
        
        public function compileOffset($me){
           
                        $limit = $this->queryGrammar->getBuilder()->limit;
                        $from = explode(" ", $this->queryGrammar->getBuilder()->from)[0];  
                        if(!isset($limit)){
                            $limit = 999999999999999999999999999;
                        }
                        $indentity = $this->getPdo()->query("select name as 'column' from syscolumns where status & 128 = 128 AND object_name(id)='".$from."'")->fetchAll($me->getFetchMode())[0];
                        
                        if(count($indentity) == 0){
                            $primaries = $this->getPdo()->query("SELECT index_col(object_name(i.id), i.indid, c.colid) AS primary_key FROM sysindexes i, syscolumns c WHERE i.id = c.id AND c.colid <= i.keycnt AND i.id = object_id('".$from."')")->fetchAll($me->getFetchMode());
                            foreach($primaries as $primary)
                            {
                                $new_arr[] = $primary->primary_key.'+0 AS '.$primary->primary_key;
                                $where_arr[] = "#tmpPaginate.".$primary->primary_key.' = #tmpTable.'.$primary->primary_key;
                            }
                            $res_primaries = implode(', ',$new_arr);
                            $where_primaries = implode(' AND ',$where_arr);
                        }else{
                            $res_primaries = $indentity->column.'+0 AS '.$indentity->column;
                            $where_primaries = "#tmpPaginate.".$indentity->column.' = #tmpTable.'.$indentity->column;
                        }
                        
                        //Offset operation
                        $this->getPdo()->query(str_replace(" from ", " into #tmpPaginate from ", $this->compileNewQuery($query, $bindings)));
                        $this->getPdo()->query("SELECT ".$res_primaries.", idTmp=identity(18) INTO #tmpTable FROM #tmpPaginate");
                        return $this->getPdo()->query("SELECT  #tmpPaginate.*, #tmpTable.idTmp FROM #tmpTable INNER JOIN #tmpPaginate ON ".$where_primaries." WHERE #tmpTable.idTmp "
                                . "BETWEEN ".($offset+1) ." AND ". ($offset+$limit) 
                                ." ORDER BY #tmpTable.idTmp ASC")->fetchAll($me->getFetchMode());
                    
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
                    
                    if($this->queryGrammar->getBuilder() != NULL){
                        $offset = $this->queryGrammar->getBuilder()->offset;
                    }else{
                        $offset = 0;
                    }
                    
                    if($offset>0){
                        return $this->compileOffset($me);
                    }else{  
                        return $this->getPdo()->query($this->compileNewQuery($query, $bindings))->fetchAll($me->getFetchMode());  
                    }
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
