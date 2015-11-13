<?php
/**
 * AtividadeForm Registration
 * @author  <your name here>
 */
class AtividadeForm extends TPage
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        $string = new StringsUtil;
        
        // creates the form
        $this->form = new TForm('form_Atividade');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'width: 500px';
        
        // add a table inside form
        $table = new TTable;
        $table-> width = '100%';
        $this->form->add($table);
        
        // add a row for the form title
        $row = $table->addRow();
        $row->class = 'tformtitle'; // CSS class
        $row->addCell( new TLabel('Atividade') )->colspan = 2;
                
        // busca dados do banco        
        try
        {
            TTransaction::open('atividade');
            $logado = Pessoa::retornaUsuario();
            
            $ultimoPonto = Ponto::retornaUltimoPonto($logado->pessoa_codigo);
            $ponto = new Ponto($ultimoPonto);
            
            if($ponto->hora_saida)
            {
                $action = new TAction(array('PontoFormList', 'onReload'));
                new TMessage('error', 'Não existe ponto com horario em aberto!', $action);
            }
            
            $data_padrao = $string->formatDateBR($ponto->data_ponto);
            $hora_padrao = Ponto::retornaHoraInicio($string->formatDate($data_padrao), $logado->pessoa_codigo);   
            TTransaction::close();
        }
        catch(Exception $e)
        {
             new TMessage('error', '<b>Error</b> ' . $e->getMessage());
        }
        
        // create the form fields
        $id                             = new THidden('id');
        $data_atividade                 = new TEntry('data_atividade');
        $data_atividade->setMask('dd/mm/yyyy');
        $data_atividade->setValue($data_padrao);
        $data_atividade->setEditable(FALSE);
        
        $hora_inicio                    = new TEntry('hora_inicio');
        $hora_inicio->setEditable(FALSE);
        
        $hora_inicio->setValue($hora_padrao);
        
        $hora_fim                       = new THidden('hora_fim');
        $hora_fim->setEditable(FALSE);
        $tempo_atividade                = new TEntry('tempo_atividade');
        $tempo_atividade->setEditable(FALSE);
        $qtde_horas                     = new TCombo('qtde_horas');
        $qtde_minutos                   = new TCombo('qtde_minutos');
        $descricao                      = new TText('descricao');
        $colaborador_id                 = new THidden('colaborador_id');
        $colaborador_id->setValue($logado->pessoa_codigo);
        $colaborador_nome               = new TEntry('colaborador_nome');
        $colaborador_nome->setEditable(FALSE);
        $colaborador_nome->setValue($logado->pessoa_nome);
        $tipo_atividade_id              = new TDBCombo('tipo_atividade_id', 'atividade', 'TipoAtividade', 'id', 'nome', 'nome');
        
        $sistema_id                     = new TDBCombo('sistema_id', 'atividade', 'Sistema', 'id', 'nome');
        
        $ticket_id                      = new TCombo('ticket_id');
        $criteria = new TCriteria;
        $criteria->add(new TFilter("status_ticket_id", "IN", array(1, 5)));
        $newparam['order'] = 'id';
        $newparam['direction'] = 'asc';
        $criteria->setProperties($newparam); // order, offset
        $this->onComboTicket($criteria);
        
        $horario = explode(':', $hora_padrao);

        // cria combos de horas e minutos
        $combo_horas = array();
        for($i = 8; $i <= 18; $i++)
        {
             $combo_horas[$i] = str_pad($i, 2, 0, STR_PAD_LEFT) ;
        }
        $qtde_horas->addItems($combo_horas);
        $qtde_horas->setValue($horario[0]);
        $qtde_horas->setDefaultOption(FALSE);
        
        $combo_minutos = array();
        for($i = 0; $i <= 59; $i++)
        {
             $combo_minutos[$i] = str_pad($i, 2, 0, STR_PAD_LEFT) ;
        }
        $qtde_minutos->addItems($combo_minutos);
        $qtde_minutos->setValue($horario[1]);
        $qtde_minutos->setDefaultOption(FALSE);
                
        // set exit action for input_exit
        $change_action = new TAction(array($this, 'onChangeAction'));
        $qtde_horas->setChangeAction($change_action);
        $qtde_minutos->setChangeAction($change_action);
        
        $change_atividade_action = new TAction(array($this, 'onTrocaTipoAtividade'));
        $tipo_atividade_id->setChangeAction($change_atividade_action);
        
        $change_ticket_action = new TAction(array($this, 'onTrocaTicket'));
        $ticket_id->setChangeAction($change_ticket_action);
        

        
        // define the sizes
        $id->setSize(100);
        $data_atividade->setSize(100);
        $hora_inicio->setSize(100);
        $hora_fim->setSize(100);
        $qtde_horas->setSize(60);
        $qtde_minutos->setSize(60);
        $tempo_atividade->setSize(100);
        $descricao->setSize(300, 80);
        $colaborador_id->setSize(200);
        $tipo_atividade_id->setSize(200);
        $ticket_id->setSize(300);
        
        // validações
        $tempo_atividade->addValidation('Hora Fim', new THoraFimValidator);
        $tipo_atividade_id->addValidation('Tipo de Atividade', new TRequiredValidator);
        $ticket_id->addValidation('Ticket', new TRequiredValidator);
        $sistema_id->addValidation('Sistema', new TRequiredValidator);
        $descricao->addValidation('Descrição', new TMinLengthValidator, array(10));
        
        $sem_atividade = TButton::create('atividade', array($this, 'onSemAtividade'), 'Sem Registro', 'ico_add.png');
        $this->form->addField($sem_atividade);
                
        // add one row for each form field
        $table->addRowSet( new TLabel('Colaborador:'), $colaborador_nome );
        $table->addRowSet( new TLabel('Data Atividade:'), array($data_atividade, $label_data = new TLabel('Data do último ponto') ) );
        $label_data->setFontColor('#A9A9A9');
        $table->addRowSet( new TLabel('Hora Inicio:'), $hora_inicio );
        $table->addRowSet( $label_qtde_horas = new TLabel('Hora Fim:'), array($qtde_horas, $qtde_minutos, $sem_atividade) );
        $label_qtde_horas->setFontColor('#FF0000');
        $table->addRowSet( new TLabel('Tempo Atividade:'), $tempo_atividade ); 
        $table->addRowSet( $label_atividade = new TLabel('Tipo Atividade:'), $tipo_atividade_id );
        $label_atividade->setFontColor('#FF0000');
        $table->addRowSet( $label_ticket = new TLabel('Ticket:'), $ticket_id );
        $label_ticket->setFontColor('#FF0000');
        $table->addRowSet( $label_sistema = new TLabel('Sistema:'), $sistema_id );
        $label_sistema->setFontColor('#FF0000');
        $table->addRowSet( $label_descricao = new TLabel('Descrição:'), $descricao ); 
        $label_descricao->setFontColor('#FF0000');  
        $table->addRowSet( new TLabel(''), $id );
        $table->addRowSet( new TLabel(''), $colaborador_id );
        $table->addRowSet( new TLabel(''), $hora_fim );   //esconder

        $this->form->setFields(array($id,$data_atividade,$hora_inicio,$qtde_horas,$qtde_minutos,$hora_fim,$tempo_atividade,$descricao,$colaborador_id,$colaborador_nome,$tipo_atividade_id,$ticket_id,$sistema_id));




        // create the form actions
        $save_button = TButton::create('save', array($this, 'onSave'), _t('Save'), 'ico_save.png');
        $new_button  = TButton::create('new',  array($this, 'onEdit'), _t('New'),  'ico_new.png');
        $del_button  = TButton::create('delete',  array($this, 'onDelete'), _t('Delete'),  'ico_delete.png');
        $list_button = TButton::create('list', array('AtividadeList', 'onClean'), _t('List'), 'ico_datagrid.png');
        
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        $this->form->addField($del_button);
        $this->form->addField($list_button);
        
        $buttons_box = new THBox;
        $buttons_box->add($save_button);
        $buttons_box->add($new_button);
        $buttons_box->add($del_button);
        $buttons_box->add($list_button);
        
        // add a row for the form action
        $row = $table->addRow();
        $row->class = 'tformaction'; // CSS class
        $row->addCell($buttons_box)->colspan = 2;






