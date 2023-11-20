<?php

class chamadaAPIService {

    protected $autenticacao;

    public function __construct($servico = null) {
        $this->autenticacao = new autenticacaoService($servico);
    }

    public function enviarPessoas($body) {
        $header = [
            "Authorization: Bearer " . $this->autenticacao->getToken(),
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/sigesp/feedback', 'POST', $header, $body);
        $status =  $curl->getStatus();

        if ($status == 200) {
            return $curl->getResponse();
        }

        return [];
    }

    public function consultarPessoa($cpf) {
        $header = [
            "Authorization: Bearer " . $this->autenticacao->getToken(),
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/pessoa?cpf=' . $cpf, 'GET', $header, null, true);
        $status =  $curl->getStatus();
        $response = $curl->getResponse();
        if ($status == 200 && $response) {
            return $response;
        }

        return ['error' => true];
    }

    public function consultarPlaca($placa) {
        $header = [
            "Authorization: Bearer " . $this->autenticacao->getToken(),
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/veiculo?placa=' . $placa, 'GET', $header, null, true);
        $status =  $curl->getStatus();
        $response = $curl->getResponse();
        if ($status == 200 && $response) {
            return $response;
        }

        return ['error' => true];
    }

    public function consultarProcesso($cpf) {
        $header = [
            "Authorization: Bearer " . $this->autenticacao->getToken(),
            "Content-Type: application/json"
        ];

        $curl = $this->enviarCurl('http://kntsys.com.br/api/processo?cpf=' . $cpf, 'GET', $header, null, true);
        $status =  $curl->getStatus();
        $response = $curl->getResponse();
        if ($status == 200 && $response) {
            return $response;
        }

        return ['error' => true];
    }

    private function enviarCurl($url, $metodo, $header, $body = null, $noBody = false) {
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


    public function gravarFeedback(array $response, array $body): void {
        $pessoasAdicionadasLista = array_keys($response['dados']);

        foreach ($body['pessoas'] as $pessoa) {
            $feedback = new Feedback;
            $feedback->pessoa_id = $pessoa['pessoaID'];
            $feedback->ocorrencia_id = $body['ocorrenciaID'];
            $feedback->situacao = (in_array($pessoa['pessoaID'], $pessoasAdicionadasLista)) ? 'pendente' : 'cancelado'; // pendente / enviado / recebido / cancelado
            $feedback->avaliacao_id = 6;
            $feedback->store();
        }
    }
}
