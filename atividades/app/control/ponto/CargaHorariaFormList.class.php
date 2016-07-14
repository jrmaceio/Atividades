<?php
/**
 * CargaHorariaFormList Registration
 * @author  <your name here>
 */
class CargaHorariaFormList extends TPage
{
    protected $form; // form
    protected $datagrid; // datagrid
    protected $pageNavigation;
    protected $loaded;
    private $string;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_CargaHoraria');
        $this->form->class = 'tform'; // CSS class
        $this->form->setFormTitle('Carga Horaria'); // define the form title
        $this->string = new StringsUtil;

        // create the form fields
        $mes                            = new TCombo('mes');
        $mes->addItems($this->string->array_meses());
        $mes->setDefaultOption(FALSE);
        $mes->setValue(date('m'));
        $mes->setSize(100);
        $ano                            = new TCombo('ano');
        $anos                           = array(2015 => '2015' , 2016 => '2016');         
        $ano->addItems($anos);
        $ano->setDefaultOption(FALSE);
        $ano->setSize(70);  
        $qtde_horas                     = new TCombo('qtde_horas');
        $qtde_minutos                   = new TCombo('qtde_minutos');
        
        // cria combos de horas e minutos
        $combo_horas       = array();
        for($i = 0; $i <= 300; $i++)
        {
             $combo_horas[$i]         = str_pad($i, 2, 0, STR_PAD_LEFT) ;
        }
        $qtde_horas->addItems($combo_horas);
        $qtde_horas->setValue(180);
        $qtde_horas->setSize(60);
        $qtde_horas->setDefaultOption(FALSE);
        $combo_minutos       = array();
        for($i = 0; $i <= 59; $i++)
        {
             $combo_minutos[$i] = str_pad($i, 2, 0, STR_PAD_LEFT) ;   
        }
        $qtde_minutos->addItems($combo_minutos);
        $qtde_minutos->setValue(0);
        $qtde_minutos->setSize(60);
        $qtde_minutos->setDefaultOption(FALSE);

