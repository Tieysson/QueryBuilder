# QueryBuilder
O QueryBuilder é uma biblioteca para conexão com banco de dados, facilitar a construção de querys ao mesmo tempo que melhora a legibilidade do código fazendo uso do Design Pattern Fluent Interface. Baseado no Eloquent do Laravel.

# Documentação

<h3>Criando uma instância</h3>
<p>Para cada banco de dados deverá ser criado uma nova instâcia do QueryBuilder, a partir dessa instância será possível ter acesso a todas as tabelas contidas nele. Para se conectar ao banco de dados deve ser informado, no construtor, as informações necessárias para a conexão, são elas o host, nome do banco, usuário e senha</p>

```php
require_once 'path/to/QueryBuilder.php';

$DB = new QueryBuilder('localhost', 'database-name', 'root', 'senha');
```

<p>Agora você já tem acesso a todas as funcionalidades oferecidas.</p>

<h3>Consultas ao banco de dados</h3>
<p></p>
<p>Para realizar qualquer operação deve-se primeiro informar a tabela em que será executada a query e o tipo da operação, por exemplo, para consultar todos os registros em uma tabela de produtos é usado o método <code>table()</code> para selecionar a tabela, <code>select()</code> para especificar as colunas que devem ser retornadas.</p>

```php
$produtos = $DB->table('produtos')->select('*')->all();
```
<p>O método <code>all()</code> retornará todos os registros encontrados pelo <code>select()</code>.</p>
<p>Outras alternativas ao <code>all()</code> podem ser:</p>

```php
// First - retorna o primeiro registro
$produto = $DB->table('produtos')->select('*')->first();

// Limit - funciona como o LIMIT do mysql, limitará o número de registros retornados
$produtos = $DB->table('produtos')->select('*')->limit(10);

// Um segundo parâmetro pode ser passado como offset
$produtos = $DB->table('produtos')->select('*')->limit(5, 10);

// Find - para procurar por determinado registros através de sua Primary Key
$produtos = $DB->table('produtos')->find(1);
```
<br>

<p>Caso o select deva retornar todas as colunas, não é necessário passar o parâmetro na função, <code>select()</code> e <code>select('*')</code> terão o mesmo efeito. Se não for necessário o retorno de todas as colunas, então há duas formas de indicar as colunas requeridas:</p>

```php
// Tem o mesmo efeito de select('*')
$produtos = $DB->table('produtos')->select()->all();

// Colunas podem ser indicadas através de uma string única ou de vários parâmetros
$produtos = $DB->table('produtos')->select('id, nome, preco')->all();
$produtos = $Db->table('produtos')->select('id', 'nome', 'preco')->all();
```
<h5>Filtragem de dados</h5>
<p>Para filtrar quais registros devem ser retornados deve ser acrescentado o método <code>where()</code> na construção da query, nele é possível impor condições ao select.</p>

```php
// Há diversas formas de se criar condições com o where

// Para comparações pode ser usado apenas dois parâmetros ou especifica-lo
$alimentos = $DB->table('produtos')->select()->where('tipo', 'alimenticio')->all();
$alimentos = $DB->table('produtos')->select()->where('tipo', '=', 'alimenticio')->all();

// Qualquer operador aritimético suportado pelo mysql pode ser usado 
$produtos_caros = $DB->table('produtos')->select()->where('preco', '>=', '500')->all();
$produtos_baratos = $DB->table('produtos')->select()->where('preco', 'BETWEEN', '1', '500')->all();
```

<p>Se for necessário operações mais complexas, um parâmetro único deve ser passado.</p>

```php
$alimentos = $DB->table('produtos')->select()->where("tipo = 'alimenticio' and preco >= '500'")->all();
```

<p>O QueryBuilder tem proteção básica contra ataques de SQL Injection e XSS, mas ao usar o método acima a query estará sujeita a ataques, para escapar strings utilize <code>sanitize()</code></p>

```php

$condicoes = "'' or 1='1'";

$DB->sanitize($condicoes);

echo $condicoes;

// A saída será: \'\' OR 1=\'1\'
```
