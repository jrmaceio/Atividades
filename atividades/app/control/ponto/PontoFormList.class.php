<?php
/**
 * PontoFormList Registration
 * @author  <your name here>
 */
class PontoFormList extends TPage
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
        $this->form = new TQuickForm('form_Ponto');
        $this->form->class = 'tform'; // CSS class
        $this->form->setFormTitle('Ponto'); // define the form title
        
        $this->string = new StringsUtil;
        
        // create the form fields
        $id                             = new THidden('id');
        $data_ponto                     = new TDate('data_ponto');
        $data_ponto->setMask('dd/mm/yyyy');
        
        $change_data_action = new TAction(array($this, 'onChangeDataAction'));
        $data_ponto->setExitAction($change_data_action);
        
        $hora_entrada                   = new THidden('hora_entrada');               
        $hora_saida                     = new THidden('hora_saida');
        $hora_entrada_tarde             = new THidden('hora_entrada_tarde');
        $hora_saida_tarde               = new THidden('hora_saida_tarde');
        
        $qtde_horas                     = new TCombo('qtde_horas');
        $change_qtde_horas = new TAction(array($this, 'onChangeHoraEntradaPrimeiroTurno'));
        $qtde_horas->setChangeAction($change_qtde_horas);
        
        $qtde_minutos                   = new TCombo('qtde_minutos');
        $change_qtde_minutos = new TAction(array($this, 'onChangeMinutoEntradaPrimeiroTurno'));
        $qtde_minutos->setChangeAction($change_qtde_minutos);
        
        $qtde_horas_final               = new TCombo('qtde_horas_final');
        $change_qtde_horas_final = new TAction(array($this, 'onChangeHoraSaidaPrimeiroTurno'));
        $qtde_horas_final->setChangeAction($change_qtde_horas_final);
        
        $qtde_minutos_final             = new TCombo('qtde_minutos_final');
        $change_qtde_minutos_final = new TAction(array($this, 'onChangeMinutoSaidaPrimeiroTurno'));
        $qtde_minutos_final->setChangeAction($change_qtde_minutos_final);
                
        $qtde_horas_tarde               = new TCombo('qtde_horas_tarde');
        $change_qtde_horas_tarde = new TAction(array($this, 'onChangeHoraEntradaSegundoTurno'));
        $qtde_horas_tarde->setChangeAction($change_qtde_horas_tarde);
        
        $qtde_minutos_tarde             = new TCombo('qtde_minutos_tarde');
        $change_qtde_minutos_tarde = new TAction(array($this, 'onChangeMinutoEntradaSegundoTurno'));
        $qtde_minutos_tarde->setChangeAction($change_qtde_minutos_tarde);
        
        $qtde_horas_final_tarde         = new TCombo('qtde_horas_final_tarde');
        $change_qtde_horas_final_tarde = new TAction(array($this, 'onChangeHoraSaidaSegundoTurno'));
        $qtde_horas_final_tarde->setChangeAction($change_qtde_horas_final_tarde);
        
        $qtde_minutos_final_tarde       = new TCombo('qtde_minutos_final_tarde');
        $change_qtde_minutos_final_tarde = new TAction(array($this, 'onChangeMinutoSaidaSegundoTurno'));
        $qtde_minutos_final_tarde->setChangeAction($change_qtde_minutos_final_tarde);
        
        $colaborador_id                 = new THidden('colaborador_id');
        TTransaction::open('atividade');
        $logado = Pessoa::retornaUsuario();
        $saldo_mes = Ponto::saldoHorasMes($logado->pessoa_codigo);
        TTransaction::close();
        $colaborador_id->setValue($logado->pessoa_codigo);
        $colaborador_nome               = new TEntry('colaborador_nome');
        $colaborador_nome->setEditable(FALSE);
        $colaborador_nome->setValue($logado->pessoa_nome);
        $saldo_horas                    = new TEntry('saldo_horas');
        $saldo_horas->setEditable(FALSE);
        $saldo_horas->setValue($saldo_mes);
        
        // cria combos de horas e minutos
        $combo_horas       = array();
        $combo_horas_final = array();
        for($i = 8; $i <= 18; $i++)
        {
             $combo_horas[$i]         = str_pad($i, 2, 0, STR_PAD_LEFT) ;
             $combo_horas_final[$i]   = str_pad($i, 2, 0, STR_PAD_LEFT) ;
        }
        $combo_horas_final[19]        = ('19');
        
        $qtde_horas->addItems($combo_horas);
        $qtde_horas->setSize(60);
        $qtde_horas_final->addItems($combo_horas_final);
        $qtde_horas_final->setSize(60);
                
        $qtde_horas_tarde->addItems($combo_horas);
        $qtde_horas_tarde->setSize(60);
        $qtde_horas_final_tarde->addItems($combo_horas_final);
        $qtde_horas_final_tarde->setSize(60);
                
                
        $combo_minutos       = array();
        $combo_minutos_final = array();
        for($i = 0; $i <= 59; $i++)
        {
             $combo_minutos[$i] = str_pad($i, 2, 0, STR_PAD_LEFT) ;
             $combo_minutos_final[$i] = str_pad($i, 2, 0, STR_PAD_LEFT) ;     
        }
        $qtde_minutos->addItems($combo_minutos);
        $qtde_minutos->setSize(60);
        $qtde_minutos_final->addItems($combo_minutos_final);
        $qtde_minutos_final->setSize(60);
                
        $qtde_minutos_tarde->addItems($combo_minutos);
        $qtde_minutos_tarde->setSize(60);     
        $qtde_minutos_final_tarde->addItems($combo_minutos_final);
        $qtde_minutos_final_tarde->setSize(60);
                
        // validations
        $data_ponto->addValidation('Data', new TRequiredValidator);
        
        // add the fields
        $this->form->addQuickField('Colaborador', $colaborador_nome,  200);
        $this->form->addQuickField('Data', $data_ponto,  100);
        $this->form->addQuickFields('1º turno entrada', array($qtde_horas, $qtde_minutos));
        $this->form->addQuickFields('1º turno saida', array($qtde_horas_final, $qtde_minutos_final));
        
        $this->form->addQuickFields('2º turno entrada', array($qtde_horas_tarde, $qtde_minutos_tarde));
        $this->form->addQuickFields('2º turno saida', array($qtde_horas_final_tarde, $qtde_minutos_final_tarde));
        
        $this->form->addQuickField('Saldo no mês:', $saldo_horas, 125);
        $this->form->addQuickField('% Produtividade', new TLabel('<span style="background-color: #00B4FF;"><b>> 59% satisfatoria&nbsp;&nbsp;</b></span><br/><span style="background-color: #FFF800;"><b>< 60% - Atenção</b></span>'), 200);  
        $this->form->addQuickField('', $hora_entrada,  200);
        $this->form->addQuickField('', $hora_saida,  200);
        $this->form->addQuickField('', $hora_entrada_tarde,  200);
        $this->form->addQuickField('', $hora_saida_tarde,  200);
        $this->form->addQuickField('', $colaborador_id,  100);
        $this->form->addQuickField('', $id,  100);
      
        // create the form actions
        $this->form->addQuickAction('Salvar', new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onEdit')), 'fa:plus-square green');
        $this->form->addQuickAction('Excluir',  new TAction(array($this, 'onDelete')), 'fa:trash-o red fa-lg');
        
        TButton::disableField('form_Ponto', 'salvar');
        TButton::disableField('form_Ponto', 'excluir');
        
        // creates a DataGrid
        $this->datagrid = new TQuickGrid;
        $this->datagrid->setHeight(380);
        
        // creates the datagrid columns
        $data_ponto = $this->datagrid->addQuickColumn('Data', 'data_ponto', 'left', 50);
        $hora_entrada = $this->datagrid->addQuickColumn('1º Ent', 'hora_entrada', 'left', 40);
        $hora_saida = $this->datagrid->addQuickColumn('1º Sai', 'hora_saida', 'left', 40);
        
        $hora_entrada_tarde = $this->datagrid->addQuickColumn('2º Ent', 'hora_entrada_tarde', 'left', 40);
        $hora_saida_tarde = $this->datagrid->addQuickColumn('2º Sai', 'hora_saida_tarde', 'left', 40);
                
        $hora_ponto = $this->datagrid->addQuickColumn('H.Pto', 'hora_ponto', 'left', 30);
        $intervalo = $this->datagrid->addQuickColumn('Ativid.', 'intervalo', 'right', 30);
        $produtividade = $this->datagrid->addQuickColumn('% prod.', 'produtividade', 'right', 55);
        
        // transformers
        $hora_entrada->setTransformer(array($this, 'tiraSegundos'));
        $hora_saida->setTransformer(array($this, 'tiraSegundos'));
        $hora_entrada_tarde->setTransformer(array($this, 'tiraSegundos'));
        $hora_saida_tarde->setTransformer(array($this, 'tiraSegundos'));
        $hora_ponto->setTransformer(array($this, 'calculaDiferenca'));
        $intervalo->setTransformer(array($this, 'retornaIntervalo'));
        $produtividade->setTransformer(array($this, 'calculaPercentualProdutividade'));
        
        // create the datagrid actions
        $edit_action   = new TDataGridAction(array($this, 'onEdit'));
        $delete_action = new TDataGridAction(array($this, 'onDelete'));
        
        // add the actions to the datagrid
        $this->datagrid->addQuickAction(_t('Edit'), $edit_action, 'id', 'fa:pencil-square-o blue fa-lg');
        
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

    public static function onChangeHoraSaidaSegundoTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_horas_final_tarde'])){
            $obj = new StdClass;
            if(!$param['qtde_horas_tarde']){
                $obj->qtde_horas_final_tarde = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um ponto com entrada de 2° turno em aberto');
            } else {
                if($param['qtde_horas_tarde'] > $param['qtde_horas_final_tarde']){
                    $obj->qtde_horas_final_tarde = '';
                    TButton::disableField('form_Ponto', 'salvar');
                    new TMessage('error', 'Horario de saida não pode ser menor que o horario de entrada');
                }        
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE); 
        } else {
            if(is_numeric($param['qtde_minutos_final_tarde'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }
    
    public static function onChangeMinutoSaidaSegundoTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_minutos_final_tarde'])){
            $obj = new StdClass;
            if(!$param['qtde_horas_final_tarde']){
                $obj->qtde_minutos_final_tarde = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um horario para saida');
            } else {
                if($param['qtde_horas_tarde'] == $param['qtde_horas_final_tarde']){
                    if($param['qtde_minutos_final_tarde'] < $param['qtde_minutos_tarde']){
                        $obj->qtde_minutos_final_tarde = '';
                        TButton::disableField('form_Ponto', 'salvar');
                        new TMessage('error', 'Horario de saida não pode ser menor que o horario de entrada');
                    }
                }        
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE);
        } else {
            if(is_numeric($param['qtde_horas_final_tarde'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }

    public static function onChangeMinutoEntradaSegundoTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_minutos_tarde'])){
            $string = new StringsUtil;
            $obj = new StdClass;
            if(!$param['qtde_horas_tarde']){
                $obj->qtde_minutos_tarde = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um horario para entrada');
            } else {
                if($param['qtde_horas_final']){
                    $saidaAlmoco = str_pad($param['qtde_horas_final'], 2, 0, STR_PAD_LEFT).':'.str_pad($param['qtde_minutos_final'], 2, 0, STR_PAD_LEFT).':00';
                    $voltaAlmoco = str_pad($param['qtde_horas_tarde'], 2, 0, STR_PAD_LEFT).':'.str_pad($param['qtde_minutos_tarde'], 2, 0, STR_PAD_LEFT).':00';
                    $saida = $string->time_to_sec($saidaAlmoco);                
                    $volta = $string->time_to_sec($voltaAlmoco);
                    $diferenca = $volta - $saida;
                    if($diferenca < 3600){
                        $obj->qtde_minutos_tarde = '';
                        TButton::disableField('form_Ponto', 'salvar');
                        new TMessage('error', 'A diferença entre turnos deve ser de pelo menos uma hora');
                    }
                }
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE); 
        } else {
            if(is_numeric($param['qtde_horas_tarde'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }

    public static function onChangeHoraEntradaSegundoTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_horas_tarde'])){
            $obj = new StdClass;
            if(!$param['data_ponto']){
                $obj->qtde_horas_tarde = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione uma data');
            } else {
                if($param['qtde_horas']){
                    if(!$param['qtde_horas_final'] && !$param['qtde_minutos_final']){
                        $obj->qtde_horas_tarde = '';
                        TButton::disableField('form_Ponto', 'salvar');
                        new TMessage('error', 'Encerre o turno da manha');
                    } else {
                        if($param['qtde_horas_tarde'] <= $param['qtde_horas_final']){
                            $obj->qtde_horas_tarde = '';
                            TButton::disableField('form_Ponto', 'salvar');
                            new TMessage('error', 'A diferença entre turnos deve ser de pelo menos uma hora');
                        }
                    }
                }
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE); 
        } else {
            if(is_numeric($param['qtde_minutos_tarde'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }

    public static function onChangeHoraSaidaPrimeiroTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_horas_final'])){
            $obj = new StdClass;
            if(!$param['qtde_horas']){
                $obj->qtde_horas_final = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um ponto com entrada de 1° turno em aberto');
            } else {
                if($param['qtde_horas'] > $param['qtde_horas_final']){
                    $obj->qtde_horas_final = '';
                    TButton::disableField('form_Ponto', 'salvar');
                    new TMessage('error', 'Horario de saida não pode ser menor que o horario de entrada');
                }        
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE); 
        }  else {
            if(is_numeric($param['qtde_minutos_final'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }
    
    public static function onChangeMinutoSaidaPrimeiroTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_minutos_final'])){
            $obj = new StdClass;
            if(!$param['qtde_horas_final']){
                $obj->qtde_minutos_final = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um horario para saida');
            } else {
                if($param['qtde_horas'] == $param['qtde_horas_final']){
                    if($param['qtde_minutos_final'] < $param['qtde_minutos']){
                        $obj->qtde_minutos_final = '';
                        TButton::disableField('form_Ponto', 'salvar');
                        new TMessage('error', 'Horario de saida não pode ser menor que o horario de entrada');
                    }
                }        
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE);
        } else {
            if(is_numeric($param['qtde_horas_final'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }
        
    public static function onChangeHoraEntradaPrimeiroTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_horas'])){
            $obj = new StdClass;
            if(!$param['data_ponto']){
                $obj->qtde_horas = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione uma data');
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE);
        } else {
            if(is_numeric($param['qtde_minutos'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }
    }
    
    public static function onChangeMinutoEntradaPrimeiroTurno($param)
    {
        TButton::enableField('form_Ponto', 'salvar');
        if(is_numeric($param['qtde_minutos'])){
            $obj = new StdClass;
            if(!$param['qtde_horas']){
                $obj->qtde_minutos = '';
                TButton::disableField('form_Ponto', 'salvar');
                new TMessage('error', 'Selecione um horario para entrada');
            }     
            TForm::sendData('form_Ponto', $obj, FALSE, FALSE);
        } else {
            if(is_numeric($param['qtde_horas'])){
                TButton::disableField('form_Ponto', 'salvar');
            }
        }    
    }
    
    public static function onChangeDataAction($param)
    {
        $string = new StringsUtil;
        
        $data1 = date('Y-m-d');
        $data2 = $string->formatDate($param['data_ponto']);
        
        if(strtotime($data2) > strtotime($data1))
        {
	         new TMessage('error', 'Data do ponto maior que a data atual!');
	         TButton::disableField('form_Ponto', 'salvar');
        }    
        else
        {
        
            $dataLimite = date('Y-m-d', strtotime("-5 days"));
            
            if(strtotime($dataLimite) > strtotime($data2))
            {
                 new TMessage('error', 'Data do ponto menor que data limite permitida ['.$string->formatDateBR($dataLimite).']' );
	             TButton::disableField('form_Ponto', 'salvar');
            }
            else
            {       
                try
                    {
                        TTransaction::open('atividade');
                        $ultimoPonto = Ponto::retornaUltimoPonto($param['colaborador_id']);
                        $ponto = new Ponto($ultimoPonto);
                        if(strtotime($ponto->data_ponto) >= strtotime($data2))
                        {
                             new TMessage('error', 'Existe data posterior ou igual cadastrada!');
	                         TButton::disableField('form_Ponto', 'salvar');
                        }
                        else
                        {
                            
                            //verificar se a data ta fechada
                            if($ponto->hora_saida || $ponto->hora_saida_tarde)
                            {
                                TButton::enableField('form_Ponto', 'salvar');
                            }
                            else
                            {
                                new TMessage('error', 'Ponto anterior não encerrado!');
	                            TButton::disableField('form_Ponto', 'salvar');
                            }
                        }
                        TTransaction::close();
                    }
                catch(Exception $e)
                {
                    new TMessage('error', '<b>Error</b> ' . $e->getMessage());
                }
            }
        }   
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

            $logado = Pessoa::retornaUsuario();            
                       
            // creates a repository for Ponto
            $repository = new TRepository('Ponto');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            $criteria->add(new TFilter('colaborador_id', '=', $logado->pessoa_codigo));
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'data_ponto';
                $param['direction'] = 'desc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            if (TSession::getValue('Ponto_filter'))
            {
                // add the filter stored in the session to the criteria
                $criteria->add(TSession::getValue('Ponto_filter'));
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
                    $object->data_ponto ? $object->data_ponto = $this->string->formatDateBR($object->data_ponto) : null;
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
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
            $key=$param['id'];
        
            TTransaction::open('atividade'); // open the transaction
            $object = new Ponto($key, FALSE); // instantiates the Active Record
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
            
            // get the form data into an active record Ponto
            $object = $this->form->getData('Ponto');
          
            $object->data_ponto ? $object->data_ponto = $this->string->formatDate($object->data_ponto) : null;
            
            $hora_final = null;
            
            if($object->qtde_horas)
            {
                $object->hora_entrada = $object->qtde_horas.':'.$object->qtde_minutos.':00';
            } else {
                $object->hora_entrada = '';
            }          
                     
            if($object->qtde_horas_final){
                $object->hora_saida   = str_pad($object->qtde_horas_final, 2, 0, STR_PAD_LEFT).':'.str_pad($object->qtde_minutos_final, 2, 0, STR_PAD_LEFT).':00';
                $hora_final = $object->hora_saida;
            } else {
                $object->hora_saida = '';
            }
            
            if($object->qtde_horas_tarde) {
                $object->hora_entrada_tarde = $object->qtde_horas_tarde.':'.$object->qtde_minutos_tarde.':00';
            } else {
                $object->hora_entrada_tarde = '';
            }          
                     
            if($object->qtde_horas_final_tarde) {
                $object->hora_saida_tarde   = str_pad($object->qtde_horas_final_tarde, 2, 0, STR_PAD_LEFT).':'.str_pad($object->qtde_minutos_final_tarde, 2, 0, STR_PAD_LEFT).':00';
                $hora_final = $object->hora_saida_tarde;
            } else {
                $object->hora_saida_tarde = '';
            }
            
            if(!$object->qtde_horas_final && $object->qtde_minutos_final) {
                throw new Exception("Horário final inválido");   
            }
            
            $validador = new THoraSaidaPontoValidator;
            
            $parameters = array('user' => $object->colaborador_id, 'data' => $object->data_ponto);
            
            $validador->validate('Hora Final', $hora_final, $parameters);
                        
            $this->form->validate(); // form validation
            $object->store(); // stores the object
            
            $object->data_ponto ? $object->data_ponto = $this->string->formatDateBR($object->data_ponto) : null;
             
            $this->form->setData($object); // fill the form with the active record data
            TTransaction::close(); // close the transaction
            
            $action = new TAction(array($this, 'onReload'));
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), $action); // success message
            $this->onReload(); // reload the listing
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
            TTransaction::open('atividade'); // open a transaction with the database
            if (isset($param['key']))
            {
                
                $key=$param['key']; // get the parameter $key

                $object = new Ponto($key); // instantiates the Active Record
             
                $ultimoPonto = Ponto::retornaUltimoPonto($object->colaborador_id);
 
                $intervalo = Ponto::horaPreenchidas($object->data_ponto, $object->colaborador_id);
                
                if($ultimoPonto == $key)
                {
                    if(!$intervalo)
                    {
                        TButton::enableField('form_Ponto', 'salvar');
                        TButton::enableField('form_Ponto', 'excluir');   
                    }
                    else
                    {
                        TButton::enableField('form_Ponto', 'salvar');
                        TCombo::disableField('form_Ponto', 'qtde_horas');
                        TCombo::disableField('form_Ponto', 'qtde_minutos');
                        
                        if($object->hora_entrada_tarde){
                            $ultima = Atividade::retornaHoraUltimaAtividade($object->colaborador_id, $object->data_ponto);
                            $horaAtividade       = new DateTime($ultima);
                            $horaEntradaTarde    = new DateTime($object->hora_entrada_tarde);
                            
                            if($horaAtividade > $horaEntradaTarde){
                                TCombo::disableField('form_Ponto', 'qtde_horas');
                                TCombo::disableField('form_Ponto', 'qtde_minutos');
                                TCombo::disableField('form_Ponto', 'qtde_horas_final');
                                TCombo::disableField('form_Ponto', 'qtde_minutos_final');
                                TCombo::disableField('form_Ponto', 'qtde_horas_tarde');
                                TCombo::disableField('form_Ponto', 'qtde_minutos_tarde');
                            }
                        }
                                                
                    }
                }
                else
                {
                    TCombo::disableField('form_Ponto', 'qtde_horas');
                    TCombo::disableField('form_Ponto', 'qtde_minutos');
                    TCombo::disableField('form_Ponto', 'qtde_horas_final');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_final');
                    
                    TCombo::disableField('form_Ponto', 'qtde_horas_tarde');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_tarde');
                    TCombo::disableField('form_Ponto', 'qtde_horas_final_tarde');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_final_tarde');
                    
                }
                
                $object->data_ponto ? $object->data_ponto = $this->string->formatDateBR($object->data_ponto) : null;
                
                $horario                            = explode(':', $object->hora_entrada);
                $horario_final                      = explode(':', $object->hora_saida);
                $horario_tarde                      = explode(':', $object->hora_entrada_tarde);
                $horario_final_tarde                = explode(':', $object->hora_saida_tarde);
                $object->qtde_horas                 = $horario[0];
                $object->qtde_minutos               = $horario[1];
                $object->qtde_horas_final           = $horario_final[0];
                $object->qtde_minutos_final         = $horario_final[1];
                $object->qtde_horas_tarde           = $horario_tarde[0];
                $object->qtde_minutos_tarde         = $horario_tarde[1];
                $object->qtde_horas_final_tarde     = $horario_final_tarde[0];
                $object->qtde_minutos_final_tarde   = $horario_final_tarde[1];
                
                if($object->qtde_horas_tarde and $object->qtde_minutos_tarde) {
                    TCombo::disableField('form_Ponto', 'qtde_horas');
                    TCombo::disableField('form_Ponto', 'qtde_minutos');
                    TCombo::disableField('form_Ponto', 'qtde_horas_final');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_final');
                }
                
                if($object->hora_saida_tarde){
                    TCombo::disableField('form_Ponto', 'qtde_horas');
                    TCombo::disableField('form_Ponto', 'qtde_minutos');
                    TCombo::disableField('form_Ponto', 'qtde_horas_final');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_final');
                    TCombo::disableField('form_Ponto', 'qtde_horas_tarde');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_tarde');
                }
                
                if($object->hora_saida){
                    TCombo::disableField('form_Ponto', 'qtde_horas');
                    TCombo::disableField('form_Ponto', 'qtde_minutos');
                }
                
                if(!$object->hora_entrada_tarde){
                    TCombo::disableField('form_Ponto', 'qtde_horas_final_tarde');
                    TCombo::disableField('form_Ponto', 'qtde_minutos_final_tarde');
                }
                
                $this->form->setData($object); // fill the form with the active record data

            }
            else
            {
                $object = new Ponto();
                $object->colaborador_id   = $param['colaborador_id'];
                $object->colaborador_nome = $param['colaborador_nome'];
                $this->form->setData($object);
            }
            TTransaction::close(); // close the transaction
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
    
    public function retornaIntervalo($campo, $object, $row)
    {
         $intervalo = Ponto::horaPreenchidas($object->data_ponto, $object->colaborador_id);
         return substr($intervalo, 0, -3);
    }
    
    public function tiraSegundos($campo, $object, $row)
    {
        return substr($campo,0,-3);         
    }
    
    public function calculaDiferenca($campo, $object, $row)
    {
        if($object->hora_saida and $object->hora_saida_tarde){
            $HoraEntrada         = new DateTime($object->hora_entrada);
            $HoraSaida           = new DateTime($object->hora_saida);
                    
            $campo = $HoraSaida->diff($HoraEntrada)->format('%H:%I:%S');
            $totalPrimeiroTurno  = $this->string->time_to_sec($campo);
            
            $HoraEntradaTarde    = new DateTime($object->hora_entrada_tarde);
            $HoraSaidaTarde      = new DateTime($object->hora_saida_tarde);
                    
            $campo = $HoraSaidaTarde->diff($HoraEntradaTarde)->format('%H:%I:%S');
            $totalSegundoTurno  = $this->string->time_to_sec($campo);
            
            $total = $totalSegundoTurno + $totalPrimeiroTurno;
            $campo = $this->string->sec_to_time($total); 
            
            return substr($campo,0,-3);         
        }
        
        if($object->hora_saida and !$object->hora_saida_tarde) {   
            $HoraEntrada         = new DateTime($object->hora_entrada);
            $HoraSaida           = new DateTime($object->hora_saida);
                    
            $campo = $HoraSaida->diff($HoraEntrada)->format('%H:%I');
                                    
            return $campo;
        }
        
        if(!$object->hora_saida and $object->hora_saida_tarde) {   
            $HoraEntrada         = new DateTime($object->hora_entrada_tarde);
            $HoraSaida           = new DateTime($object->hora_saida_tarde);
                    
            $campo = $HoraSaida->diff($HoraEntrada)->format('%H:%I');
                                    
            return $campo;
        }
        
    }
    
    public function calculaPercentualProdutividade($campo, $object, $row)
    {       
        $intervalo = $this->retornaIntervalo($campo, $object, $row);
        $ponto = new CalculoHorario;
        $horaPonto = $ponto->retornoCargaHorariaDiaria($object);
        
        if($horaPonto){        
            $campo = round($this->string->time_to_sec($intervalo) * 100 / $this->string->time_to_sec($horaPonto) );
            if($campo > 59){
                return "<span style='color:#007BFF'><b>".$campo."%</b></span>";
            } else {
                return "<span style='color:#FFB300'><b>".$campo."%</b></span>";
            } 
        }   
    }
    
}
