<?php
/**
 * DatagridInputDialogView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class PessoaDepartamentoList extends TPage
{
    private $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        // creates one datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setHeight(320);

        
        // add the columns
        
        $dg_id             = new TDataGridColumn('id',               'ID',              'center', 30);
        $dg_colaborador    = new TDataGridColumn('colaborador_id',   'Colaborador',     'center', 280);
        $dg_departamento   = new TDataGridColumn('departamento_id',  'Departamento',    'center', 180);
        
        $dg_colaborador->setTransformer(array($this, 'retornaColaborador'));
        $dg_departamento->setTransformer(array($this, 'retornaDepartamento'));

        $this->datagrid->addColumn($dg_id);
        $this->datagrid->addColumn($dg_colaborador);
        $this->datagrid->addColumn($dg_departamento);
                
        // add the actions
        //$this->datagrid->addQuickAction('Input',   new TDataGridAction(array($this, 'onInputDialog')), 'name', 'fa:external-link');
        $action            = new TDataGridAction(array($this, 'onInputDialog'));
        $action->setField('colaborador_id');
        $action->setImage('fa:external-link');
        $this->datagrid->addAction($action);
        
        // creates the datagrid model
        $this->datagrid->createModel();
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        
        $vbox->add($this->datagrid);

        parent::add($vbox);
    }
    
    public function retornaColaborador($campo, $object, $row)
    {
        try
        {
            TTransaction::open('atividade');
            
            $repository = new TRepository('Pessoa');
            $criteria = new TCriteria;
            $criteria->add(new TFilter("pessoa_codigo", "=", $object->colaborador_id));
        
            $pessoas = $repository->load($criteria);
            
            $pessoa = $pessoas[0]->pessoa_nome;
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
        }
        
        return $pessoa;
                 
    }
    
    
    public function retornaDepartamento($campo, $object, $row)
    {
        try
        {
            TTransaction::open('atividade');
            
            $repository = new TRepository('Departamento');
            $criteria = new TCriteria;
            $criteria->add(new TFilter("id", "=", $object->departamento_id));
        
            $deptos = $repository->load($criteria);
            
            $depto = $deptos[0]->nome;
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
        }
        
        return $depto;
                 
    }
    
    /**
     * Load the data into the datagrid
     */
    function onReload()
    {
        $this->datagrid->clear();
        
        try
        {
            TTransaction::open('atividade');
            
            $repository = new TRepository('Pessoa');
            $criteria = new TCriteria;
            $criteria->add(new TFilter("origem", "=", 1));
            $criteria->add(new TFilter("ativo", "=", 1));
            $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
            $criteria->setProperty('order', 'pessoa_nome asc');
            $pessoas = $repository->load($criteria);
            
            if($pessoas){
                foreach($pessoas as $pessoa){
                    
                    $repo = new TRepository('PessoaDepartamento');
                    $cri = new TCriteria;
                    $cri->add(new TFilter("colaborador_id", "=", $pessoa->pessoa_codigo));
                    $pd = $repo->load($cri);
                    
                    $item = new StdClass;
                    $item->id = $pd[0]->id;
                    $item->colaborador_id = $pessoa->pessoa_codigo;
                    $item->departamento_id = $pd[0]->departamento_id;
                    
                    $this->datagrid->addItem($item);            
                }                
                            
            }
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
        }

    }
    
    /**
     * Open an input dialog
     */
    public function onInputDialog( $param )
    {
                
        //new TMessage('info', 'colaborador: '.$param['colaborador_id']);
        
        $colaborador = $this->retornaColaborador(null, (object)$param, null);

        TTransaction::open('atividade');
        $repo = new TRepository('PessoaDepartamento');
        $cri = new TCriteria;
        $cri->add(new TFilter("colaborador_id", "=", $param['colaborador_id']));
        $pd = $repo->load($cri);
        TTransaction::close();

        $pdid               = new THidden('id');
        $pdid->setValue($pd[0]->id);
        $cola               = new THidden('colaborador_id');
        $cola->setValue($param['colaborador_id']);
        $name               = new TEntry('colaborador_nome');
        $name->setEditable(FALSE);
        $name->setValue($colaborador);
        $dpto               = new TDBCombo('departamento_id', 'atividade', 'Departamento', 'id', 'nome', 'nome');  
        $dpto->setValue($pd[0]->departamento_id);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;

        $table->addRowSet( new TLabel('Colaborador: '), $name );
        $table->addRowSet( $lbl = new TLabel('Departamento: '), $dpto );
        $table->addRowSet( new TLabel(''), array($pdid, $cola  ));
        
        
        $form->setFields(array($pdid, $cola, $name, $dpto));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'onConfirm'));
    //    $action->setParameter('stay-open', 1);
        new TInputDialog('Colaborador Departamento', $form, $action, 'Confirm');
       
    }
    
    /**
     * Show the input dialog data
     */
    public static function onConfirm( $param )
    {
      
        TTransaction::open('atividade');
     
        $pd = new PessoaDepartamento($param['id']);
     
        $pd->departamento_id     = $param['departamento_id'];
        $pd->colaborador_id      = $param['colaborador_id'];
     
        $pd->store();
        
        TTransaction::close();
        
        TApplication::gotoPage('PessoaDepartamentoList', 'onReload', null); // reload
    
    }
    
    /**
     * shows the page
     */
    function show()
    {
        $this->onReload();
        parent::show();
    }
}
