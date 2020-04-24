<?php

/**
* QueryBuilder - Biblioteca para conexão com banco de dados, facilitar a construção de querys ao mesmo tempo que melhora a legibilidade do código fazendo uso do Design Pattern Fluent Interface. Baseado no Eloquent do Laravel.
*
* @author Tieysson Cardoso Silva <tieysson.cardoso.silva@gmail.com>
* @version 0.1
*/

class QueryBuilder extends PDO
{
	/** @var array $buffer Buffer que guarda informações e partes de códigos que são usadas durante a construção da query, age como uma memória */
	protected $buffer = [];
	

	/**
	* Função construtora da classe
	*
	* Faz conexão com banco de dados. Para cada banco deve ser usada uma nova instância
	*
	* @param string $host O Host usado
	* @param string $db_name O nome do banco usado para essa instância
	* @param string $user Usuário do banco de dados
	* @param string $password Senha do usuário
	*
	* @return void
	*/
	public function __construct($host, $db_name, $user, $password)
	{
		try
		{
			parent::__construct("mysql:host={$host}; dbname={$db_name}; charset=utf8", $user, $password);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $error)
		{
			$this->error($error->getMessage());
		}
	}


	/**
	* Função para mostrar erros de conexão, sintax ou construção da query de forma personalizada
	*
	* @param string $error_message Mensagem que especifíca e ilustra o erro
	*
	* @return void
	*/
	private function error($error_message)
	{
		exit("<pre><span style='color: red;'>QueryBuilder - Database Error:</span> {$error_message}</pre>");
	}


	/**
	* Função de manipulação do Buffer
	*
	* Atribui um espaço no Buffer da query com o identificador e o valor especificados nos parametros da função
	*
	* @param string $key Identificador. É o 'endereço' usado para encontrar determinado valor, geralmente um pedaço de código de uma query
	* @param string $value Valor atribuido ao identificador
	*
	* @return void
	*/
	private function set_buffer($key, $value)
	{
		$this->buffer[$key] = $value;
	}


	/**
	* Função acessora do Buffer
	*
	* Acessa o index, passado pelo parametro, do Buffer
	*
	* @param string $key Nome do identificador que se deseja ter acesso
	*
	* @return string|bool
	*/
	private function get_buffer($key)
	{
		if(array_key_exists($key, $this->buffer))
		{
			return $this->buffer[$key];
		}else
		{
			return false;
		}
	}


	/**
	* Função para limpar o Buffer
	*
	* Após a contrução de uma query, o Buffer é limpo por esta função, a fim de estar preparado para uma nova contrução
	*
	* @return void
	*/
	private function clear_buffer()
	{
		$this->buffer = [];
	}


	/**
	* Constroi a query
	*
	* A partir do Buffer, a função junta os 'pedaços' de código para montar a query de acordo com o tipo especificado
	*
	* @return string
	*/
	private function build_query()
	{
		$query_type = $this->get_buffer('query_type');
		$table = $this->get_buffer('table');
		$columns = $this->get_buffer('columns');
		$where = $this->get_buffer('where');
		$order = $this->get_buffer('order');
		$group = $this->get_buffer('group');
		$having = $this->get_buffer('having');
		$limit = $this->get_buffer('limit');

		switch($query_type)
		{
			case 'select':
				$builded_query = "SELECT {$columns} FROM {$table} {$where} {$group} {$having} {$order} {$limit}";
				break;
			case 'insert':
				$columns = $this->get_buffer('insert_columns');
				$values = $this->get_buffer('insert_values');

				$builded_query = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
				break;
			case 'update':
				$fields = $this->get_buffer('update');

				$builded_query = "UPDATE {$table} SET {$fields} {$where}";
				break;
			case 'delete':
				$builded_query = "DELETE FROM {$table} {$where} {$limit}";
				break;
			case 'increment':
				$increment_value = $this->get_buffer('increment_value');
				$column = $this->get_buffer('column');

				$builded_query = "UPDATE {$table} SET {$column} = {$column} + {$increment_value} {$where}";
				break;
			case 'decrement':
				$decrement_value = $this->get_buffer('decrement_value');
				$column = $this->get_buffer('column');

				$builded_query = "UPDATE {$table} SET {$column} = {$column} - {$decrement_value} {$where}";
				break;
			case 'find':
				$primary = $this->get_primary_key($table);
				$search_value = $this->get_buffer('search_value');

				$builded_query = "SELECT * FROM {$table}  WHERE {$primary} = {$search_value}";
				break;
			case 'count':
				$builded_query = "SELECT COUNT(*) FROM {$table} {$where}";
				break;
			case 'max':
				$max = $this->get_buffer('max');
				$builded_query = "SELECT MAX({$max}) FROM {$table} {$where}";
				break;
			case 'min':
				$min = $this->get_buffer('min');
				$builded_query = "SELECT MIN({$min}) FROM {$table} {$where}";
				break;
			case 'avg':
				$avg = $this->get_buffer('avg');
				$builded_query = "SELECT AVG($avg) FROM {$table} {$where}";
				break;
			case 'sum':
				$sum = $this->get_buffer('sum');
				$builded_query = "SELECT SUM({$sum}) FROM {$table} {$where}";
				break;
		}

		$this->clear_buffer();

		return $builded_query;
	}


