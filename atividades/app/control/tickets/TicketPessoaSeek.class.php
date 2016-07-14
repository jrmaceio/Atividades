 <?php

class TicketPessoaSeek extends TWindow
{
    private $form;      // form
    private $datagrid;  // datagrid
    private $pageNavigation;
    private $parentForm;
    private $loaded;
    
    /**
     * constructor method
     */
    public function __construct()
    {
        parent::__construct();
        parent::setTitle('Busca de Pessoas');
        parent::setSize(800,600);
        new TSession;
        
        // creates the form
        $this->form = new TForm('form_city_Pessoa');
        // creates the table
        $table = new TTable;
        
        // add the table inside the form
        $this->form->add($table);

        // create the form fields
        $name= new TEntry('pessoa_nome');
        $origem= new TEntry('pessoa_origem');
        // keep the session value
        $name->setValue(TSession::getValue('test_pessoa_name'));
        $origem->setValue(TSession::getValue('test_pessoa_origem'));
        
        // add the field inside the table
        $row=$table->addRow();
        $row->addCell(new TLabel('Nome:'));
        $row->addCell($name);
        $row=$table->addRow();
        $row->addCell(new TLabel('Origem:'));
        $row->addCell($origem);
        
        // create a find button
        $find_button = new TButton('search');
        // define the button action
        $find_button->setAction(new TAction(array($this, 'onSearch')), 'Search');
        $find_button->setImage('ico_find.png');
        
        // add a row for the find button
        $row=$table->addRow();
        $row->addCell($find_button);
        
        // define wich are the form fields
        $this->form->setFields(array($name, $origem, $find_button));
        
        // create the datagrid
        $this->datagrid = new TDataGrid;
        
        // create the datagrid columns
        $id    = new TDataGridColumn('pessoa_codigo',    'Id',   'right',   25);
        $name  = new TDataGridColumn('pessoa_nome',  'Nome', 'left',   250);
        $origem = new TDataGridColumn('origem_nome', 'Origem', 'left',  330);
        
        $order1= new TAction(array($this, 'onReload'));
        $order2= new TAction(array($this, 'onReload'));
        
        $order1->setParameter('order', 'pessoa_codigo');
        $order2->setParameter('order', 'pessoa_nome');
        
        // define the column actions
        $id->setAction($order1);
        $name->setAction($order2);
        
        // add the columns inside the datagrid
        $this->datagrid->addColumn($id);
        $this->datagrid->addColumn($name);
        $this->datagrid->addColumn($origem);
        
        // create one datagrid action
        $action1 = new TDataGridAction(array($this, 'onSelect'));
        $action1->setLabel('Selecionar');
        $action1->setImage('fa:check-circle-o green');
        $action1->setField('pessoa_codigo');
        
        // add the action to the datagrid
        $this->datagrid->addAction($action1);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigator
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // create a table for layout
        $table = new TTable;
        // create a row for the form
        $row = $table->addRow();
        $row->addCell($this->form);
        
        // create a row for the datagrid
        $row = $table->addRow();
        $row->addCell($this->datagrid);
        
        // create a row for the page navigator
        $row = $table->addRow();
        $row->addCell($this->pageNavigation);
        
        $table->style = 'width: 100%;max-width: 1200px;';
        $this->datagrid->style = '  width: 100%;  max-width: 1200px;';
        
        // add the table inside the page
        parent::add($table);
    }
    
