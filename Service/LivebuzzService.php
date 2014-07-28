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
    protected $username;

    /**
     * @var string
     */
    protected $password;

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

        $parameter = $this->getContainer()->getParameter('cekurte_livebuzz');

        $this->setCredentials(
            $parameter['livebuzz']['email'],
            $parameter['livebuzz']['password']
        );
    }

    /**
     * Set a password
     *
     * @param string $password
     *
     * @return LivebuzzService
     */
    protected function setPassword($password)
    {
        $this->password = md5($password);

        return $this;
    }

    /**
     * Get a encoded md5 password
     *
     * @return string
     */
    protected function getEncodedPassword()
    {
        return $this->password;
    }

    /**
     * Set a username
     *
     * @param string $username
     *
     * @return LivebuzzService
     */
    protected function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get a username
     *
     * @return string
     */
    protected function getUsername()
    {
        return $this->username;
    }

    /**
     * Get a auth token
     *
     * @return string
     */
    protected function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set a auth token
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
     * Set credentials
     *
     * @param string $username
     * @param string $password
     *
     * @return LivebuzzService
     */
    public function setCredentials($username, $password)
    {
        $this
            ->setUsername($username)
            ->setPassword($password)
        ;

        $serviceHandler = curl_init();

        curl_setopt($serviceHandler, CURLOPT_URL, self::SERVICE_AUTH_URL);
        curl_setopt($serviceHandler, CURLOPT_HEADER, FALSE);
        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($serviceHandler, CURLOPT_POST, TRUE);

        curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, http_build_query(array(
            'email' => $this->getUsername(),
            'senha' => $this->getEncodedPassword(),
        )));

        $response = curl_exec($serviceHandler);

        $code = curl_getinfo($serviceHandler, CURLINFO_HTTP_CODE);

        if ($code != 200) {
            throw new \Exception(sprintf('%s. %s!',
                'Ocorreu um erro ao realizar a autenticação com o serviço "Livebuzz"',
                'Verifique se as suas credenciais estão corretas'
            ));
        }

        $response = json_decode($response);

        try {

            $this->setAuthToken($response->token);

        } catch (\Exception $e) {

            throw new \InvalidArgumentException(
                sprintf('CekurteLivebuzzBundle: %s', $response->message),
                $response->code
            );
        }

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
