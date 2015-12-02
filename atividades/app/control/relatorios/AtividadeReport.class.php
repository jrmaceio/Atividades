<?php
/**
 * AtividadeReport Report
 * @author  <your name here>
 */
class AtividadeReport extends TPage
{
    protected $form; // form
    protected $notebook;
    private $string;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TForm('form_Atividade_report');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'width: 500px';
        $this->string = new StringsUtil;
        
        // creates the table container
        $table = new TTable;
        $table->width = '100%';
        
        // add the table inside the form
        $this->form->add($table);

        // define the form title
        $table->addRowSet( new TLabel('Indicadores por colaborador'), '' )->class = 'tformtitle';
        
        // cria array para popular as combos
        TTransaction::open('atividade');
           
        $criteria = new TCriteria;
        $criteria->add(new TFilter("origem", "=", 1));
        $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
        $criteria->add(new TFilter("ativo", "=", 1));
        $newparam['order'] = 'pessoa_nome';
        $newparam['direction'] = 'asc';
        $criteria->setProperties($newparam); // order, offset
           
        $repo = new TRepository('Pessoa');
        $pessoas = $repo->load($criteria);
           
        $arrayPessoas[-1] = 'TODOS COLABORADORES';
        foreach($pessoas as $pessoa)
        {
            $arrayPessoas[$pessoa->pessoa_codigo] = $pessoa->pessoa_nome;
        }
           
        $criteria = new TCriteria;
        $criteria->add(new TFilter("enttipent","=","1"));
        $newparam['order'] = 'entcodent';
        $newparam['direction'] = 'asc';
        $criteria->setProperties($newparam); // order, offset
           
        $repo = new TRepository('Entidade');
        $clientes = $repo->load($criteria);
           
        $arrayClientes[-1] = 'TODOS CLIENTES';
        foreach($clientes as $cliente)
        {
            $arrayClientes[$cliente->entcodent] = str_pad($cliente->entcodent, 4, '0', STR_PAD_LEFT).' - '.$cliente->entrazsoc;
        }
        $arrayClientes[999] = 'ECS 999';
           
        TTransaction::close();
        // fim dos array das combos
        
        // create the form fields
        $colaborador_id                 = new TCombo('colaborador_id');
        $colaborador_id->setDefaultOption(FALSE);
        $colaborador_id->addItems($arrayPessoas);
        
        $cliente_id                     = new TCombo('cliente_id');
        $cliente_id->setDefaultOption(FALSE);
        $cliente_id->addItems($arrayClientes);
        
        $mes_atividade                  = new TCombo('mes_atividade');
        
        $mes_atividade->addItems($this->string->array_meses());
        $mes_atividade->setDefaultOption(FALSE);
        $mes_atividade->setValue(date('m'));
        
        $ano_atividade                  = new TCombo('ano_atividade');
        $anos = array(
                            2015 => '2015'
                      );         
        $ano_atividade->addItems($anos);
        $ano_atividade->setDefaultOption(FALSE);       
 
        $output_type                    = new TRadioGroup('output_type');

        // define the sizes
        $colaborador_id->setSize(250);
        $cliente_id->setSize(250);
        $mes_atividade->setSize(250);
        $ano_atividade->setSize(100);
        $output_type->setSize(100);

        // add one row for each form field
        $table->addRowSet( new TLabel('Colaborador:'), $colaborador_id );
        $table->addRowSet( new TLabel('Cliente:'), $cliente_id );
        $table->addRowSet( new TLabel('Mês:'), $mes_atividade );
        $table->addRowSet( new TLabel('Ano:'), $ano_atividade );
        $table->addRowSet( new TLabel('Output:'), $output_type );
        
        $this->form->setFields(array($colaborador_id,$cliente_id,$mes_atividade,$ano_atividade,$output_type));
        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('html');
        $output_type->setLayout('horizontal');
        
        $generate_button = TButton::create('generate', array($this, 'onGenerate'), _t('Generate'), 'fa:check-circle-o green');
        $this->form->addField($generate_button);
        
        // add a row for the form action
        $table->addRowSet( $generate_button, '' )->class = 'tformaction';
        
