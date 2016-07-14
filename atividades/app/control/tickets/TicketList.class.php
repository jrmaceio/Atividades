<?php
/**
 * TicketList Listing
 * @author  <your name here>
 */
class TicketList extends TPage
{
    private $form;     // registration form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
                        
        // creates the form
        $this->form = new TForm('form_Ticket');
        $this->form->class = 'tform'; // CSS class
        
        // creates a table
        $table = new TTable;
        $table-> width = '100%';
        $this->form->add($table);
        
        // add a row for the form title
        $row = $table->addRow();
        $row->class = 'tformtitle'; // CSS class
        $row->addCell( new TLabel('Ticket') )->colspan = 2;
        
        // create the form fields
        $id                             = new TEntry('id');
        $id->setMask('99999');
        $titulo                         = new TEntry('titulo');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter("ativo", "=", 1));
        $newparam['order'] = 'pessoa_nome';
        $newparam['direction'] = 'asc';
        $criteria->setProperties($newparam); // order, offset
        $solicitante_id                 = new TSeekButton('solicitante_id');
        $solicitante_nome               = new TEntry('solicitante_nome');
		$obj                            = new TicketPessoaSeek('form_Ticket');
        $action                         = new TAction(array($obj, 'onReload'));
        $solicitante_id->setAction($action);      
        $solicitante_nome->setEditable(FALSE);
        
        $criteria = new TCriteria;
        $criteria->add( new TFilter('enttipent', '=', 1));
        $entcodent                      = new TDBComboMultiValue('entcodent', 'atividade', 'Entidade', 'entcodent', array(0 => 'entcodent', 1 => 'entrazsoc'), 'entcodent', $criteria);
        $tipo_ticket_id                 = new TDBCombo('tipo_ticket_id', 'atividade', 'TipoTicket', 'id', 'nome');
        $status_ticket_id               = new TDBCombo('status_ticket_id', 'atividade', 'StatusTicket', 'id', 'nome');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter("origem", "=", 1));
        $criteria->add(new TFilter("ativo", "=", 1));
        $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
        $responsavel_id                 = new TDBCombo('responsavel_id', 'atividade', 'Pessoa', 'pessoa_codigo', 'pessoa_nome', 'pessoa_nome', $criteria);
        $proprietario_id                = new TDBCombo('proprietario_id', 'atividade', 'Pessoa', 'pessoa_codigo', 'pessoa_nome', 'pessoa_nome', $criteria);
        
        $prioridade_id                  = new TDBCombo('prioridade_id', 'atividade', 'Prioridade', 'id', 'nome');
        
        $sistema_id                     = new TDBCombo('sistema_id', 'atividade', 'Sistema', 'id', 'nome', 'nome');
        
        $total_registros               = new TEntry('total_registros');
        $total_registros->setEditable(FALSE);

        // define the sizes
        $id->setSize(50);
        $titulo->setSize(274);
        $solicitante_id->setSize(50);
        $solicitante_nome->setSize(200);
        $entcodent->setSize(274);
        $status_ticket_id->setSize(100);
        $tipo_ticket_id->setSize(200);
        $sistema_id->setSize(200);
        $responsavel_id->setSize(274);
        $proprietario_id->setSize(274);
        $prioridade_id->setSize(100);
        $total_registros->setSize(100);

        // add one row for each form field
        $table->addRowSet( new TLabel('ID:'), $id );
        $table->addRowSet( new TLabel('Titulo:'), $titulo );
        $table->addRowSet( new TLabel('Cliente:'), array($solicitante_id, $solicitante_nome) );
        $table->addRowSet( new TLabel('Entidade:'), $entcodent );
        $table->addRowSet( new TLabel('Proprietário:'), $proprietario_id );
        $table->addRowSet( new TLabel('Responsável:'), $responsavel_id );
        $table->addRowSet( new TLabel('Tipo Ticket:'), $tipo_ticket_id );
        $table->addRowSet( new TLabel('Sistema:'), $sistema_id );
        $table->addRowSet( new TLabel('Status:'), $status_ticket_id );
        $table->addRowSet( new TLabel('Prioridade:'), $prioridade_id );
        $table->addRowSet( new TLabel('Total registros:'), $total_registros );

        $this->form->setFields(array($id,$titulo,$solicitante_id,$solicitante_nome,$entcodent,$status_ticket_id,$tipo_ticket_id,$proprietario_id,
                                     $responsavel_id,$prioridade_id, $sistema_id,$total_registros ));

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Ticket_filter_data') );
        
        // create two action buttons to the form
        $find_button = TButton::create('find', array($this, 'onSearch'), _t('Find'), 'ico_find.png');
        $new_button  = TButton::create('new',  array('TicketForm', 'onEdit'), _t('New'), 'fa:plus-square green');
        $clean_button  = TButton::create('clean',  array($this, 'onClean'), 'Limpar', 'ico_close.png');
        
        $this->form->addField($find_button);
        $this->form->addField($new_button);
        $this->form->addField($clean_button);
        
        $buttons_box = new THBox;
        $buttons_box->add($find_button);
        $buttons_box->add($new_button);
        $buttons_box->add($clean_button);
        
        // add a row for the form action
        $row = $table->addRow();
        $row->class = 'tformaction'; // CSS class
        $row->addCell($buttons_box)->colspan = 2;
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setHeight(320);
        
        // creates the datagrid columns
        $status_ticket_id = new TDataGridColumn('status_ticket_id', 'S', 'center', 20);
        $id               = new TDataGridColumn('id', 'ID', 'left', 20);        
        $titulo           = new TDataGridColumn('titulo', 'Titulo', 'left', 250);
        $solicitante_id   = new TDataGridColumn('solicitante_id', 'Cliente', 'left', 100);
        $responsavel_id   = new TDataGridColumn('pessoa_responsavel->pessoa_nome', 'Responsavel', 'left', 100);
        $data_cadastro    = new TDataGridColumn('data_cadastro', 'Dias', 'center', 35);
        $prioridade_id    = new TDataGridColumn('prioridade->nome', 'Pri', 'right', 20); //get_prioridade()->nome

        $status_ticket_id->setTransformer(array($this, 'retornaStatus'));
        $solicitante_id->setTransformer(array($this, 'retornaCliente'));
        $responsavel_id->setTransformer(array($this, 'retornaPessoa'));
        $prioridade_id->setTransformer(array($this, 'retornaPrioridade'));
        $data_cadastro->setTransformer(array($this, 'retornaDias'));

        // add the columns to the DataGrid
        $this->datagrid->addColumn($status_ticket_id);
        $this->datagrid->addColumn($id);
         
        $this->datagrid->addColumn($titulo);
        $this->datagrid->addColumn($solicitante_id);
        $this->datagrid->addColumn($responsavel_id);
        $this->datagrid->addColumn($data_cadastro);
        $this->datagrid->addColumn($prioridade_id);

        // creates the datagrid column actions
        $order_id= new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $id->setAction($order_id);

        $order_titulo= new TAction(array($this, 'onReload'));
        $order_titulo->setParameter('order', 'titulo');
        $titulo->setAction($order_titulo);
           
        $order_cliente= new TAction(array($this, 'onReload'));
        $order_cliente->setParameter('order', 'solicitante_id');
        $solicitante_id->setAction($order_cliente);
        
        $order_status_ticket_id= new TAction(array($this, 'onReload'));
        $order_status_ticket_id->setParameter('order', 'status_ticket_id');
        $status_ticket_id->setAction($order_status_ticket_id);
        
        $order_dias= new TAction(array($this, 'onReload'));
        $order_dias->setParameter('order', 'data_cadastro');
        $data_cadastro->setAction($order_dias);

        $order_pessoa_responsavel=new TAction(array($this, 'onReload'));
        $order_pessoa_responsavel->setParameter('order', 'pessoa_responsavel->pessoa_nome');
        $responsavel_id->setAction($order_pessoa_responsavel);
        
        $order_prioridade_id= new TAction(array($this, 'onReload'));
        $order_prioridade_id->setParameter('order', 'prioridade->nome');
        $prioridade_id->setAction($order_prioridade_id);
        
        // creates two datagrid actions
        $action1 = new TDataGridAction(array('TicketForm', 'onEdit'));
        $action1->setLabel(_t('Edit'));
        $action1->setImage('fa:pencil-square-o blue fa-lg');
        $action1->setField('id');
        
        // add the actions to the datagrid
        $this->datagrid->addAction($action1);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // create the page container
        $container = TVBox::pack( $this->form, $this->datagrid, $this->pageNavigation);
        
        $container->style = 'width: 100%;max-width: 1200px;';
        $this->datagrid->style = '  width: 100%;  max-width: 1200px;';
        
        parent::add($container);
    }
    
    /**
     * method onInlineEdit()
     * Inline record editing
     * @param $param Array containing:
     *              key: object ID value
     *              field name: object attribute to be updated
     *              value: new attribute content 
     */
    function onInlineEdit($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            TTransaction::open('atividade'); // open a transaction with database
            $object = new Ticket($key); // instantiates the Active Record
            $object->{$field} = $value;
            $object->store(); // update the object in the database
            TTransaction::close(); // close the transaction
            
            $this->onReload($param); // reload the listing
            new TMessage('info', "Record Updated");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method onSearch()
     * Register the filter in the session when the user performs a search
     */
    function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('TicketList_filter_id',   NULL);
        TSession::setValue('TicketList_filter_titulo',   NULL);
        TSession::setValue('TicketList_filter_solicitante_id',   NULL);
        TSession::setValue('TicketList_filter_entidade_id',   NULL);
        TSession::setValue('TicketList_filter_status_ticket_id',   NULL);
        TSession::setValue('TicketList_filter_tipo_ticket_id',   NULL);
        TSession::setValue('TicketList_filter_responsavel_id',   NULL);
        TSession::setValue('TicketList_filter_proprietario_id',   NULL);
        TSession::setValue('TicketList_filter_prioridade_id',   NULL);
        TSession::setValue('TicketList_filter_sistema_id',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('TicketList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->titulo) AND ($data->titulo)) {
            $filter = new TFilter('titulo', 'ilike', "%{$data->titulo}%"); // create the filter
            TSession::setValue('TicketList_filter_titulo',   $filter); // stores the filter in the session
        }


        if (isset($data->solicitante_id) AND ($data->solicitante_id)) {
            $filter = new TFilter('solicitante_id', '=', "$data->solicitante_id"); // create the filter
            TSession::setValue('TicketList_filter_solicitante_id',   $filter); // stores the filter in the session
        }

        if (isset($data->entcodent) AND ($data->entcodent)) {
            
            try
            {
                TTransaction::open('atividade');
                $solicitantes = Pessoa::getPessoasEntidade($data->entcodent);
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage());
            }
            
            $filter = new TFilter('solicitante_id', 'IN', ($solicitantes)); // create the filter
            TSession::setValue('TicketList_filter_entidade_id',   $filter); // stores the filter in the session
        }

        if (isset($data->status_ticket_id) AND ($data->status_ticket_id)) {
            $filter = new TFilter('status_ticket_id', '=', "$data->status_ticket_id"); // create the filter
            TSession::setValue('TicketList_filter_status_ticket_id',   $filter); // stores the filter in the session
        }

        if (isset($data->tipo_ticket_id) AND ($data->tipo_ticket_id)) {
            $filter = new TFilter('tipo_ticket_id', '=', "$data->tipo_ticket_id"); // create the filter
            TSession::setValue('TicketList_filter_tipo_ticket_id',   $filter); // stores the filter in the session
        }

        if (isset($data->proprietario_id) AND ($data->proprietario_id)) {
            $filter = new TFilter('proprietario_id', '=', "$data->proprietario_id"); // create the filter
            TSession::setValue('TicketList_filter_proprietario_id',   $filter); // stores the filter in the session
        }

        if (isset($data->responsavel_id) AND ($data->responsavel_id)) {
            $filter = new TFilter('responsavel_id', '=', "$data->responsavel_id"); // create the filter
            TSession::setValue('TicketList_filter_responsavel_id',   $filter); // stores the filter in the session
        }

        if (isset($data->prioridade_id) AND ($data->prioridade_id)) {
            $filter = new TFilter('prioridade_id', '=', "$data->prioridade_id"); // create the filter
            TSession::setValue('TicketList_filter_prioridade_id',   $filter); // stores the filter in the session
        }
        
        if (isset($data->sistema_id) AND ($data->sistema_id)) {
            $filter = new TFilter('sistema_id', '=', "$data->sistema_id"); // create the filter
            TSession::setValue('TicketList_filter_sistema_id',   $filter); // stores the filter in the session
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('Ticket_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * method onReload()
     * Load the datagrid with the database objects
     */
    function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'atividade'
            TTransaction::open('atividade');
            
            // creates a repository for Ticket
            $repository = new TRepository('Ticket');
            $limit = 15;
            // creates a criteria
            $criteria = new TCriteria;
            
            $newparam = $param; // define new parameters
            if (isset($newparam['order']) AND $newparam['order'] == 'prioridade->nome')
            {
                $newparam['order'] = '(select nome from prioridade where prioridade_id = id)';
            }
            
            if (isset($newparam['order']) AND $newparam['order'] == 'solicitante_id')
            {
                $newparam['order'] = '(select 
                                        CASE 	WHEN p.origem = 1 THEN e.entnomfan 
                                        	WHEN p.origem = 2 THEN l.lojnomfan 
                                        	WHEN p.origem = 3 THEN t.razao_social
                                        END as solicitante_id
                                        from tbz_pessoas as p 
                                        left join car200 as e on e.entcodent = p.codigo_cadastro_origem
                                        left join car800 as l on l.lojcodloj = p.codigo_cadastro_origem
                                        left join tbz_empresa as t on t.id = p.codigo_cadastro_origem
                                        where pessoa_codigo = solicitante_id)';
            }
            
            if (isset($newparam['order']) AND $newparam['order'] == 'data_cadastro') {
                $newparam['order'] = '(select CASE 	
                                            WHEN data_cancelamento is not null THEN data_cancelamento - data_cadastro 
                                        	WHEN data_encerramento is not null THEN data_encerramento - data_cadastro
                                        	ELSE current_date - data_cadastro
                                        END as dias
                                        from ticket as t
                                        where ticket.id = t.id)';
                 $newparam['direction'] = 'desc';
            }
            
            if (isset($newparam['order']) AND $newparam['order'] == 'pessoa_responsavel->pessoa_nome')
            {
                $newparam['order'] = '(select pessoa_nome from tbz_pessoas where pessoa_codigo = responsavel_id)';
            }
            
            
            // default order
            if (empty($newparam['order']))
            {
                $newparam['order'] = 'id';
                $newparam['direction'] = 'desc';
            }
            
            $criteria->setProperties($newparam); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue('TicketList_filter_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_titulo')) {
                $criteria->add(TSession::getValue('TicketList_filter_titulo')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_solicitante_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_solicitante_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_entidade_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_entidade_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_status_ticket_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_status_ticket_id')); // add the session filter
            }
            
            if (TSession::getValue('TicketList_filter_tipo_ticket_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_tipo_ticket_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_proprietario_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_proprietario_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_responsavel_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_responsavel_id')); // add the session filter
            }

            if (TSession::getValue('TicketList_filter_prioridade_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_prioridade_id')); // add the session filter
            }
            
            if (TSession::getValue('TicketList_filter_sistema_id')) {
                $criteria->add(TSession::getValue('TicketList_filter_sistema_id')); // add the session filter
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            $this->datagrid->clear();
            
             try
             {
                if ($objects)
                {
                    // iterate the collection of active records
                    foreach ($objects as $object)
                    {
                        // add the object inside the datagrid
                        $this->datagrid->addItem($object);
                    }
                }
             }
             catch(Exception $e)
             {
                 new TMessage('error', '<b>Error</b> ' . $e->getMessage());
             }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->onTotalTickets($count);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    
    public static function onTotalTickets($param)
    {
        $obj = new StdClass;
        $obj->total_registros         = $param;  
        TForm::sendData('form_Ticket', $obj, FALSE, FALSE);
    }
    
    /**
     * method onDelete()
     * executed whenever the user clicks at the delete button
     * Ask if the user really wants to delete the record
     */
    function onDelete($param)
    {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(TAdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * method Delete()
     * Delete a record
     */
    function Delete($param)
    {
        try
        {
            $key=$param['key']; // get the parameter $key
            TTransaction::open('atividade'); // open a transaction with database
            $object = new Ticket($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object from the database
            TTransaction::close(); // close the transaction
            $this->onReload( $param ); // reload the listing
            new TMessage('info', TAdiantiCoreTranslator::translate('Record deleted')); // success message
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method show()
     * Shows the page
     */
    function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }
    
    public function onClean()
    {
         
        // clear session filters
        TSession::setValue('TicketList_filter_id',   NULL);
        TSession::setValue('TicketList_filter_titulo',   NULL);
        TSession::setValue('TicketList_filter_solicitante_id',   NULL);
        TSession::setValue('TicketList_filter_entidade_id',   NULL);
        TSession::setValue('TicketList_filter_status_ticket_id',   NULL);
        TSession::setValue('TicketList_filter_tipo_ticket_id',   NULL);
        TSession::setValue('TicketList_filter_proprietario_id',   NULL);
        TSession::setValue('TicketList_filter_responsavel_id',   NULL);
        TSession::setValue('TicketList_filter_prioridade_id',   NULL);
        TSession::setValue('TicketList_filter_sistema_id',   NULL);
         
        $this->form->clear();

        $this->onReload( );
         
    }
    
    public function retornaCliente($campo, $object, $row)
    {
         $cliente = new Pessoa($campo);
         return $cliente->origem_nome;
    }
    
    public function retornaPessoa($campo, $object, $row)
    {
        return substr($campo,0,10);         
    }
    
    public function retornaPrioridade($campo, $object, $row)
    {
        return substr($campo,0,1);         
    }
    
    public function retornaStatus($campo, $object, $row)
    {
         $status = array(1 => 'Ativo', 2 => 'Pendente', 3 => 'Encerrado', 4 => 'Cancelado', 5 => 'Fixo', 6 => 'Cobrança');           
        
         $row->popover = 'true';
         $row->popcontent = "<table class='popover-table' border='0'><tr><td>Status: {$status[$object->status_ticket_id]}</td></tr></table>";
         $row->poptitle = 'Ticket: '.$object->titulo;
         
         $campo = new TImage($object->status_ticket_id.'.png');
         $campo->height=15;
         $campo->width=15;
         return $campo;
    }
    
    public function retornaDias($campo, $object, $row)
    {
         $string = new StringsUtil;
         $dias = 0;
         if($object->data_cancelamento){
             $dias = $string->subtrair_datas($object->data_cadastro, $object->data_cancelamento);
         } elseif($object->data_encerramento){
             $dias = $string->subtrair_datas($object->data_cadastro, $object->data_encerramento);
         } else {
             $dias = $string->subtrair_datas($object->data_cadastro, date('Y-m-d'));
         }
         
         $campo = $dias;
         return $campo;
    }
     
}
