<?php namespace Uepg\LaravelSybase\Database\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class SybaseGrammar extends Grammar {

	/**
	 * All of the available clause operators.
	 *
	 * @var array
	 */
        protected $operators = array(
		'=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
		'like', 'not like', 'between', 'ilike',
		'&', '&=', '|', '|=', '^', '^=',
	);
        
        protected $Builder;
        public function getBuilder(){
            return $this->Builder;
        }
        

        /**
	 * Compile a select query into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder
	 * @return string
	 */
        
	public function compileSelect(Builder $query)
	{
                $this->Builder = $query;
		$components = $this->compileComponents($query);

		return $this->concatenate($components);
	}
	/**
	 * Compile the "select *" portion of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $columns
	 * @return string
	 */
	protected function compileColumns(Builder $query, $columns)
	{
		if ( ! is_null($query->aggregate)) return;

		$select = $query->distinct ? 'select distinct ' : 'select ';

		// If there is a limit on the query, but not an offset, we will add the top
		// clause to the query, which serves as a "limit" type clause within the
		// SQL Server system similar to the limit keywords available in MySQL.
		if ($query->limit > 0 && $query->offset <= 0)
		{
			$select .= 'top '.$query->limit.' ';
		}

		return $select.$this->columnize($columns);
	}

	/**
	 * Compile the "from" portion of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  string  $table
	 * @return string
	 */
	protected function compileFrom(Builder $query, $table)
	{
		$from = parent::compileFrom($query, $table);

		if (is_string($query->lock)) return $from.' '.$query->lock;

		if ( ! is_null($query->lock))
		{
			return $from.' with(rowlock,'.($query->lock ? 'updlock,' : '').'holdlock)';
		}

		return $from;
	}
	
	/**
	 * Compile the "limit" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $limit
	 * @return string
	 */
	protected function compileLimit(Builder $query, $limit)
	{
		return '';
	}

	/**
	 * Compile the "offset" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $offset
	 * @return string
	 */
	protected function compileOffset(Builder $query, $offset)
	{
		return '';
	}

	/**
	 * Compile a truncate table statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return array
	 */
	public function compileTruncate(Builder $query)
	{
		return array('truncate table '.$this->wrapTable($query->from) => array());
	}

	/**
	 * Get the format for database stored dates.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return 'Y-m-d H:i:s.000';
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrapValue($value)
	{
		if ($value === '*') return $value;

		return '['.str_replace(']', ']]', $value).']';
	}

}