//                        TScript::create(' $( "#descricao" ).focus(); ');
        
        parent::add($this->form);
    }
    
    public static function onSetarValoresCombo($param)
    {
        $obj = new StdClass;
        $obj->ticket_id         = $param;  
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE);
    }
    
    public static function onComboTicket($criteria)
    {
        
        try
        {
            TTransaction::open('atividade');
            $repo = new TRepository('Ticket');
            $tickets = $repo->load($criteria);
            $options[null] = null;
            foreach($tickets as $ticket)
            {
                $options [ $ticket->id ] = $ticket->id.' - '.$ticket->titulo;
            }
            TTransaction::close();
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
        
        TCombo::reload('form_Atividade', 'ticket_id', $options);
    }
    
    public static function onTrocaTipoAtividade($param)
    {
        $obj = new StdClass;
        $obj->ticket_id = '';
        $obj->sistema_id = '';
        if($param['tipo_atividade_id'])
        {   
            try
            {
                TTransaction::open('atividade');
                $atividade = new TipoAtividade($param['tipo_atividade_id']);
                $obj->ticket_id  = $atividade->ticket_id;
                $obj->sistema_id = $atividade->sistema_id;
                TTransaction::close();
            }
            catch(Exception $e)
            {
                new TMessage('error', $e->getMessage());
            }
        }
        
        if($atividade->ticket_id && $atividade->sistema_id){
            TScript::create(' form_Atividade.descricao.focus(); ');
        }
        
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE); 

        
    }
    
    public static function onTrocaTicket($param)
    {
        $obj = new StdClass;
        $obj->sistema_id = '';
        if($param['ticket_id'])
        {   
            try
            {
                TTransaction::open('atividade');
                
                $ticket = new Ticket($param['ticket_id']);
                $obj->sistema_id = $ticket->sistema_id;
                
                TTransaction::close();
            }
            catch(Exception $e)
            {
                new TMessage('error', $e->getMessage());
            }
        }
        
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE); 
        
    }
    
    public static function onSemAtividade($param)
    {
        $obj = new StdClass;
        
        $hora         = $param['qtde_horas'];
        $minutos      = str_pad($param['qtde_minutos'], 2, 0, STR_PAD_LEFT);
        $hora_fim     = $hora.':'.$minutos.':00';
        
        $obj->hora_inicio      = $hora_fim;
        $obj->hora_fim         = $hora_fim;
        $obj->tempo_atividade  = '';
        
        
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE);
        
    }

    public static function onChangeAction($param)
    {
        $obj = new StdClass;
        
        $hora         = $param['qtde_horas'];
        $minutos      = str_pad($param['qtde_minutos'], 2, 0, STR_PAD_LEFT);
        
        $hora_fim     = $hora.':'.$minutos.':00';
      
        $HoraEntrada = new DateTime($param['hora_inicio']);
        $HoraSaida   = new DateTime($hora_fim);

        $diffHoras = $HoraSaida->diff($HoraEntrada)->format('%H:%I:%S');
        
        TButton::enableField('form_Atividade', 'save');
        
        if($HoraEntrada > $HoraSaida)
        {
            new TMessage('error', 'Hora final menor que a Hora inicial!');
            TButton::disableField('form_Atividade', 'save');
            
            $horario = explode(':', $param['hora_inicio']);
           
            $obj->qtde_horas = $horario[0];
            $obj->qtde_minutos = $horario[1];
            
        }
        else
        {
             $obj->hora_fim         = $hora_fim;
             $obj->tempo_atividade  = $diffHoras;
        }
        
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE);
        
    }


    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    function onSave()
    {
        $string = new StringsUtil;
        try
        {
            TTransaction::open('atividade'); // open a transaction
            
            // get the form data into an active record Atividade
            $object = $this->form->getData('Atividade');

            $object->data_atividade ? $object->data_atividade = $string->formatDate($object->data_atividade) : null;
            
            $this->onSetarValoresCombo($object->ticket_id);
                                        
            $this->form->validate(); // form validation
            $object->store(); // stores the object

            $object->ticket_id = $arraySwap;  

            $object->data_atividade ? $object->data_atividade = $string->formatDateBR($object->data_atividade) : null;
            
            $this->form->setData($object); // keep form data
            TTransaction::close(); // close the transaction
            
            // shows the success message
            $action = new TAction(array($this, 'onEdit'));
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), $action);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method onEdit()
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        $string = new StringsUtil;
        try
        {
            TTransaction::open('atividade'); // open a transaction
            $logado = Pessoa::retornaUsuario();
            
            if (isset($param['key']))
            {
                $key=$param['key'];  // get the parameter $key
    
                $atividade = new Atividade($key);
                
                $criteria = new TCriteria;
                $newparam['order'] = 'id';
                $newparam['direction'] = 'asc';
                $criteria->setProperties($newparam); // order, offset
                $this->onComboTicket($criteria);
                $this->onSetarValoresCombo($atividade->ticket_id);
                
                // criar metodo de preenchimento de horas
                $HoraEntrada = new DateTime($atividade->hora_inicio);
                $HoraSaida   = new DateTime($atividade->hora_fim);
                $diffHoras = $HoraSaida->diff($HoraEntrada)->format('%H:%I:%S');
                $horas = explode(':', $atividade->hora_fim);
                
                $atividade->qtde_horas      = $horas[0];
                $atividade->qtde_minutos    = $horas[1];
                TCombo::disableField('form_Atividade', 'qtde_horas');
                TCombo::disableField('form_Atividade', 'qtde_minutos');
                $atividade->tempo_atividade = $diffHoras;
                
                $ultimaAtividade = Atividade::retornaUltimaAtividade( $atividade->colaborador_id );
                
                if(!$ultimaAtividade)
                {
                    $action = new TAction(array('AtividadeList', 'onReload'));
                    new TMessage('info', 'Nenhuma atividade cadastrada!', $action); // success message
                }   
                
                if($key <> $ultimaAtividade)
                {
                    TButton::disableField('form_Atividade', 'delete');
                }
                
                $atividade->data_atividade ? $atividade->data_atividade = $string->formatDateBR($atividade->data_atividade) : null;
                
                $colaborador = new Pessoa($atividade->colaborador_id);
                
                $atividade->colaborador_nome = $colaborador->pessoa_nome;
                
                if($logado->pessoa_codigo <> $atividade->colaborador_id)
                {
                    TButton::disableField('form_Atividade', 'save');
                    TButton::disableField('form_Atividade', 'delete');
                }
                
                TButton::disableField('form_Atividade', 'atividade');
                
                $this->form->setData($atividade); // fill the form
            }
            else
            {
                $object = new Atividade();
                $object->colaborador_id   = $logado->pessoa_codigo;
                $object->colaborador_nome = $logado->pessoa_nome;
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
           // print_r($param);
            
            $key=$param['id']; // get the parameter $key
            
            TTransaction::open('atividade'); // open a transaction with database
            $object = new Atividade($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object from the database
            TTransaction::close(); // close the transaction
            
            $action = new TAction(array('AtividadeList', 'onReload'));
            new TMessage('info', TAdiantiCoreTranslator::translate('Record deleted'), $action); // success message
                        
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
}