	/**
	* Executa a query
	*
	* Executa as ações especificadas pela query
	*
	* @param string $query A query que deve ser executada
	* @param bool $should_return Diz para a função se deve ser retornado algum tipo de dado, por padrão false, caso true será dado 'fetchAll' nos dados
	*
	* @return mixed
	*/
	private function execute_query($query, $should_return = false)
	{
		try {
			if($should_return)
			{
				return $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
			}else
			{
				return $this->query($query);
			}	
		}catch (Exception $error)
		{
			$this->error($error->getMessage());
		}
	}


	/**
	* Escapa o parametro passado
	*
	* Função usada para proteção do banco de dados contra tentativas de ataque através de SQL Injection e XSS
	*
	* @param string $string_to_escape String que deve ser protegida
	*
	* @return void
	*/
	public function sanitize(&$string_to_escape)
	{
		$string_to_escape = trim($string_to_escape);
		$string_to_escape = htmlspecialchars($string_to_escape);
		$string_to_escape = addslashes($string_to_escape);
	}


	/**
	* Função para descobrir a Primary Key de uma tabela
	*
	* @param string $table Nome da tabela do banco de dados em que deve ser feita a busca
	*
	* @return string Nome da coluna primária
	*/
	private function get_primary_key($table)
	{
		return $this->execute_query("SHOW KEYS FROM {$table} WHERE Key_name = 'secondary'", true)[0]['Column_name'];
	}


	/**
	* Atribui ao Buffer a tabela, ou tabelas, em que será executada a query
	*
	* @param mixed $tables As tabelas podem ser passadas como uma string única ou como vários parametros
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function table(...$tables)
	{
		$table = implode(', ', $tables);

		$this->set_buffer('table', $table);
		return $this;
	}


	/**
	* Define o tipo de query que está sendo construida como um SELECT
	*
	* @param string $columns Colunas que devem ser selecionadas
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function select(...$columns)
	{

		if(empty($columns))
		{
			$columns = '*';
		}else
		{
			$columns = implode(', ', $columns);
		}

		$this->set_buffer('query_type', 'select');
		$this->set_buffer('columns', $columns);

		return $this;
	}


	/**
	* Define o tipo de query que está sendo construida como um INSERT e recebe suas as colunas
	*
	* @param string $columns Colunas que devem ter valores atribuidos durante a inserção
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function insert(...$columns)
	{

		$this->set_buffer('query_type', 'insert');
		$this->set_buffer('insert_columns', implode(', ', $columns));

		return $this;
	}


	/**
	* Define o tipo de query que está sendo construida como UPDATE e atualiza os campos especificados no parametro da função
	*
	* @param array $update_list Lista com as colunas e os dados que iram ser atualizados
	*
	* @return void
	*/
	public function update($update_list)
	{
		foreach($update_list as $column => $value)
		{
			$this->sanitize($value);

			$update_field[] = "{$column} = '{$value}'";
		}

		$this->set_buffer('query_type', 'update');
		$this->set_buffer('update', implode(', ', $update_field));

		$query = $this->build_query();
		$this->execute_query($query);
	}


	/**
	* Deleta todos os registros da tabela que estejam detro das condições impostar pelo WHERE, se não forem passadas condições então apagará tudo da tabela
	*
	* @param string|int $limit Limita a quantidade de registros deletados pela query
	*
	* @return void
	*/
	public function delete($limit = '')
	{
		if($limit)
		{
			$this->set_buffer('limit', "LIMIT {$limit}");
		}

		$this->set_buffer('query_type', 'delete');

		$query = $this->build_query();
		$this->execute_query($query);
	}


