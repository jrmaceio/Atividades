<?php
/**
 * RequisitoDesenvolvimentoForm Registration
 * @author  <your name here>
 */
class RequisitoDesenvolvimentoForm extends TPage
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
        
        // creates the form
        $this->form = new TForm('form_RequisitoDesenvolvimento');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'width: 500px';
        
        $this->string = new StringsUtil;
        
        // add a table inside form
        $table = new TTable;
        $table-> width = '100%';
        $this->form->add($table);
        
        // add a row for the form title
        $row = $table->addRow();
        $row->class = 'tformtitle'; // CSS class
        $row->addCell( new TLabel('Cadastro de DTr') )->colspan = 2;
        
        // create the form fields
        $id                             = new THidden('id');
        $titulo                         = new TEntry('titulo');

        $data_cadastro                  = new TEntry('data_cadastro');
        $data_cadastro->setEditable(FALSE);        
        $data_cadastro->setMask('dd/mm/yyyy');
        $data_cadastro->setValue(date('d/m/Y'));
        
        $rotina                         = new TEntry('rotina');
        $objetivo                       = new TText('objetivo');
        $entrada                        = new TText('entrada');
        $processamento                  = new TText('processamento');
        $saida                          = new TText('saida');
        
        $ticket_id                      = new TEntry('ticket_id');
        $ticket_id->setEditable(FALSE);
        $ticket_titulo                  = new TEntry('ticket_titulo');
        $ticket_titulo->setEditable(FALSE);
       
        // define the sizes
        $id->setSize(100);
        $titulo->setSize(300);
        $data_cadastro->setSize(100);
        $rotina->setSize(300);
        $objetivo->setSize(300, 60);
        $entrada->setSize(300, 60);
        $processamento->setSize(300, 60);
        $saida->setSize(300, 60);
        $ticket_id->setSize(45);
        $ticket_titulo->setSize(250);

        // validations
        $titulo->addValidation('Título', new TRequiredValidator);
        $objetivo->addValidation('Objetivo', new TRequiredValidator);
        $ticket_id->addValidation('Ticket', new TRequiredValidator);

        // add one row for each form field
        $table->addRowSet( $label_titulo = new TLabel('Título:'), $titulo );
        $label_titulo->setFontColor('#FF0000');
        $table->addRowSet( $label_ticket_id = new TLabel('Ticket:'), array($ticket_id, $ticket_titulo) );
        $label_ticket_id->setFontColor('#FF0000');
        $table->addRowSet( new TLabel('Data de Cadastro:'), $data_cadastro );
        $table->addRowSet( new TLabel('Rotina:'), $rotina );
        $table->addRowSet( $label_objetivo = new TLabel('Objetivo:'), $objetivo );
        $label_objetivo->setFontColor('#FF0000');
        $table->addRowSet( new TLabel('Entrada:'), $entrada );
        $table->addRowSet( new TLabel('Processamento:'), $processamento );
        $table->addRowSet( new TLabel('Saida:'), $saida );
        $table->addRowSet( new TLabel(''), $id );

        $this->form->setFields(array($id,$titulo,$data_cadastro,$rotina,$objetivo,$entrada,$processamento,$saida,$ticket_id, $ticket_titulo));

        // create the form actions
        $save_button = TButton::create('save', array($this, 'onSave'), _t('Save'), 'fa:floppy-o');
        $list_button   = TButton::create('list', array('RequisitoDesenvolvimentoList', 'onReload'), _t('List'), 'fa:table blue');
        
        $gerar_dtr = TButton::create('gerar_dtr', array($this, 'onGenerate'), 'Gerar DTr', 'fa:floppy-o');
        $gerar_kanban = TButton::create('gerar_kanban', array($this, 'onGenerateKanban'), 'Gerar Kanban', 'fa:floppy-o');

        TButton::disableField('form_RequisitoDesenvolvimento', 'save');
        TButton::disableField('form_RequisitoDesenvolvimento', 'gerar_dtr');
        TButton::disableField('form_RequisitoDesenvolvimento', 'gerar_kanban');
             
        $this->form->addField($save_button);
        $this->form->addField($list_button);
        $this->form->addField($gerar_dtr);
        $this->form->addField($gerar_kanban);
        
        $buttons_box = new THBox;
        $buttons_box->add($save_button);
        $buttons_box->add($list_button);
        $buttons_box->add($gerar_dtr);
        $buttons_box->add($gerar_kanban);
                
        // add a row for the form action
        $row = $table->addRow();
        $row->class = 'tformaction'; // CSS class
        $row->addCell($buttons_box)->colspan = 2;
        
        parent::add($this->form);
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
            
            // get the form data into an active record RequisitoDesenvolvimento
            $object = $this->form->getData('RequisitoDesenvolvimento');
            
            $object->data_cadastro ? $object->data_cadastro = $this->string->formatDate($object->data_cadastro) : null;
            
            $this->form->validate(); // form validation
            $object->store(); // stores the object
            
            $object->data_cadastro ? $object->data_cadastro = $this->string->formatDateBR($object->data_cadastro) : null;
            
            $this->form->setData($object); // keep form data
            TTransaction::close(); // close the transaction
            
            TButton::enableField('form_RequisitoDesenvolvimento', 'save');
            TButton::enableField('form_RequisitoDesenvolvimento', 'gerar_dtr');
            TButton::enableField('form_RequisitoDesenvolvimento', 'gerar_kanban');
            
            // shows the success message
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
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
            TButton::enableField('form_RequisitoDesenvolvimento', 'save');
            if (isset($param['key']))
            {
                
                $key=$param['key'];  // get the parameter $key
                TTransaction::open('atividade'); // open a transaction
                $object = new RequisitoDesenvolvimento($key); // instantiates the Active Record
                TButton::enableField('form_RequisitoDesenvolvimento', 'gerar_dtr');
                TButton::enableField('form_RequisitoDesenvolvimento', 'gerar_kanban');
                
                $object->data_cadastro ? $object->data_cadastro = $this->string->formatDateBR($object->data_cadastro) : null;
                
                $object->ticket_titulo = $object->ticket->titulo;
                
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                                
                $object = new RequisitoDesenvolvimento;
                $object->titulo        = $param['titulo'];
                $object->ticket_id     = $param['id'];
                $object->ticket_titulo = $param['titulo'];
                $object->objetivo      = $param['solicitacao_descricao'];
                $this->form->setData($object);
                
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    function onBuscaDTR($param)
    {
        try
        {  
            TTransaction::open('atividade');
            $ticket = new Ticket($param['key']);            
            $dtrs = $ticket->getRequisitoDesenvolvimentos();
            foreach ($dtrs as $dtr)
            {
                $param['key'] = $dtr->id;
            }
            TTransaction::close();   
            $this->onEdit($param);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage);
        }
    }
    
    public function onGenerate()
    {
        try
        {
            TTransaction::open('atividade');
            $object = $this->form->getData();
            
            $desenvolvimento = new RequisitoDesenvolvimento($object->id);
            
            $cliente_id         = $desenvolvimento->ticket->solicitante_id;
            $responsavel_id     = $desenvolvimento->ticket->responsavel_id;

            $pessoa = new Pessoa($cliente_id);
            $cliente = $pessoa->pessoa_nome;
            
            $pessoa = new Pessoa($responsavel_id);
            $responsavel = $pessoa->pessoa_nome;
                
            if (!class_exists('PHPRtfLite_Autoloader'))
            {
                PHPRtfLite::registerAutoloader();
            }
            $tr = new TTableWriterRTF(array(500));
            
            $tr->addStyle('title', 'Arial', '10', 'BI', '#000000', '#ffffff');
            $tr->addStyle('datap', 'Arial', '10', '',   '#000000', '#ffffff');
            
            $data = $desenvolvimento->data_cadastro;
            $data = explode('-', $data);
            
            $data_prevista = '___/___/___';
            if($desenvolvimento->ticket->data_prevista)
            {
                $data_prevista = $this->string->formatDateBR($desenvolvimento->ticket->data_prevista);
            }
            
            $cabecalho = 'DTR010 - Solicitação de Desenvolvimento
Número: '.$desenvolvimento->ticket_id.'/'.$data[0].' Data: '.$this->string->formatDateBR($desenvolvimento->data_cadastro).' Prazo de entrega: '.$data_prevista.' Qtde de Horas: '.strstr($desenvolvimento->ticket->orcamento_horas, ':', true).' Ticket: '.$desenvolvimento->ticket_id.'
Benefício: ( )+Receita ( )-Despesa ( )+Eficiência ( )-NDA
Título: '.$desenvolvimento->titulo.'
Sistema: '.$desenvolvimento->ticket->sistema->nome.'      Módulo:                                   Rotina: '.$desenvolvimento->rotina.'
Cliente: '.$cliente.' Solicitante/Dpto: '.$responsavel;
            
            $tr->addRow();
            $tr->addCell(utf8_decode($cabecalho), 'left', 'title');
           
            $tr->addRow();
            $tr->addCell('<br /><b>Objetivo:</b> <br />'.$desenvolvimento->objetivo, 'left', 'datap');
            
            $tr->addRow();
            $tr->addCell('<br /><b>Entrada: </b><br />'.$desenvolvimento->entrada, 'left', 'datap');
                         
            $tr->addRow();
            $tr->addCell('<br /><b>Processamento: </b><br />'.$desenvolvimento->processamento, 'left', 'datap');
            
            $tr->addRow();
            $tr->addCell('<br /><b>Saida: </b><br />'.$desenvolvimento->saida, 'left', 'datap');

            $tipo = array(4 => 'D', 5 => 'A', 6 => 'C');
            $nome = 'DTR010'.$tipo[$desenvolvimento->ticket->tipo_ticket_id].$desenvolvimento->ticket_id .'-'.$data[0].' - '.$desenvolvimento->titulo;

            $tr->save("app/output/{$nome}.rtf");
            parent::openFile("app/output/{$nome}.rtf");
            
            TButton::enableField('form_RequisitoDesenvolvimento', 'save');
            $this->form->setData($object);
            
            // define the onEdit action
            $action = new TAction(array($this, 'onEdit'));
            $param['key'] = $object->id;
            $action->setParameters($param); // pass the key parameter ahead
            
            new TMessage('info', 'DTR gerado com sucesso!', $action);
            
            TTransaction::close();
            
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage);
        }
        
    }
    
    public function onGenerateKanban()
    {
        
        try
        {
            TTransaction::open('atividade');
            $object = $this->form->getData();
            
            $desenvolvimento = new RequisitoDesenvolvimento($object->id);
            
            $cliente_id         = $desenvolvimento->ticket->solicitante_id;
            $responsavel_id     = $desenvolvimento->ticket->responsavel_id;
            
            $pessoa = new Pessoa($cliente_id);
            $cliente = $pessoa->pessoa_nome;
            
            $pessoa = new Pessoa($responsavel_id);
            $responsavel = $pessoa->pessoa_nome;
            
            $data = $desenvolvimento->data_cadastro;
            $data = explode('-', $data);
            
            $data_prevista = '___/___/___';
            if($desenvolvimento->ticket->data_prevista)
            {
                $data_prevista = $this->string->formatDateBR($desenvolvimento->ticket->data_prevista);
            }    
            
            $designer = new TPDFDesigner;
            $designer->fromXml('app/reports/kanban.pdf.xml');
            $designer->replace('{ID_DTR}', $desenvolvimento->ticket_id.'/'.$data[0]);
            $designer->replace('{CADASTRO}', $this->string->formatDateBR($desenvolvimento->data_cadastro));
            $designer->replace('{INICIO}', date('d/m/Y'));
            $designer->replace('{PREVISTA}', $data_prevista);
            $designer->replace('{SISTEMA}', utf8_decode($desenvolvimento->ticket->sistema->nome));
            $designer->replace('{TICKET}', $desenvolvimento->ticket_id);
            $designer->replace('{TITULO}', utf8_decode($desenvolvimento->titulo));
            $designer->replace('{SOLICITANTE}', utf8_decode($cliente));
            $designer->replace('{RESPONSAVEL}', utf8_decode($responsavel));

            $designer->generate();
            
            $tipo = array(4 => 'D', 5 => 'A', 6 => 'C');
            $nome = 'DTR011'.$tipo[$desenvolvimento->ticket->tipo_ticket_id].$desenvolvimento->ticket_id .'-'.$data[0].' - '.$desenvolvimento->titulo;

            $file = 'app/output/'.$nome.'.pdf';
            $designer->save($file);
            parent::openFile($file); 
            
            TButton::enableField('form_RequisitoDesenvolvimento', 'save');
            $this->form->setData($object);
            
             // define the onEdit action
            $action = new TAction(array($this, 'onEdit'));
            $param['key'] = $object->id;
            $action->setParameters($param); // pass the key parameter ahead
            
            new TMessage('info', 'Cartão kambam gerado com sucesso!', $action);
            
            TTransaction::close();
            
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage);
        }
    }
}
