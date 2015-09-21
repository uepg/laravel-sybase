<?php
    namespace Mainginski\SybaseEloquent\Database;
    
    use Illuminate\Database\Connection as BaseConnection;
    
    class Connection extends BaseConnection {
        
        // https://bugs.php.net/bug.php?id=57655
        // 
        //Coloque aqui todos os tipos que não levam plicas
        private $without_quotes = ['int', 'bigint', 'integer', 'smallint', 'tinyint', 'decimal', 'double', 'float', 'real', 'bit']; 
        
        /**
         * Set new bindings with specified column types to Sybase
         * 
         * @param  string  $query
	 * @param  array   $bindings
         * @return mixed   $new_binds
         */
        private function new_bindings($query, $bindings)
        {
            $bindings = $this->prepareBindings($bindings);
            
            $text_inside = [];
            $new_binds   = [];
            
            preg_match_all("/\[([^\]]*)\]/", $query, $text_inside);

            $queryRes = $this->getPdo()->query("select b.name, c.name AS type from sysobjects a noholdlock JOIN syscolumns b noholdlock ON  a.id = b.id JOIN systypes c noholdlock ON b.usertype = c.usertype and a.name = '".$text_inside[1][0]."'");
            $res = $queryRes->fetchAll(); //Pega os campos e seus tipos

            $i = 0;
            foreach($text_inside[1] as $ind=>$bind){
                foreach($res as $campo) {
                    if($bind == $campo['name']) {    
                        if(in_array($campo['type'], $this->without_quotes)){
                                $new_binds[$i] = (int)$bindings[$i];
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
        private function mount_new_query($query, $bindings){
            $bindings = $this->new_bindings($query, $bindings);

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

                        return $this->getPdo()->query($this->mount_new_query($query, $bindings))->fetchAll($me->getFetchMode());
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
                           return $this->getPdo()->query($this->mount_new_query($query, $bindings));
            });
        }

        public function affectingStatement($query, $bindings = array())
        {   

            return $this->run($query, $bindings, function($me, $query, $bindings)
            {
                    if ($me->pretending()) return 0;


                    return $this->getPdo()->query($this->mount_new_query($query, $bindings))->rowCount();
            });
        }
        
    }