	/**
	* Filtra registros
	*
	* Cria as condições do WHERE para a query
	*
	* @param string $conditions As condições podem ser passadas como um parâmetro único ou vários
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function where(...$conditions)
	{

		if($conditions)
		{
			$conditions_counter = count($conditions);

			if($conditions_counter == 1)
			{
				$this->set_buffer('where', 'WHERE '.$conditions[0]);
			}elseif($conditions_counter == 2)
			{
				$this->sanitize($conditions[1]);

				$this->set_buffer('where', "WHERE {$conditions[0]} = '{$conditions[1]}'");
			}elseif($conditions_counter == 3)
			{
				if(strcasecmp($conditions[1], 'IN') === 0)
				{
					$list = $conditions[2];

					foreach($list as $key => $value)
					{
						$this->sanitize($value);
						$list[$key] = "'{$value}'";
					}

					$list = implode(', ', $list);

					$this->sanitize($conditions[0]);

					$this->set_buffer('where', "WHERE {$conditions[0]} IN ({$list})");
				}else{
					$this->sanitize($conditions[2]);

					$this->set_buffer('where', "WHERE {$conditions[0]} {$conditions[1]} '{$conditions[2]}'");
				}
			}elseif($conditions_counter == 4)
			{
				$this->sanitize($conditions[2]);
				$this->sanitize($conditions[3]);

				$this->set_buffer('where', "WHERE {$conditions[0]} BETWEEN {$conditions[2]} AND {$conditions[3]}");
			}
		}

		return $this;
	}


	/**
	* Ordena os resultados da query
	*
	* @param string $order_params
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function order($order_params = '')
	{
		if($order_params)
		{
			$this->set_buffer('order', 'ORDER BY '.$order_params);
		}

		return $this;
	}


	/**
	* Agrupa os resultados da query
	*
	* @param string $column_groups
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function group(...$column_groups)
	{

		if($column_groups)
		{
			$this->set_buffer('group', 'GROUP BY '.implode(', ', $column_groups));
		}

		return $this;
	}


	/**
	* Especifica condições de filtragem de agrupamentos
	*
	* @param string $condition
	*
	* @return QueryBuilder Retorna a si mesmo para permitir a técnica de métodos encadeados
	*/
	public function having($condition)
	{
		if($condition)
		{
			$this->set_buffer('having', 'HAVING '.$condition);
		}

		return $this;
	}


	/**
	* Atribui os valores usados no INSERT e executa a inserção
	*
	* @param mixed $insert_values
	*
	* @return void
	*/
	public function values(...$insert_values)
	{

		foreach ($insert_values as $key => $value)
		{
			$this->sanitize($value);

			$insert_values[$key] = "'{$value}'";
		}

		$insert_values = implode(', ', $insert_values);

		$this->set_buffer('insert_values', $insert_values);

		$query = $this->build_query();
		$this->execute_query($query);
	}


	/**
	* Incrementa a coluna especificada
	*
	* A coluna especificada será incrementada pelo valor indicado, caso não seja passado um valor de incremento será usado o número '1' por padrão
	*
	* @param string $column Coluna que terá seu valor modificado
	* @param int $increment_value Valor a ser incrementado
	*
	* @return void
	*/
	public function increment($column, $increment_value = 1)
	{
		$this->set_buffer('query_type', 'increment');
		$this->set_buffer('column', $column);
		$this->set_buffer('increment_value', $increment_value);

		$query = $this->build_query();
		$this->execute_query($query);
	}


	/**
	* Decrementa a coluna especificada
	*
	* A coluna especificada será decrementada pelo valor indicado, caso não seja passado um valor de decremento será usado o número '1' por padrão
	*
	* @param string $column Coluna que terá seu valor modificado
	* @param int $decrement_value Valor a ser incrementado
	*
	* @return void
	*/
	public function decrement($column, $decrement_value = 1)
	{
		$this->increment($column, -$decrement_value);
	}


	/**
	* Retorna todos os dados da consulta SELECT
	*
	* @return array|null Resultados da pesquisa
	*/
	public function all()
	{
		$query = $this->build_query();
		return $this->execute_query($query, true);
	}


	/**
	* Retorna o primeiro resultado da consulta SELECT
	*
	* @return array|null
	*/
	public function first()
	{
		return @$this->all()[0];
	}


	/**
	* Limita o número de resultados da query
	*
	* @param int $limits_params Limite e/ou offset
	*
	* @return array|null
	*/
	public function limit(...$limits_params)
	{

		if(!empty($limits_params)){
			$this->set_buffer('limit', 'LIMIT '.implode(', ', $limits_params));
		}

		return $this->all();
	}