        // add the fields
        $this->form->addQuickFields('Mês/Ano', array($mes, $ano));
        $this->form->addQuickFields('Carga horaria', array($qtde_horas, $qtde_minutos));

        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('Find'),  new TAction(array($this, 'onReload')), 'ico_find.png');
        
        // creates a DataGrid
        $this->datagrid = new TQuickGrid;
        $this->datagrid->setHeight(320);
        
        // creates the datagrid columns
        $horario = $this->datagrid->addQuickColumn('horario', 'horario', 'left', 50);
        $colaborador_id = $this->datagrid->addQuickColumn('colaborador', 'pessoa->pessoa_nome', 'left', 100);        
        $mes = $this->datagrid->addQuickColumn('mes', 'mes', 'left', 100);
        $ano = $this->datagrid->addQuickColumn('ano', 'ano', 'left', 50);

        $mes->setTransformer(array('StringsUtil', 'retorna_mes'));
        $horario->setTransformer(array('StringsUtil', 'retira_segundos'));
        $colaborador_id->setTransformer(array($this, 'retornaPessoa'));
        
        // creates the edit action
        $editaction = new TDataGridAction(array($this, 'onEdit'));
        $editaction->setField('id');
        $horario->setEditAction($editaction);
        
        // create the datagrid actions
        $delete_action = new TDataGridAction(array($this, 'onDelete'));
        
        // add the actions to the datagrid
        $this->datagrid->addQuickAction(_t('Delete'), $delete_action, 'id', 'fa:trash-o red fa-lg');
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // create the page container
        $container = TVBox::pack( $this->form, $this->datagrid, $this->pageNavigation);
        
        parent::add($container);
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
                        
            $criteria = new TCriteria;
            $criteria->add(new TFilter("origem", "=", 1));
            $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
            $criteria->add(new TFilter("ativo", "=", 1));
            $criteria->add(new TFilter("usuario", "is not "));
            $repo = new TRepository('Pessoa');
            $count = $repo->count($criteria);
            
            // creates a repository for CargaHoraria
            $repository = new TRepository('CargaHoraria');
            $limit = $count;
            // creates a criteria
            $criteria = new TCriteria;
            
            !$param['ano'] ? $param['ano'] = date('Y') : $param['ano'] = $param['ano'];
            !$param['mes'] ? $param['mes'] = date('m') : $param['mes'] = $param['mes'];
            
            $criteria->add(new TFilter("mes", "=", $param['mes']));
            $criteria->add(new TFilter("ano", "=", $param['ano']));
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            if (TSession::getValue('CargaHoraria_filter'))
            {
                // add the filter stored in the session to the criteria
                $criteria->add(TSession::getValue('CargaHoraria_filter'));
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            $obj = new StdClass;
            $obj->mes = $param['mes'];
            $obj->ano = $param['ano'];
            
            $this->form->setData($obj); // fill the form with the active record data
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
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
            // get the parameter $key
            $key=$param['key'];
            
            TTransaction::open('atividade'); // open the transaction
            $object = new CargaHoraria($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object
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
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    function onSave()
    {
        try
        {
            TTransaction::open('atividade'); // open a transaction with database
            
            // get the form data into an active record CargaHoraria
            $object = $this->form->getData('CargaHoraria');

            $object->horario = $object->qtde_horas.':'.str_pad($object->qtde_minutos, 2, 0, STR_PAD_LEFT);
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter("mes", "=", $object->mes));
            $criteria->add(new TFilter("ano", "=", $object->ano));
            $repo = new TRepository('CargaHoraria');
            $horarios = $repo->delete($criteria);
                    
            $criteria = new TCriteria;
            $criteria->add(new TFilter("origem", "=", 1));
            $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
            $criteria->add(new TFilter("ativo", "=", 1));
            $criteria->add(new TFilter("usuario", "is not "));
            
            $repo = new TRepository('Pessoa');
            $pessoas = $repo->load($criteria);

            foreach($pessoas as $pessoa)
            {
                $cargaHoraria = new CargaHoraria();
                $cargaHoraria->mes = $object->mes;
                $cargaHoraria->ano = $object->ano;
                $cargaHoraria->horario = $object->horario;
                $cargaHoraria->colaborador_id = $pessoa->pessoa_codigo;
                
                $cargaHoraria->store(); // stores the object               
            }
            new TMessage('info', 'Registros salvos'); // success message
            $this->form->setData($object); // fill the form with the active record data
            
            TTransaction::close(); // close the transaction
            
            $param['mes'] = $object->mes;
            $param['ano'] = $object->ano;
            
            $this->onReload($param); // reload the listing
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method onEdit()
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                
                $key=$param['key']; // get the parameter $key
                
                $field = $param['field'];
                $value = $param['value'];
                $tempo = explode(':', $value);
                $result = count($tempo);
                
                if($result == 1) {
                    $value = $tempo[0].':00';
                }
                
                if($result < 0 || $result > 2){
                    $erro = 'horário inválido';
                } else if (!is_numeric($tempo[0])){
                    $erro = 'hora não numérica';
                } else if (strlen($tempo[0]) > 3 || strlen($tempo[0]) < 1){
                    $erro = 'hora inválida';
                } else if ($result == 2){
                    if (!is_numeric($tempo[1])){
                        $erro = 'minuto nao numerico';
                    } else if ($tempo[1] > 59){
                        $erro = 'minuto inválido';
                    } 
                }
                
                if($erro)
                {
                    new TMessage('error', '<b>Error:</b> '.$erro);                
                }
                else
                {
                
                    TTransaction::open('atividade'); // open a transaction with the database
                    $object = new CargaHoraria($key); // instantiates the Active Record
                    $object->{$field} = $value;
                    
                    $object->store();
                    
                    //$this->form->setData($object); // fill the form with the active record data
                    TTransaction::close(); // close the transaction
                    
                    $this->onReload(); // reload the listing
                
                }
                                
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method show()
     * Shows the page e seu conteÃºdo
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
    
    public function retornaPessoa($campo, $object, $row)
    {
        return substr($campo,0,10);         
    }
}
