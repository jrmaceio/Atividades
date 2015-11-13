<?php
 
//// habilitar erros da pagina
//ini_set('display_errors', 1);
//ini_set('display_startup_erros', 1);
//error_reporting(E_ALL);
 
/**
 *
 *
 * @version    1.1
 * @author     Jackson Meires / Gustavo Emmel
 * @data       18/09/2015
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TipoAtividadesVinculos extends TPage {
 
    private $form;
    private $datagrid;
 
    public function __construct() {
        parent::__construct();
 
        $this->form = new TForm;
 
        // creates one datagrid
        $this->datagrid = new TQuickGrid;
        $this->datagrid->disableDefaultClick();
 
        $this->form->add($this->datagrid);
        
        // creates the action button
        $button1 = new TButton('action1');
        // define the button action
        $action_button1 = new TAction(array($this, 'onUpdate'));
        $action_button1->setParameter('id', filter_input(INPUT_GET, 'id'));
        $button1->setAction($action_button1, 'Atualizar');
        $button1->setImage('ico_apply.png');
 
        $this->form->addField($button1);
 
        // add the columns
        $this->datagrid->addQuickColumn('Tipo de Atividade', 'nome', 'left', 180);
        $this->datagrid->addQuickColumn('Ticket', 'ticket', 'left', 180);
        $this->datagrid->addQuickColumn('Sistema', 'sistema', 'left', 180);
        
        // creates the datagrid model
        $this->datagrid->createModel();
 
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->add($button1);
        $vbox->add($this->form);
 
        parent::add($vbox);
    }
 
    /**
     * Load the data into the datagrid
     */
    function onReloadTwo() {
        $this->onReload();
    }
 
    function onReload() {
 
        TTransaction::open('atividade');
 
        $repository = new TRepository('TipoAtividade');
        $criteria = new TCriteria;
        $criteria->setProperty('order', 'nome asc');
        $cadastros = $repository->load($criteria);

        //array de tickets
        $ticketItem = array();
        
        $cri = new TCriteria;
        $cri->setProperty('order', 'id asc');
        $repo = new TRepository('Ticket');
        $tickets = $repo->load($cri);
        foreach($tickets as $ticket)
        {
            $ticketItem[$ticket->id] = $ticket->id.' - '.$ticket->titulo;
        }

        //array de sistemas
        $sistemaItem = array();
        
        $repo = new TRepository('Sistema');
        $sistemas = $repo->load();
        foreach($sistemas as $sistema)
        {
            $sistemaItem[$sistema->id] = $sistema->nome;
        }
        
        $this->datagrid->clear();
        if ($cadastros) {
            // percorre os objetos retornados
            foreach ($cadastros as $key => $cadastro) {
 
                // add an regular object to the datagrid
                $itemObj = new StdClass;
       
                $itemObj->id = $cadastro->id;
                $itemObj->nome = $cadastro->nome;
               
                $tempId = $cadastro->id;
                $tempAtiSistema = $cadastro->sistema;
                $itemObj->sistema = new TCombo('sistema' . $key);
                $itemObj->sistema->addItems($sistemaItem);
                $itemObj->sistema->setValue($cadastro->sistema_id);
                $itemObj->sistema->setSize(180);
                
                $tempAtividadeTicket = $cadastro->ticket;
                $itemObj->ticket = new TCombo('ticket' . $key);
                $itemObj->ticket->addItems($ticketItem);
                $itemObj->ticket->setValue($cadastro->ticket_id);
                $itemObj->ticket->setSize(180);
 
                // adiciona o objeto na DataGrid
                $this->datagrid->addItem($itemObj);
 
                $this->form->addField($itemObj->sistema); // important!
                $this->form->addField($itemObj->ticket); // important!

                // armazenar valor dos campos no array
                $itemObj->id = $tempId;
                $itemObj->sistema = $tempAtiSistema;
                $itemObj->ticket = $tempAtividadeTicket;
 
                // converte campo de objeto para array
                $array_items[] = get_object_vars($itemObj);
            }
 
            // joga array para sessao
            TSession::setValue('array_items', $array_items);
        }
        // finaliza a transacao
        TTransaction::close();
        $this->loaded = true;
    }
 
    /**
     * Simulates an save button
     * Show the form content
     */
    public function onUpdate($param) {
 
        $data = $this->form->getData("TipoAtividade"); // optional parameter: active record class
        // pegar os dados da sessao armazenar na variavel
        $cotacao_items = TSession::getValue('array_items');
        // inicia transacao com o banco 'pg_ceres'
        TTransaction::open('atividade');
 
        // put the data back to the form
        $this->form->setData($data);
 
        $msg = '';
        $contAdd = 0;
 
        foreach ($cotacao_items as $item) {
            $itemObj = new StdClass;
 
            $itemObj->id = $item['id'];

            $itemObj->no = $item['nome'];
 
            foreach ($this->form->getFields() as $name => $field) {
                // pegando valor do combo
                if ($field instanceof TCombo) {
                    if ($name === ( 'sistema' . $contAdd )) {
                        $itemObj->sistema = $field->getValue();
                    }
                    if ($name === ( 'ticket' . $contAdd )) {
                        $itemObj->ticket = $field->getValue();
                    }
                }
            }
            $contAdd++;
            $cotacao_items_add[] = get_object_vars($itemObj);
        }
 
        try {
 
            if ($msg == '') {
 
                // percore o objeto e armazena
                foreach ($cotacao_items_add as $item) {
 
                    $itemObj = new TipoAtividade($item['id']);
                    
                    $itemObj->nome = $item['nome'];
                    $itemObj->sistema_id = $item['sistema'];
                    $itemObj->ticket_id = $item['ticket'];
                    
                    // armazena o objeto
                    $itemObj->store();
                }
                $msg = 'Registro salvo com sucesso!';
 
                // finaliza a transacao
                TTransaction::close();
            } else {
                $icone = 'error';
            }
 
            if ($icone == 'error') {
                // exibe mensagem de erro
                new TMessage($icone, "Erro ao Salvar o registro!");
            } else {
                // show the message
                $param = array();
                $param['id'] = filter_input(INPUT_GET, 'id');
                //chama o formulario com o grid
                TApplication::gotoPage('TipoAtividadesVinculos', 'onReloadTwo', $param); // reload
                new TMessage("info", $msg);
            }
        } catch (Exception $e) { // em caso de exce??o
            // exibe a mensagem gerada pela excecao
            new TMessage('error', $e->getMessage());
            // desfaz todas altera??es no banco de dados
            TTransaction::rollback();
        }
    }
 
    /**
     * shows the page
     */
    function show() {
        if (filter_input(INPUT_GET, 'method') == 'onReloadTwo') {
//            $this->onReloadTwo();
        } else {
            $this->onReload();
        }
        parent::show();
    }
 
}