	/**
	* Retorna o número de resultados da query
	*
	* @return int
	*/
	public function count()
	{
		$this->set_buffer('query_type', 'count');

		$query = $this->build_query();
		return (Int) $this->execute_query($query, true)[0]['COUNT(*)'];
	}


	/**
	* Retorna o maior valor de determinada coluna
	*
	* @param string $max_column
	*
	* @return int
	*/
	public function max($max_column)
	{
		$this->set_buffer('query_type', 'max');
		$this->set_buffer('max', $max_column);

		$query = $this->build_query();
		return (Int) $this->execute_query($query, true)[0]["MAX({$max_column})"];
	}


	/**
	* Retorna o menor valor de determinada coluna
	*
	* @param string $min_column
	*
	* @return int
	*/
	public function min($min_column)
	{
		$this->set_buffer('query_type', 'min');
		$this->set_buffer('min', $min_column);

		$query = $this->build_query();
		return (Int) $this->execute_query($query, true)[0]["MIN({$min_column})"];
	}


	/**
	* Retorna a média dos valores de determinada coluna
	*
	* @param string $avg_column
	*
	* @return int
	*/
	public function avg($avg_column)
	{
		$this->set_buffer('query_type', 'avg');
		$this->set_buffer('avg', $avg_column);

		$query = $this->build_query();
		return (Int) $this->execute_query($query, true)[0]["AVG({$avg_column})"];
	}


	/**
	* Retorna a soma de todos os valores de determinada coluna
	*
	* @param string $sum_column
	*
	* @return int
	*/
	public function sum($sum_column)
	{
		$this->set_buffer('query_type', 'sum');
		$this->set_buffer('sum', $sum_column);

		$query = $this->build_query();
		return (Int) $this->execute_query($query, true)[0]["SUM({$sum_column})"];
	}


	/**
	* Procura um registro na tabela pela sua Primary Key com o valor especificado e o retorna
	*
	* Pelo fato da pesquisa ser feita pela chave primária, sempre retornará apenas um ou nenhum resultado
	*
	* @param mixed $search Valor da pesquisa
	*
	* @return array|null
	*/
	public function find($search)
	{
		$this->set_buffer('query_type', 'find');
		$this->set_buffer('search', $search);

		$query = $this->build_query();
		$data = $this->execute_query($query, true);

		return empty($data) ? $data : $data[0];
	}


	/**
	* Automatiza a paginação de resultados
	*
	* Função que automatiza a paginação dos resultados da query, sendo necessário apenas informar a quantidade de itens por página e a página atual
	*
	* @param int $items_per_page
	* @param int $page Caso não seja especificada a página atual é considerado o número '1'
	*
	* @return object
	*/
	public function paginate($items_per_page, $page = 1)
	{
		$this->sanitize($page);
		$page = max(1, $page);
		$offset = ($page-1) * $items_per_page;

		$not_paginated_buffer = $this->buffer;

		$this->set_buffer('limit', "LIMIT {$offset}, {$items_per_page}");

		$paginated_query = $this->build_query();

		$this->buffer = $not_paginated_buffer;

		$not_paginated_query = $this->build_query();

		$items = $this->execute_query($paginated_query, true);
		$all_items = $this->execute_query($not_paginated_query, true);

		$total_pages = empty($all_items) ? 0 : ceil(count($all_items) / $items_per_page);
		$previous_page = max(1, $page-1);
		$next_page = min($total_pages, $page+1);

		return (Object) array(
			'items' => $items,
			'total_pages' => (Int) $total_pages,
			'total_items' => (Int) $all_items ? count($all_items) : 0,
			'items_per_page' => (Int) $items_per_page,
			'current_page' => (Int) $page,
			'last_page' => (Int) $total_pages,
			'previous_page' => (Int) $previous_page,
			'next_page' => (Int) $next_page,
			'in_first_page' => ($page == '1'),
			'in_last_page' => ($page == $total_pages),
			'has_previous_page' => ($page > 1),
			'has_next_page' => ($page < $total_pages)
		);
	}


	/**
	* Debuga o dado passado para a função
	*
	* Função para facilitar o debug de algum dado
	*
	* @param mixed $data Dado que será debugado
	*/
	public function dump($data)
	{
		echo "<pre>", var_dump($data), "</pre>";
	}
}
