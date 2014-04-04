# Exemplos

Em um controller você poderá utilizar a API do serviço desta forma:

```php
# src/Namespace/YourBundle/Controller/DefaultController.php

// ...

$result = $this->get('cekurte_livebuzz')->api('tag/listar');

var_dump($result);

/*
object(stdClass)[2758]
  public 'total' => string '13' (length=2)
  public 'total_page' => string '13' (length=2)
  public 'data_list' =>
    array (size=13)
      0 =>
        object(stdClass)[2753]
          public 'cod_tag' => string '123321' (length=5)
          public 'nome' => string 'Abc Def' (length=12)
          public 'descricao' => string '' (length=0)
      1 => ...
*/

// ...

```

O método **api** pode receber três parametros, são eles:

- *(string)* **$uri**: o recurso que será solicitado

- *(array|null)* **$data**: os dados enviados via requisição POST e GET

- *(string)* **$method**: o método de envio (GET ou POST)

[Voltar para a Configuração](configuracao.md)