    /**
     * Register a filter in the session
     */
    function onSearch()
    {
        // get the form data
        $data = $this->form->getData();
        
        // check if the user has filled the fields
        if (isset($data->pessoa_nome))
        {
            // cria um filtro pelo conteúdo digitado
            $filter = new TFilter('pessoa_nome', 'ilike', "%{$data->pessoa_nome}%");
            
            // armazena o filtro na seção
            TSession::setValue('test_pessoa_filter', $filter);
            TSession::setValue('test_pessoa_name', $data->pessoa_nome);
            
            // put the data back to the form
            $this->form->setData($data);
        }
        
        
        
        if (isset($data->pessoa_origem))
        {
            TSession::setValue('test_pessoa_origem', $data->pessoa_origem);
            // put the data back to the form
            $this->form->setData($data);
        }
        
        // redefine the parameters for reload method
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with the database objects
     */
    function onReload($param = NULL)
    {
        try
        {
            // start database transaction
            TTransaction::open('atividade');
            $clientes = Ticket::retornaClientesTickets();
            
            // create a repository for City table
            $repository = new TRepository('Pessoa');
            $limit = 10;
            // creates a criteria
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'pessoa_nome';
                $param['direction'] = 'asc';
            }
            
            $criteria = new TCriteria;
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            $criteria->add(new TFilter("ativo", "=", 1));
                        
            if (TSession::getValue('test_pessoa_filter'))
            {
                // filter by city name
                $criteria->add(TSession::getValue('test_pessoa_filter'));
            }
            
            if(TSession::getValue('test_pessoa_origem'))
            {
                $origem_nome = TSession::getValue('test_pessoa_origem');
                $pessoas = array();
                $repo = new TRepository('Entidade');
                $cri = new TCriteria;
                $cri->add(new TFilter("entnomfan", "ilike", "%{$origem_nome}%"));
                $origens = $repo->load($cri);
                 if($origens){
                     $entidades = array();
                     foreach($origens as $origem){
                         $entidades[] = $origem->entcodent;   
                     }                     
                     $repo = new TRepository('Pessoa');
                     $cri = new TCriteria;
                     $cri->add(new TFilter("origem", "=", 1));
                     $cri->add(new TFilter("codigo_cadastro_origem", "IN", $entidades));
                     $load = $repo->load($cri);
                     foreach ($load as $p){
                         $pessoas[] = $p->pessoa_codigo;
                     }
                 }
            
                $repo = new TRepository('Estabelecimento');
                $cri = new TCriteria;
                $cri->add(new TFilter("lojnomfan", "ilike", "%{$origem_nome}%"));
                $origens = $repo->load($cri);
                 if($origens){
                     $estabelecimentos = array();
                     foreach($origens as $origem){
                         $estabelecimentos[] = $origem->lojcodloj;   
                     }
                     $repo = new TRepository('Pessoa');
                     $cri = new TCriteria;
                     $cri->add(new TFilter("origem", "=", 2));
                     $cri->add(new TFilter("codigo_cadastro_origem", "IN", $estabelecimentos));
                     $load = $repo->load($cri);
                     foreach ($load as $p){
                         $pessoas[] = $p->pessoa_codigo;
                     }
                 }
            
                $repo = new TRepository('Empresa');
                $cri = new TCriteria;
                $cri->add(new TFilter("razao_social", "ilike", "%{$origem_nome}%"));
                $origens = $repo->load($cri);
                 if($origens){
                     $empresas = array();
                     foreach($origens as $origem){
                         $empresas[] = $origem->id;   
                     }
                     $repo = new TRepository('Pessoa');
                     $cri = new TCriteria;
                     $cri->add(new TFilter("origem", "=", 3));
                     $cri->add(new TFilter("codigo_cadastro_origem", "IN", $empresas));
                     $load = $repo->load($cri);
                     foreach ($load as $p){
                         $pessoas[] = $p->pessoa_codigo;
                     }
                 }
            
                 $clientes = array_intersect($pessoas, $clientes);
            
            }
            
            $criteria->add(new TFilter("pessoa_codigo", "IN", $clientes));
            
            // load the objects according to the criteria
            $pessoas = $repository->load($criteria);
            $this->datagrid->clear();
            if ($pessoas)
            {
                foreach ($pessoas as $pessoa)
                {
                    // add the objects inside the datagrid
                    $this->datagrid->addItem($pessoa);
                }
            }
            
            // clear the criteria
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // commit and closes the database transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // exceptions
        {
            // show the error message
            new TMessage('error', '<b>Erro</b> ' . $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Executed when the user chooses the record
     */
    function onSelect($param)
    {
        try
        {
            $key = $param['key'];
            TTransaction::open('atividade');
            
            // load the active record
            $pessoa = new Pessoa($key);

            
            $object = new StdClass;
            $object->solicitante_id   = $pessoa->pessoa_codigo;
            $object->solicitante_nome = $pessoa->pessoa_nome;

            // closes the transaction
            TTransaction::close();
            
            TForm::sendData('form_Ticket', $object);
            parent::closeWindow(); // closes the window
        }
        catch (Exception $e) // em caso de exceção
        {
            // clear fields
            $object = new StdClass;
            $object->solicitante_id   = '';
            $object->solicitante_nome = '';
            $object->origem_nome      = '';
            TForm::sendData('form_Ticket', $object);
            
            // undo pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Shows the page
     */
    function show()
    {
        // if the datagrid was not loaded yet
        if (!$this->loaded)
        {
            $this->onReload();
        }
        parent::show();
    }
}
?>
