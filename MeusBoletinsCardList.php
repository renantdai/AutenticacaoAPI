<?php

require 'app/service/RandonCodeGenerator.php';

class MeusBoletinsCardList extends TPage
{
    private $form; // form
    private $cardView; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'gmob';
    private static $activeRecord = 'Ocorrencia';
    private static $primaryKey = 'id';
    private static $formName = 'form_OcorrenciaCardList';
    private $showMethods = ['onReload', 'onSearch'];

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Meus Boletins");

           try {
                if (!parent::isMobile()) {
                    throw new Exception();
                }
            } catch (Exception $e) {
                TToast::show("error", "Acesso somente através de dispositivos móveis", "center", "fas:mobile-alt");
                exit();
            }

        $id = new TEntry('id');
        $fato_id = new TDBCombo('fato_id', 'gmob', 'Fato', 'id', '{fato}','fato asc'  );

        $fato_id->enableSearch();
        $id->setSize(100);
        $fato_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Número:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Fato:", null, '14px', null, '100%'),$fato_id]);
        $row2->layout = ['col-sm-6'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        $this->cardView = new TCardView;

        $this->cardView->setContentHeight(170);
        $this->cardView->setTitleTemplate('{fato->fato}');
        $this->cardView->setItemTemplate("<strong>Número:</strong> {id}  </p><p><strong>Registro:</strong> {created_at}</p><p><strong>Forma de despacho:</strong> {forma_despacho->nome_comunicacao}</p>   ");

        $this->cardView->setItemDatabase(self::$database);

        $this->filter_criteria = new TCriteria;

        $filterVar = TSession::getValue("userid");
        $this->filter_criteria->add(new TFilter('responsavel', '=', $filterVar));
        $filterVar = StatusAtivo::ATIVO;
        $this->filter_criteria->add(new TFilter('status_ocorrencia_id', '=', $filterVar));

        $action_OcorrenciaForm_onEdit = new TAction(['OcorrenciaForm', 'onEdit'], ['key'=> '{id}']);

        $this->cardView->addAction($action_OcorrenciaForm_onEdit, '', 'fas:pencil-alt #3F51B5', null, '', true); 

        $action_MeusBoletinsCardList_onTransmitir = new TAction(['MeusBoletinsCardList', 'onTransmitir'], ['key'=> '{id}']);

        $this->cardView->addAction($action_MeusBoletinsCardList_onTransmitir, '', 'fas:arrow-alt-circle-up #8BC34A', null, '', true); 

        $action_CertidaoBoletimDocument_onGenerate = new TAction(['CertidaoBoletimDocument', 'onGenerate'], ['key'=> '{id}']);

        $this->cardView->addAction($action_CertidaoBoletimDocument_onGenerate, '', 'fas:file-pdf #F44336', null, '', true); 

        $panel = new TPanelGroup;
        $panel->add($this->cardView);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);

    }

    public function onTransmitir($param = null) 
    {
        try 
        {
           new TQuestion("Deseja transmitir a ocorrência ao revisor?", new TAction([__CLASS__, 'onYes'], $param), new TAction([__CLASS__, 'onNo'], $param));

            //</autoCode>
        }
        catch (Exception $e) 
        {
        new TMessage('error', $e->getMessage());    
        }
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        // get the search form data
        $data = $this->form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->id) AND ( (is_scalar($data->id) AND $data->id !== '') OR (is_array($data->id) AND (!empty($data->id)) )) )
        {

            $filters[] = new TFilter('id', '=', $data->id);// create the filter 
        }

        if (isset($data->fato_id) AND ( (is_scalar($data->fato_id) AND $data->fato_id !== '') OR (is_array($data->fato_id) AND (!empty($data->fato_id)) )) )
        {

            $filters[] = new TFilter('fato_id', '=', $data->fato_id);// create the filter 
        }

        $param = array();
        $param['offset']     = 0;
        $param['first_page'] = 1;

        // fill the form with data again
        $this->form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        $this->onReload($param);
    }

    public function onReload($param = NULL)
    {
        try
        {

            // open a transaction with database 'gmob'
            TTransaction::open(self::$database);

            // creates a repository for Ocorrencia
            $repository = new TRepository(self::$activeRecord);
            $limit = 20;

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->cardView->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $object->created_at = call_user_func(function($value, $object, $row)
                    {
                        if(!empty(trim($value)))
                        {
                            try
                            {
                                $date = new DateTime($value);
                                return $date->format('d/m/Y H:i');
                            }
                            catch (Exception $e)
                            {
                                return $value;
                            }
                        }
                    }, $object->created_at, $object, null);

                    $this->cardView->addItem($object);

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function onYes($param = null) 
    {
        try 
        {
             TTransaction::open(self::$database); # abre a conexão com o banco de dados

            $key = (int) $param['key']; # captura o id do registro (Ocorrencia)

            $ocorrencia = Ocorrencia::find($key); # busca o registro

            if ($ocorrencia) # verifica se existe o registro
            {   
                $ocorrencia->status_ocorrencia_id = StatusOcorrencia::REVISAO;
                $ocorrencia->store(); # atualiza o status da ocorrência

                $validaDocumentoHash = DocumentoHash::where('nome_documento', '=', 'boletim_' . $param['key'])->first();
                if (!$validaDocumentoHash) {
                    $DocumentoHash = new DocumentoHash();
                    $DocumentoHash->cod_verificacao = RandomCodeGenerator::generateCode();
                    $DocumentoHash->nome_documento = 'boletim_' . $param['key'];
                    $DocumentoHash->store();
                    $ocorrencia->cod_verificacao = $DocumentoHash->cod_verificacao;
                } else {
                    $ocorrencia->cod_verificacao = $validaDocumentoHash->cod_verificacao;
                }

                TApplication::postData('form_OcorrenciaList',__CLASS__, 'onReload'); # atualiza o datagrid
            }

            TTransaction::close(); # fecha a conexão

            new TMessage('info', "Boletim encaminhado ao revisor!");
            //TToast::show("success", "Boletim transmitido para o revisor!", "center", "fas:check");

            MeusBoletinsCardList::enviarPessoaFeedback($ocorrencia);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onNo($param = null) 
    {
        try 
        {

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function enviarPessoaFeedback($ocorrencia): void {
       TTransaction::open(self::$database);
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ocorrencia_id', '=', $ocorrencia->id));
        $criteria->add(new TFilter('codicao_fisica_parcipante_id', '<>', 3)); //condicao 3 = morto
        $criteria->add(new TFilter('envolvimento_participante_id', '<>', 1)); // envolvimento 1 = preso
        $criteria->add(new TFilter('envolvimento_participante_id', '<>', 4)); // envolvimento 4 = apreendido
        $criteria->add(new TFilter('envolvimento_participante_id', '<>', 8)); // envolvimento 8 = suspeito
        $criteria->add(new TFilter('envolvimento_participante_id', '<>', 9)); // envolvimento 9 = autor
        $criteria->add(new TFilter('envolvimento_participante_id', '<>', 10)); // envolvimento 10 = abordado
        $participantes = Participante::getObjects($criteria);

        if ($participantes) {
            $pessoas = [];
            foreach ($participantes as $participante) {
                $dadosPessoa = $participante->get_pessoa();
                $feedbackEnviado = Feedback::select('id')->where('pessoa_id', '=', $dadosPessoa->id)->where('ocorrencia_id', '=', $ocorrencia->id)->load();
                if ($feedbackEnviado) {
                    continue;
                }

                $condicao = CodicaoParcipante::select('codicao_fisica')->where('id', '=', $participante->codicao_fisica_parcipante_id)->load();
                $pessoas[] = [
                    'pessoaID' => $dadosPessoa->id,
                    'nome' => $dadosPessoa->nome,
                    'telefone' => preg_replace("/[^0-9]/", '', $dadosPessoa->contato_principal),
                    'condicao' => $condicao[0]->codicao_fisica
                ];
            }
            if (empty($pessoas)) {
                TTransaction::close();
                return;
            }

            $body = [
                'ocorrenciaID' => $ocorrencia->id,
                'chaveAcesso' => $ocorrencia->cod_verificacao,
                'horarioDespachoID' => 1,
                'sistemaOrigem' => 'imbe',
                'pessoas' => $pessoas
            ];
            $chamadaAPI = new chamadaAPIService();
            $retorno = $chamadaAPI->enviarPessoas($body);
            if ($retorno) {
                $chamadaAPI->gravarFeedback($retorno, $body);
            }
        }
        TTransaction::close();
    }

}