        parent::add($this->form);
    }

    function retornaPonto($user, $mes)
    {
        $ano = date("Y"); // Ano atual
        $ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));
        $totalPonto = null;
        
        for ($dia = 1; $dia <= $ultimo_dia; $dia++)
        {
        
            $data = $ano.'-'.$mes.'-'.$dia;
            $ponto = Ponto::retornaTempoPonto($user, $data);
                        
            $total = new DateTime($ponto);
            $almoco = new DateTime('01:00:00');
            $limite = new DateTime('06:00:00');
            if($total > $limite)
            {
                $ponto = $total->diff($almoco)->format('%H:%I:%S');
            }
            
            $totalPonto += $this->string->time_to_sec($ponto);
        
        }
        
        return $this->string->sec_to_time($totalPonto);
    }

    /**
     * method onGenerate()
     * Executed whenever the user clicks at the generate button
     */
    function onGenerate()
    {
        try
        {
            // open a transaction with database 'atividade'
            TTransaction::open('atividade');
            
            // get the form data into an active record
            $formdata = $this->form->getData();

            $format  = $formdata->output_type;
            
            $tickets = null;
            if($formdata->cliente_id > 0)
            {
                $cliente = Pessoa::getPessoasEntidade($formdata->cliente_id);
                $retorno = Ticket::getTicketsCliente($cliente);
                $tickets = implode(",",$retorno);
            }
            
            $total = Atividade::retornaTotalAtividadesColaborador($formdata->colaborador_id, $formdata->mes_atividade, $formdata->ano_atividade, $tickets);
            
            if ($total)
            {
                
                $widths = array(25,350,70,50);
                
                switch ($format)
                {
                    case 'html':
                        $tr = new TTableWriterHTML($widths);
                        $break = '<br />';
                        break;
                    case 'pdf':
                        $tr = new TTableWriterPDF($widths);
                        $break = '';
                        break;
                    case 'rtf':
                        if (!class_exists('PHPRtfLite_Autoloader'))
                        {
                            PHPRtfLite::registerAutoloader();
                        }
                        $tr = new TTableWriterRTF($widths);
                        $break = '<br />';
                        break;
                }
                
                // create the document styles
                $tr->addStyle('title', 'Arial', '10', 'B',   '#ffffff', '#6B6B6B');
                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#E5E5E5');
                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                $tr->addStyle('header', 'Times', '16', 'B',  '#4A5590', '#C0D3E9');
                $tr->addStyle('footer', 'Times', '12', 'BI', '#4A5590', '#C0D3E9');
                
                $titulo = 'TODOS COLABORADORES';
                if($formdata->colaborador_id > 0)
                {
                    $colaborador = new Pessoa($formdata->colaborador_id);
                    $titulo = utf8_decode($colaborador->pessoa_nome);
                    
                    $totalPonto = $this->retornaPonto($formdata->colaborador_id, $formdata->mes_atividade);
                    
                    $criteria     = new TCriteria;
                    $criteria->add(new TFilter("mes",             "=", $formdata->mes_atividade));
                    $criteria->add(new TFilter("ano",             "=", $formdata->ano_atividade));
                    $criteria->add(new TFilter("colaborador_id",  "=", $formdata->colaborador_id));
                    $repo         = new TRepository('CargaHoraria');
                    $cargaHoraria = $repo->load($criteria);
                    
                    foreach($cargaHoraria as $carga)
                    {
                        $horario = $carga->horario;
                    }
                    
                }
                else 
                {
                    //calcular todos
                    $criteria     = new TCriteria;
                    $criteria->add(new TFilter("mes",             "=", $formdata->mes_atividade));
                    $criteria->add(new TFilter("ano",             "=", $formdata->ano_atividade));
                    $repo         = new TRepository('CargaHoraria');
                    $cargaHoraria = $repo->load($criteria);
                    
                    foreach($cargaHoraria as $carga)
                    {
                        $horario += $this->string->time_to_sec($carga->horario);
                    }
                
                    $criteria = new TCriteria;
                    $criteria->add(new TFilter("origem", "=", 1));
                    $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
                    $criteria->add(new TFilter("ativo", "=", 1));
                    $criteria->add(new TFilter("usuario", "is not "));
                    $repo = new TRepository('Pessoa');
                    $pessoas = $repo->load($criteria);
                
                    foreach($pessoas as $pessoa)
                    {
                        $totalPonto += $this->string->time_to_sec($this->retornaPonto($pessoa->pessoa_codigo, $formdata->mes_atividade));
                    }                
                    
                    $horario     = $this->string->sec_to_time($horario);
                    $totalPonto  = $this->string->sec_to_time($totalPonto);
                }
                
                                
                // report description
                $tr->addRow();
                $tr->addCell('', 'center', 'title');
                $tr->addCell($titulo, 'center', 'title');
                $tr->addCell("{$this->string->array_meses()[$formdata->mes_atividade]}-{$formdata->ano_atividade}", 'center', 'title',2);
                                
                // add a header row
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                $tr->addCell('Indicadores', 'center', 'header');
                $tr->addCell('Horas', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                
                // data rows
                $style = 'datai';
                
                $tr->addRow();
                $tr->addCell('', 'center', $style);
                $tr->addCell(utf8_decode('Carga horária mensal:'), 'left', $style);
                $tr->addCell($this->string->retira_segundos($horario), 'right', $style);
                $tr->addCell('100%', 'right', $style);
                    
                $tr->addRow();
                $tr->addCell('', 'center', $style);
                $tr->addCell(utf8_decode('Horas ponto total:'), 'left', $style);
                $tr->addCell($this->string->retira_segundos($totalPonto), 'right', $style);
                $tr->addCell(round(($this->string->time_to_sec($totalPonto))*100/$this->string->time_to_sec($horario)).'%', 'right', $style);
                                   
                 
                $cri = new TCriteria;
                if($formdata->colaborador_id > 0){
                    $cri->add(new TFilter("colaborador_id", "=", $formdata->colaborador_id));
                }
            	$cri->add(new TFilter("ticket_id", "IN", array(328,514)));
            	$cri->add(new TFilter("extract('month' from data_atividade)", "=", $formdata->mes_atividade));
            	$cri->add(new TFilter("extract('year' from data_atividade)", "=", $formdata->ano_atividade)); 
            	$repo = new TRepository('Atividade');
            	$ausencias = $repo->count($cri); 
                if($ausencias)
            	{
                	$horas = $repo->load($cri);	
                	foreach ($horas as $h)
                	{
                	    $tempo += $this->string->time_to_sec($h->hora_fim) - $this->string->time_to_sec($h->hora_inicio);
                	}
	                
	                $pontoUtil = $this->string->time_to_sec($totalPonto) - $tempo;
	                
	                $tr->addRow();
                    $tr->addCell('', 'center', $style);
                    $tr->addCell(utf8_decode('Horas ponto útil:'), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($this->string->sec_to_time($pontoUtil)), 'right', $style);
                    $tr->addCell(round($pontoUtil*100/$this->string->time_to_sec($horario)).'%', 'right', $style);
                    
                    $tr->addRow();
                    $tr->addCell('', 'center', 'datap');
                    $tr->addCell(utf8_decode('Horas atividade (indicador total):'), 'left', 'datap');
                    $tr->addCell($this->string->retira_segundos($total), 'right', 'datap');
                    $tr->addCell(round(($this->string->time_to_sec($total))*100/$this->string->time_to_sec($totalPonto)).'%', 'right', 'datap');
                    
                    $tr->addRow();
                    $tr->addCell('', 'center', 'datap');
                    $tr->addCell(utf8_decode('Horas atividade (indicador útil):'), 'left', 'datap');
                    $tr->addCell($this->string->retira_segundos($total), 'right', 'datap');
                    $tr->addCell(round(($this->string->time_to_sec($total))*100/$pontoUtil).'%', 'right', 'datap');
                    
            	} 
                else
                {
                    $tr->addRow();
                    $tr->addCell('', 'center', $style);
                    $tr->addCell(utf8_decode('Horas atividade:'), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($total), 'right', $style);
                    $tr->addCell(round(($this->string->time_to_sec($total))*100/$this->string->time_to_sec($totalPonto)).'%', 'right', $style);
                } 
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', 4);
                
                //ATESTADOS MEDICOS
                $objects = Atividade::retornaAtestadosMedicos($formdata->colaborador_id, $formdata->mes_atividade, $formdata->ano_atividade);
                // add a header row
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                $tr->addCell(utf8_decode('Ausências'), 'center', 'header');
                $tr->addCell('Horas', 'center', 'header');
                $tr->addCell('', 'center', 'header');   
                // controls the background filling
                $colour= FALSE;
                $seq = 1;
                // data rows
                foreach ($objects as $row)
                {
                    $style = $colour ? 'datap' : 'datai';
                    
                    $ticket = new Ticket($row['ticket_id']);
                    $tr->addRow();
                    $tr->addCell($seq, 'center', $style);
                    $tr->addCell(utf8_decode($ticket->titulo), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($row['total']), 'right', $style);
                    $tr->addCell(round(($this->string->time_to_sec($row['total']))*100/$this->string->time_to_sec($totalPonto)).'%', 'right', $style);
                    
                    $seq++;                    
                    $colour = !$colour;
                }
                
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', 4);
                
                $objects = Atividade::retornaAtividadesColaborador($formdata->colaborador_id, $formdata->mes_atividade, $formdata->ano_atividade, $tickets);
                
                // add a header row
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                $tr->addCell('Tipo Atividades', 'center', 'header');
                $tr->addCell('Horas', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                // controls the background filling
                $colour= FALSE;
                $seq = 1;
                // data rows
                foreach ($objects as $row)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($seq, 'center', $style);
                    $tr->addCell(utf8_decode($row['nome']), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($row['total']), 'right', $style);
                    $tr->addCell(round(($this->string->time_to_sec($row['total'])/$this->string->time_to_sec($total))*100) .'%', 'right', $style);
                    
                    $seq++;                    
                    $colour = !$colour;
                }
                // footer row
                $tr->addRow();
                $tr->addCell('', 'right', 'footer');
                $tr->addCell('Total:', 'left', 'footer');
                $tr->addCell($this->string->retira_segundos($total), 'right', 'footer');
                $tr->addCell('100%', 'right', 'footer');
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', 4);
                
                $objects = Atividade::retornaAtividadesSistemaColaborador($formdata->colaborador_id, $formdata->mes_atividade, $formdata->ano_atividade, $tickets);
                // add a header row
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                $tr->addCell('Por Sistema', 'center', 'header');
                $tr->addCell('Horas', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                // controls the background filling
                $colour= FALSE;
                $seq = 1;
                // data rows
                foreach ($objects as $row)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($seq, 'center', $style);
                    $tr->addCell(utf8_decode($row['nome']), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($row['total']), 'right', $style);
                    $tr->addCell(round(($this->string->time_to_sec($row['total'])/$this->string->time_to_sec($total))*100) .'%', 'right', $style);
                    
                    $seq++;                    
                    $colour = !$colour;
                }
                // footer row
                $tr->addRow();
                $tr->addCell('', 'right', 'footer');
                $tr->addCell('Total:', 'left', 'footer');
                $tr->addCell($this->string->retira_segundos($total), 'right', 'footer');
                $tr->addCell('100%', 'right', 'footer');
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', 4);
                
                $objects = Atividade::retornaAtividadesClienteColaborador($formdata->colaborador_id, $formdata->mes_atividade, $formdata->ano_atividade, $tickets);
                foreach ($objects as $row)
                {
                    $cliente = new Pessoa($row['solicitante_id']);  
                    if($cliente->origem == 1)
                    {
                        $ind = $cliente->codigo_cadastro_origem;
                    }
                    else
                    {
                        $ind = 999;
                    }
                    $array[$ind] += $this->string->time_to_sec($row['total']); 
                }
                ksort($array);
                // add a header row
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                $tr->addCell('Por Cliente', 'center', 'header');
                $tr->addCell('Horas', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                // controls the background filling
                $colour= FALSE;
                $seq = 1;
                // data rows
                foreach ($array as $key => $value)
                {
                    if($key < 999)
                    {
                        $etd = new Entidade($key);
                        $nome = $key.' - '.$etd->entnomfan;       
                    }
                    else
                    {
                        $nome = $key.' - ECS';
                    }
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($seq, 'center', $style);
                    $tr->addCell(utf8_decode($nome), 'left', $style);
                    $tr->addCell($this->string->retira_segundos($this->string->sec_to_time($value)), 'right', $style);
                    $tr->addCell(round(($value/$this->string->time_to_sec($total))*100) .'%', 'right', $style);
                    $seq++;                    
                    $colour = !$colour;
                }
                
                // footer row
                $tr->addRow();
                $tr->addCell('', 'right', 'footer');
                $tr->addCell('Total:', 'left', 'footer');
                $tr->addCell($this->string->retira_segundos($total), 'right', 'footer');
                $tr->addCell('100%', 'right', 'footer');
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', 4);
                
                // footer row
                $tr->addRow();
                $tr->addCell(date('d/m/Y H:i:s'), 'center', 'footer', 4);
                // stores the file
                
                $var = rand(0, 1000);
                
                if (!file_exists("app/output/Atividade{$var}_{$formdata->mes_atividade}_$formdata->colaborador_id.{$format}") OR is_writable("app/output/Atividade.{$format}"))
                {
                    $tr->save("app/output/Atividade{$var}_{$formdata->mes_atividade}_$formdata->colaborador_id.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/Atividade_{$formdata->mes_atividade}_$formdata->colaborador_id.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/Atividade{$var}_{$formdata->mes_atividade}_$formdata->colaborador_id.{$format}");
                
                // shows the success message
                new TMessage('info', 'Relatorio gerado. Por favor, habilite popups no navegador (somente para web).');
                
                
            }
            else
            {
                new TMessage('error', 'Sem atividade no periodo cadastrado!');
            }
    
            // fill the form with the active record data
            $this->form->setData($formdata);
            
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
