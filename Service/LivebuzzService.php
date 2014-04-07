<?php

namespace Cekurte\LivebuzzBundle\Service;

use Cekurte\ComponentBundle\Util\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Classe responsável por permitir que o Symfony2
 * trabalhe com a API da Dinamize realizando a integração
 * com o serviço Livebuzz.
 *
 * @author João Paulo Cercal <sistemas@cekurte.com>
 * @version 1.0
 */
class LivebuzzService extends ContainerAware
{
    /**
     * URL utilizada no processo de autenticação da API do Livebuzz
     */
    const SERVICE_AUTH_URL = 'http://api.livebuzz.com.br/authenticate';

    /**
     * URL utilizada pela API do Livebuzz para
     * fazer as requisições após a autenticação
     */
    const SERVICE_URL = 'http://api.livebuzz.com.br';

    /**
     * @var string
     */
    protected $authToken;

    /**
     * Inicialização
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $serviceHandler = curl_init();

        // Prepara as opções para o processo de autenticação
        curl_setopt($serviceHandler, CURLOPT_URL, self::SERVICE_AUTH_URL);
        curl_setopt($serviceHandler, CURLOPT_HEADER, FALSE);
        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($serviceHandler, CURLOPT_POST, TRUE);

        $parameter = $this->getContainer()->getParameter('cekurte_livebuzz');

        // Dados de autenticação. Senhas devem ser passadas sempre como um hash MD5
        $data = array(
            'email' => $parameter['livebuzz']['email'],
            'senha' => md5($parameter['livebuzz']['password'])
        );

        curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, http_build_query($data));

        // Captura a resposta da ação de autenticação
        $response = curl_exec($serviceHandler);

        // E o código do status HTTP da resposta
        $code = curl_getinfo($serviceHandler, CURLINFO_HTTP_CODE);

        // Se a requisição HTTP não foi bem sucedida (código 200)
        if ($code != 200) {
            throw new \Exception('Ocorreu um erro ao realizar a autenticação com o serviço "Livebuzz". Verifique se as suas credenciais estão corretas!');
        }

        // Aplica a função da linguagem que converte uma string JSON para um objeto
        $response = json_decode($response);

        // Armazena o Token e a URL que serão usados nas requisições subsequentes
        $this->setAuthToken($response->token);
    }

    /**
     * Get AuthToken
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set AuthToken
     *
     * @param string $authToken
     *
     * @return LivebuzzService
     */
    protected function setAuthToken($authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Este método é fornecido na API do Livebuzz::runCommand
     *
     * @param  string $uri      o recurso que será solicitado
     * @param  array|null $data os dados enviados via requisição POST e GET
     * @param  string $method   o método de envio
     *
     * @return array
     */
    public function api($uri, $data = null, $method = 'GET')
    {
        $method = strtoupper($method);

        // Inicializa a biblioteca cURL
        $serviceHandler = curl_init();

        if ($method == 'GET' && $data)
            $uri .= "/" . urlencode(json_encode($data));

        curl_setopt($serviceHandler, CURLOPT_URL, self::SERVICE_URL . '/' . $uri);
        curl_setopt($serviceHandler, CURLOPT_HEADER, false);

        // O cabeçalho "Livebuzz-Auth" deve estar sempre presente.
        $headers = array('Livebuzz-Auth: ' . $this->getAuthToken());

        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($serviceHandler, CURLOPT_POST, TRUE);
            curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, $data);
        } else if ($method == 'GET') {
            curl_setopt($serviceHandler, CURLOPT_HTTPGET, TRUE);
        }

        // Seta os cabeçalhos HTTP
        curl_setopt($serviceHandler, CURLOPT_HTTPHEADER, $headers);

        // Retorna a resposta, já decodificada como objeto PHP.
        return json_decode(curl_exec($serviceHandler));
    }
}
