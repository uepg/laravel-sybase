<?php
    namespace Mainginski\SybaseEloquent\Database;
    
    use Illuminate\Database\Connection as BaseConnection;
    
    class Connection extends BaseConnection {
        
	// All types without quotes in Sybase's query
        private $without_quotes = ['int' , 'numeric', 'bigint', 'integer' , 'smallint', 'tinyint', 'decimal', 'double', 'float', 'real', 'bit', 'binary', 'varbinary', 'timestamp'];
        /**
         * Set new bindings with specified column types to Sybase
         * 
         * @param  string  $query
	 * @param  array   $bindings
         * @return mixed   $new_binds
         */
        private function compileBindings($query, $bindings)
        {
            $bindings = $this->prepareBindings($bindings);
            
            $text_inside = [];
            $new_binds   = [];
            
            preg_match_all("/\[([^\]]*)\]/", $query, $text_inside);

            $queryRes = $this->getPdo()->query("select b.name, c.name AS type from sysobjects a noholdlock JOIN syscolumns b noholdlock ON  a.id = b.id JOIN systypes c noholdlock ON b.usertype = c.usertype and a.name = '".$text_inside[1][0]."'");
            $res = $queryRes->fetchAll(); //Pega os campos e seus tipos
            $i = 0;
            foreach($text_inside[1] as $bind){
                foreach($res as $campo) {
                    if($bind == $campo['name'] && $i<count($bindings)) {    
                        if(in_array(strtolower($campo['type']), $this->without_quotes)){
                            $new_binds[$i] = $bindings[$i]/1;
                        }else{
                            $new_binds[$i] = (string)$bindings[$i];
                        }
                        $i++;
                        break;
                    }
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
        
        /*
        * O PDO emperra com prepared statments, por isso, a query precisa ser criada
        * de uma forma rústica
        * 
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