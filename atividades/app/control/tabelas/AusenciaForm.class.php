<?php
/**
 * AusenciaForm Registration
 * @author  <your name here>
 */
class AusenciaForm extends TPage
{
    protected $form; // form
    private $string;
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        $this->string = new StringsUtil;
        
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
        $row->addCell( new TLabel('Registro de ausências') )->colspan = 2;
        

        // create the form fields
        $id                             = new THidden('id');
        $criteria = new TCriteria;
        $criteria->add(new TFilter("origem", "=", 1));
        $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
        $criteria->add(new TFilter("ativo", "=", 1));
        $criteria->add(new TFilter("usuario", "is not "));
        $colaborador_id                 = new TDBCombo('colaborador_id', 'atividade', 'Pessoa', 'pessoa_codigo', 'pessoa_nome', 'pessoa_nome', $criteria);
        $criteria = new TCriteria;
        $criteria->add(new TFilter("id", "in", array(10,17)));
        $tipo_atividade_id              = new TDBCombo('tipo_atividade_id', 'atividade', 'TipoAtividade', 'id', 'nome', 'nome', $criteria);
        $tipo_atividade_id->setDefaultOption(FALSE);
        $data_inicial                   = new TDate('data_inicial');
        $data_inicial->setMask('dd/mm/yyyy');   
        $data_final                     = new TDate('data_final');
        $data_final->setMask('dd/mm/yyyy');   

        // define the sizes
        $id->setSize(100);
        $data_inicial->setSize(100);
        $data_final->setSize(100);
        $colaborador_id->setSize(290);
        $tipo_atividade_id->setSize(290);
        
        // validações
        $data_inicial->addValidation('Data inicial', new TRequiredValidator);
        $data_final->addValidation('Data final', new TRequiredValidator);
        $colaborador_id->addValidation('Colaborador', new TRequiredValidator);
        $tipo_atividade_id->addValidation('Tipo atividade', new TRequiredValidator); 

        // add one row for each form field
        $table->addRowSet($label1 = new TLabel('Colaborador:'), $colaborador_id );
        $label1->setFontColor('#FF0000');  
        $table->addRowSet($label2 = new TLabel('Tipo Atividade:'), $tipo_atividade_id );
        $label2->setFontColor('#FF0000');  
        $table->addRowSet($label3 = new TLabel('Data inicial:'), array($data_inicial, $label4 = new TLabel('final:'), $data_final) );
        $label3->setFontColor('#FF0000');  
        $label4->setFontColor('#FF0000');  
        $table->addRowSet( new TLabel(''), $id );    

        // add some row for the form info
        $row = $table->addRow();
        $row->addCell( new TLabel('Este sistema deve ser utilizado para registrar ausências de dias completos') )->colspan = 2;
        $row = $table->addRow();
        $row->addCell( new TLabel('No periodo selecionado não pode haver ponto cadastrado') )->colspan = 2;
        $row = $table->addRow();
        $row->addCell( $label_red = new TLabel('Cuidado para não cadastrar finais de semana e feriados (periodo útil)') )->colspan = 2;
        $label_red->setFontColor('#FF0000');
        
        $this->form->setFields(array($id,$data_inicial,$data_final,$colaborador_id,$tipo_atividade_id));
        
        $change_data = new TAction(array($this, 'onChangeData'));
        $data_inicial->setExitAction($change_data);
        $data_final->setExitAction($change_data);

        // create the form actions
        $save_button = TButton::create('save', array($this, 'onSave'), _t('Save'), 'ico_save.png');
        $new_button  = TButton::create('new',  array($this, 'onEdit'), _t('New'),  'ico_new.png');
        
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        
        $buttons_box = new THBox;
        $buttons_box->add($save_button);
        $buttons_box->add($new_button);
        
        // add a row for the form action
        $row = $table->addRow();
        $row->class = 'tformaction'; // CSS class
        $row->addCell($buttons_box)->colspan = 2;
        
        parent::add($this->form);
    }

    public static function onChangeData($param)
    {
      
        $obj = new StdClass;
        $string = new StringsUtil;
        
        if(strlen($param['data_inicial']) == 10 && strlen($param['data_final']) == 10)
        {
        
            if(strtotime($string->formatDate($param['data_final'])) < strtotime($string->formatDate($param['data_inicial'])))
            {
    	        $obj->data_final = ''; 
    	        new TMessage('error', 'Data de cadastro final menor que data inicial'); 
            }
            else
            {
             	// Start date
            	$date = $string->formatDate($param['data_inicial']);
            	// End date
            	$end_date = $string->formatDate($param['data_final']);
             
            	while (strtotime($date) <= strtotime($end_date)) {
                    
                    if(date("w", strtotime($date)) == 0 or date("w", strtotime($date)) == 6)
                    {
                        $obj->data_final = ''; 
    	                new TMessage('error', 'Existe final de semana no periodo informado'); 
    	                break;
                    }                    
                    $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
            	}   
            }
        }
        
        TForm::sendData('form_Atividade', $obj, FALSE, FALSE);
       
    }

    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    function onSave()
    {
        try
        {
            TTransaction::open('atividade'); // open a transaction
            
            // get the form data into an active record Atividade
            $object = $this->form->getData();
            $this->form->validate(); // form validation
            
            $tipo = new TipoAtividade($object->tipo_atividade_id);
            
        	// Start date
        	$date = $this->string->formatDate($object->data_inicial);
        	// End date
        	$end_date = $this->string->formatDate($object->data_final);
         
        	while (strtotime($date) <= strtotime($end_date)) {
                
                $criteria = new TCriteria;
                $criteria->add(new TFilter("data_ponto", "=", $date));
                $criteria->add(new TFilter("colaborador_id", "=", $object->colaborador_id));
                $repo = new TRepository('Ponto');
                $count = $repo->count($criteria);
                
                if(!$count)
                {
                    $criteria = new TCriteria;
                    $criteria->add(new TFilter("data_atividade", "=", $date));
                    $criteria->add(new TFilter("colaborador_id", "=", $object->colaborador_id));
                    $repo = new TRepository('Atividade');
                    $count = $repo->count($criteria);
                    
                    if(!$count)
                    {
                        
                        $ponto = new Ponto;
                        $ponto->data_ponto = $date;
                        $ponto->hora_entrada = '08:15:00';
                        $ponto->hora_saida   = '18:03:00';      
                        $ponto->colaborador_id = $object->colaborador_id;   
                        $ponto->store();
                        
                        $atividade = new Atividade;
                        $atividade->data_atividade = $date;
                        $atividade->hora_inicio = '09:15:00';
                        $atividade->hora_fim    = '18:03:00';   
                        $atividade->descricao   = 'AUSENCIA CADASTRADA EM LOTE';         
                        $atividade->colaborador_id = $object->colaborador_id;
                        $atividade->tipo_atividade_id = $object->tipo_atividade_id;
                        $atividade->sistema_id = $tipo->sistema_id;
                        $atividade->ticket_id  = $tipo->ticket_id;
                        $atividade->store();
                        
                    }
                }
                else
                {
                    new TMessage('error', '<b>Erro:</b> Ponto já cadastrado dia: '.$this->string->formatDateBR($date) );
                    break;
                }
                
                $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        	}
            
            $this->form->setData($object); // keep form data
            TTransaction::close(); // close the transaction
            
            // shows the success message
            
            if(!$count)
            {
                new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
            }
            
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
        try
        {
            if (isset($param['key']))
            {
                $key=$param['key'];  // get the parameter $key
                TTransaction::open('atividade'); // open a transaction
                $object = new Atividade($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
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
}
