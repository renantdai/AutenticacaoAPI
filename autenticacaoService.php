<?php
class autenticacaoService extends AdiantiRecordService {
    const DATABASE      = 'gmob';
    const ACTIVE_RECORD = 'AutenticacaoApi';
    const ATTRIBUTES    = ['created_at', 'email', 'id', 'refresh_token', 'senha', 'servico', 'token', 'update_at', 'validade'];
    protected $token;
    protected $credenciais;

    public function __construct($servico = null) {
        if ($servico) {
            $this->obterConfiguracoes($servico);
        } else {
            $this->obterConfiguracoes();
        }
    }

    public function setToken(?string $token): void {
        $this->token = $token;
    }

    public function getToken() {
        return $this->token;
    }

    public function setCredenciais(array $credenciais): void {
        $this->credenciais = $credenciais;
        $this->token = $credenciais['token'];
    }


    private function obterConfiguracoes($servico = null) {
        TTransaction::open(self::DATABASE);
        $criteria = new TCriteria;
        if ($servico) {
            $criteria->add(new TFilter('servico', '=', $servico));
            $consultaCredenciais = AutenticacaoApi::getObjects($criteria);
        } else {
            $consultaCredenciais = AutenticacaoApi::find(1);
        }
        TTransaction::close();

        $parametros = [];
        foreach ($consultaCredenciais as $key => $value) {
            $parametros[$key] = $value;
        }

        $this->setCredenciais($parametros);
        if (!$this->testarConfiguracao()) {
            return ['msg' => 'Sucesso ao obter as configurações', 'error' => false];
        }

        return ['error' => true];
    }


    public function testarConfiguracao(): bool {
        $header = [
            "Authorization: Bearer " . $this->token,
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/auth/me', 'POST', $header, null, true);
        $status = $curl->getStatus();
        $response = $curl->getResponse();

        if ($status == 200 && empty($response)) {
            $retorno = $this->gerarToken();

            return $retorno['error'];
        }

        return false;
    }

    public function gerarToken(): array {
        $header = [
            "Content-Type: multipart/form-data;"
        ];
        $body = [
            'email' => $this->credenciais['email'],
            'password' =>  $this->credenciais['senha']
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/auth/login', 'POST', $header, $body);
        $status = $curl->getStatus();
        $response = $curl->getResponse();

        if ($status == 200) {
            $this->atualizarToken($response);

            return ['error' => false];
        }
        return ['error' => true];
    }

    public function refreshToken() {
        $header = [
            "Authorization: Bearer " . $this->token,
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/auth/refresh', 'POST', $header, null, true);
        $status =  $curl->getStatus();
        $response = $curl->getResponse();

        if ($status == 200) {
            $this->atualizarToken($response);

            return ['error' => false];
        }
        return ['error' => true];
    }

    private function enviarCurl($url, $metodo, $header, $body = null, $noBody = false) {
        require_once('curl.php');
        $curl = new Curl();
        $curl->setMethod($metodo);
        $curl->setUrl($url);
        $curl->setHeader($header);
        if ($body) {
            $curl->setBody($body);
        }
        $curl->setNoBody($noBody);
        $curl->sendCurl();

        return $curl;
    }

    private function atualizarToken($response) {
        $this->token = $response['access_token'];

        TTransaction::open(self::DATABASE);
        $credencial = new autenticacaoAPI;
        $credencial->id = 1;
        $credencial->token = $response['access_token'];

        $dataAtual = new DateTime();
        $dataAtual->modify('+' . $response['expires_in'] . ' seconds');
        $credencial->validade = $dataAtual->format('Y-m-d H:i:s');
        $credencial->store();
        TTransaction::close();
    }
}
