<?php
/**
 * AtividadeClienteReport Report
 * @author  <your name here>
 */
class AtividadeClienteReport extends TPage
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
        
        $this->string = new StringsUtil;
        // creates the form
        $this->form = new TForm('form_Atividade_Cliente');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'width: 500px';
        
        // creates the table container
        $table = new TTable;
        $table->width = '100%';
        
        // add the table inside the form
        $this->form->add($table);

        // define the form title
        $row = $table->addRow();
        $row->class = 'tformtitle';
        $cell = $row->addCell(new TLabel('Indicador por Periodo'));
        $cell->colspan=2;

        // create the form fields
        
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
        
        $colaborador_id                 = new TCombo('colaborador_id');
        $colaborador_id->setDefaultOption(FALSE);
        $colaborador_id->addItems($arrayPessoas);
        
        $cliente_id                     = new TCombo('cliente_id');
        $cliente_id->setDefaultOption(FALSE);
        $cliente_id->addItems($arrayClientes);
        
        $anos = array(
                            2015 => '2015'
                      );         
        
        $mes_atividade_inicial    = new TCombo('mes_atividade_inicial');
        $mes_atividade_inicial->addItems($this->string->array_meses());
        $mes_atividade_inicial->setDefaultOption(FALSE);
        $mes_atividade_inicial->setValue(date('m'));        
        $ano_atividade_inicial    = new TCombo('ano_atividade_inicial');
        $ano_atividade_inicial->addItems($anos);
        $ano_atividade_inicial->setDefaultOption(FALSE);       

        $mes_atividade_final      = new TCombo('mes_atividade_final');
        $mes_atividade_final->addItems($this->string->array_meses());
        $mes_atividade_final->setDefaultOption(FALSE);
        $mes_atividade_final->setValue(date('m'));
        $ano_atividade_final      = new TCombo('ano_atividade_final');           
        $ano_atividade_final->addItems($anos);
        $ano_atividade_final->setDefaultOption(FALSE);
        
        $output_type              = new TRadioGroup('output_type');       

        // define the sizes
        $colaborador_id->setSize(353);
        $cliente_id->setSize(353);
        $mes_atividade_inicial->setSize(250);
        $ano_atividade_inicial->setSize(100);
        $mes_atividade_final->setSize(250);
        $ano_atividade_final->setSize(100);
        $output_type->setSize(100);

        // validations
        $output_type->addValidation('Output', new TRequiredValidator);

        // add one row for each form field
        $table->addRowSet( new TLabel('Colaborador:'), $colaborador_id );
        $table->addRowSet( new TLabel('Cliente:'), $cliente_id );
        $table->addRowSet( new TLabel('Mês ano inicial:'), array($mes_atividade_inicial, $ano_atividade_inicial) );
        $table->addRowSet( new TLabel('Mês ano final:'), array($mes_atividade_final, $ano_atividade_final) );
        $table->addRowSet( new TLabel('Output:'), $output_type );
        
        $this->form->setFields(array($colaborador_id,$cliente_id,$mes_atividade_inicial, $mes_atividade_final, $ano_atividade_inicial, $ano_atividade_final, $output_type));

        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('html');
        $output_type->setLayout('horizontal');
        
        $generate_button = TButton::create('generate', array($this, 'onGenerate'), _t('Generate'), 'fa:check-circle-o green');
        $this->form->addField($generate_button);
        
        // add a row for the form action
        $table->addRowSet( $generate_button, '' )->class = 'tformaction';
        
        parent::add($this->form);
    }

    function colecaoMeses($formdata)
    {
        
        $data_ini  = $formdata->ano_atividade_inicial.'-'.$formdata->mes_atividade_inicial.'-01';
        $data_end  = $formdata->ano_atividade_final.'-'.$formdata->mes_atividade_final.'-01';
 
        $dif = strtotime($data_end) - strtotime($data_ini);
 
        $meses = floor($dif / (60 * 60 * 24 * 30)) + 1;
 
        // Esta mesma data em formato UNIX timestamp
        $timestamp = mktime(0, 0, 0, $formdata->mes_atividade_inicial, '01', $formdata->ano_atividade_inicial); 
        $lista = array();
        $lista[] = '01/'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'/'.$formdata->ano_atividade_inicial;
        
        for ($i = 1; $i < $meses; $i++) {
        
            // Incrementando um mês à esta data
            $nova = strtotime("+1 month", $timestamp);
            
            $lista[] = date("d/m/Y", $nova);
            
            $timestamp = $nova;    
        
        }
        
        return $lista;
    
    }
    
    function retornaPonto($user, $mes, $ano)
    {
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
            
            $validador = new TDuasDatasValidator;
            $validador->validate('Datas', '', array( $formdata->ano_atividade_inicial.'-'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'-01', $formdata->ano_atividade_final.'-'.str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT).'-01' ));
            
            $meses = $this->colecaoMeses($formdata);
            
            $tickets = null;
            $arrayTickets = null;
            if($formdata->cliente_id > 0)
            {
                $cliente = Pessoa::getPessoasEntidade($formdata->cliente_id);
                $retorno = Ticket::getTicketsCliente($cliente);
                $arrayTickets = $retorno;                
                $tickets = implode(",",$retorno);
            }
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter("origem", "=", 1));
            $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
            $criteria->add(new TFilter("ativo", "=", 1));
            $criteria->add(new TFilter("usuario", "is not "));
            $repo = new TRepository('Pessoa');
            $pessoas = $repo->load($criteria);
    
            $criteria = new TCriteria;
            $criteria->add(new TFilter('data_atividade', '<', $formdata->ano_atividade_inicial.'-'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'-01'), TExpression::OR_OPERATOR);
            $criteria->add(new TFilter('data_atividade', '>', $formdata->ano_atividade_final.'-'.str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT).'-'.cal_days_in_month(CAL_GREGORIAN, str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT) , $formdata->ano_atividade_final)), TExpression::OR_OPERATOR);
    	    $repository = new TRepository('Atividade');
    	    $count = $repository->count($criteria);
            
            $format  = $formdata->output_type;
            
            if ($count > 0)
            {
                $widths = array();
                
                switch ($format)
                {
                    case 'html':
                        $tr = new TTableWriterHTML($widths);
                        $break = '<br />';
                        break;
                    case 'pdf':
                        $tr = new TTableWriterPDF($widths);
                        $break = '<br />';
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
                
                $colunas = (count($meses) * 4) +2;
                $arrayMeses = $this->string->array_meses();
                
                // add a header row
                $tr->addRow();
                $tr->addCell('RELATORIO XPTO', 'center', 'header', $colunas);
                
                // add a header row
                $tr->addRow();
                $tr->addCell('Indicadores', 'center', 'header', $colunas);
                
                // add a header row
                $i = 0;
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                foreach($meses as $mes)
                {
                    $i++; 
                    $tr->addCell(substr(strtoupper($arrayMeses[intval(substr($mes, 3, 2))]), 0, 3), 'center', 'header');
                    $tr->addCell('%', 'center', 'header');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                }
                $tr->addCell('TOTAL', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                
                // add a carga horaria row
                $i = 0;
                $totalHorario = null;
                $arrayHorario = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Carga horária mensal:'), 'left', 'datap');
                foreach($meses as $mes)
                {
                    $criteria     = new TCriteria;
                    $criteria->add( new TFilter("mes",            "=", substr($mes, 3, 2)));
                    $criteria->add( new TFilter("ano",            "=", substr($mes, 6, 4)));
                    if($formdata->colaborador_id > 0){
                    $criteria->add(new TFilter("colaborador_id",  "=", $formdata->colaborador_id));
                    }
                    $repo         = new TRepository('CargaHoraria');
                    $cargaHoraria = $repo->load($criteria);
                    $horario = null;
                    foreach($cargaHoraria as $carga)
                    {
                        $horario += $this->string->time_to_sec($carga->horario);
                    }
                    
                    $totalHorario += $horario;
                    $arrayHorario[$i] = $horario;
                    $horario       = $this->string->sec_to_time($horario);
                    
                    $tr->addCell($this->string->retira_segundos($horario), 'center', 'datap');
                    $tr->addCell('100%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                }
                $totalHorario = $this->string->sec_to_time($totalHorario);
                $tr->addCell($this->string->retira_segundos($totalHorario), 'center', 'datap');
                $tr->addCell('100%', 'center', 'datap');   
                
                //add horas ponto total row
                $i = 0;
                $totalPontoTotal = null;
                $arrayPontoTotal = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Horas ponto total:'), 'left', 'datap');
                foreach($meses as $mes)
                {
                    if($formdata->colaborador_id > 0){
                    $totalPonto = $this->retornaPonto($formdata->colaborador_id, substr($mes, 3, 2), substr($mes, 6, 4));
                    $totalPontoTotal += $this->string->time_to_sec($totalPonto);
                    $arrayPontoTotal[$i] = $this->string->time_to_sec($totalPonto);
                    } else {
                    foreach($pessoas as $pessoa)
                    {
                        $totalPonto += $this->string->time_to_sec($this->retornaPonto($pessoa->pessoa_codigo, substr($mes, 3, 2), substr($mes, 6, 4)));
                    }
                    $totalPontoTotal += $totalPonto;
                    $arrayPontoTotal[$i] = $totalPonto;
                    $totalPonto  = $this->string->sec_to_time($totalPonto);
                    }
                    
                    $tr->addCell($this->string->retira_segundos($totalPonto), 'center', 'datap');
                    $tr->addCell(round($arrayPontoTotal[$i] * 100 / $arrayHorario[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                }
                $totalPontoTotal = $this->string->sec_to_time($totalPontoTotal);
                $tr->addCell($this->string->retira_segundos($totalPontoTotal), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalPontoTotal) * 100 / $this->string->time_to_sec($totalHorario) ).'%', 'center', 'datap');  
           
                //add horas ponto util row
                $i = 0;
                $totalPontoUtil = null;
                $arrayPontoUtil = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Horas ponto util:'), 'left', 'datap');
                foreach($meses as $mes)
                {
                    $cri = new TCriteria;
                    if($formdata->colaborador_id > 0){
                    $cri->add(new TFilter("colaborador_id", "=", $formdata->colaborador_id));
                    }
                	$cri->add(new TFilter("ticket_id", "IN", array(328,514)));
                	$cri->add(new TFilter("extract('month' from data_atividade)", "=", substr($mes, 3, 2)));
                	$cri->add(new TFilter("extract('year' from data_atividade)", "=", substr($mes, 6, 4))); 
                	$repo = new TRepository('Atividade');
                	$ausencias = $repo->count($cri);
                	$tempo = 0;
                	if($ausencias)
                	{
                	    $horas = $repo->load($cri);	
                    	foreach ($horas as $h)
                    	{
                    	    $tempo += $this->string->time_to_sec($h->hora_fim) - $this->string->time_to_sec($h->hora_inicio);
                    	}
                	}    
                    	               
                    $pontoUtil = $arrayPontoTotal[$i] - $tempo;
                    $arrayPontoUtil[$i] = $pontoUtil;
                    $totalPontoUtil += $pontoUtil;
                    $pontoUtil  = $this->string->sec_to_time($pontoUtil);
                
                    $tr->addCell($this->string->retira_segundos($pontoUtil), 'center', 'datap');
                    $tr->addCell(round($arrayPontoUtil[$i] * 100 / $arrayHorario[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                }
                $totalPontoUtil = $this->string->sec_to_time($totalPontoUtil);
                $tr->addCell($this->string->retira_segundos($totalPontoUtil), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalPontoUtil) * 100 / $this->string->time_to_sec($totalHorario) ).'%', 'center', 'datap');  
                
                //add atividades indicador total 
                $i = 0;
                $totalAtividadeTotal = null;
                $arrayAtividadeTotal = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Horas atividade (indicador total):'), 'left', 'datap');
                foreach($meses as $mes)
                {
                    $total = Atividade::retornaTotalAtividadesColaborador($formdata->colaborador_id, substr($mes, 3, 2), substr($mes, 6, 4), $tickets);
                    
                    $arrayAtividadeTotal[$i] = $this->string->time_to_sec($total);
                    $totalAtividadeTotal += $this->string->time_to_sec($total);
                    
                    $tr->addCell($this->string->retira_segundos($total), 'center', 'datap');
                    $tr->addCell(round($arrayAtividadeTotal[$i] * 100 / $arrayPontoTotal[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                }
                $totalAtividadeTotal = $this->string->sec_to_time($totalAtividadeTotal);
                $tr->addCell($this->string->retira_segundos($totalAtividadeTotal), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalAtividadeTotal) * 100 / $this->string->time_to_sec($totalPontoTotal) ).'%', 'center', 'datap');  
                
                //add atividades indicador util
                $i = 0;
                $tr->addRow();
                $tr->addCell(utf8_decode('Horas atividade (indicador util):'), 'left', 'datap');
                foreach($meses as $mes)
                {
                    $total = $this->string->sec_to_time($arrayAtividadeTotal[$i]);
                    $tr->addCell($this->string->retira_segundos($total), 'center', 'datap');
                    $tr->addCell(round($arrayAtividadeTotal[$i] * 100 / $arrayPontoUtil[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                }
                //$totalAtividadeTotal = $this->string->sec_to_time($totalAtividadeTotal);
                $tr->addCell($this->string->retira_segundos($totalAtividadeTotal), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalAtividadeTotal) * 100 / $this->string->time_to_sec($totalPontoUtil) ).'%', 'center', 'datap'); 
                
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', $colunas);
                
                // add a header row
                $tr->addRow();
                $tr->addCell(utf8_decode('Ausências'), 'center', 'header', $colunas);
                
                // add a header row
                $i = 0;
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                foreach($meses as $mes)
                {
                    $i++; 
                    $tr->addCell(substr(strtoupper($arrayMeses[intval(substr($mes, 3, 2))]), 0, 3), 'center', 'header');
                    $tr->addCell('%', 'center', 'header');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                }
                $tr->addCell('TOTAL', 'center', 'header');
                $tr->addCell('%', 'center', 'header');   
                
                // add atestado medico row
                $i = 0;
                $totalAtestados = null;
                $arrayAtestados = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Atestado médico:'), 'left', 'datap');
                foreach($meses as $mes)
                {
                
                    $atestados = Atividade::retornaAtestados($formdata->colaborador_id, substr($mes, 3, 2), substr($mes, 6, 4));
                    
                    $arrayAtestados[$i] = $this->string->time_to_sec($atestados);
                    $totalAtestados += $this->string->time_to_sec($atestados);
                    
                    $tr->addCell($this->string->retira_segundos($atestados), 'center', 'datap');
                    $tr->addCell(round($arrayAtestados[$i] * 100 / $arrayPontoTotal[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                
                }
                $totalAtestados = $this->string->sec_to_time($totalAtestados);
                $tr->addCell($this->string->retira_segundos($totalAtestados), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalAtestados) * 100 / $this->string->time_to_sec($totalPontoTotal) ).'%', 'center', 'datap');  
                
                // add ausencias row
                $i = 0;
                $totalAusencias = null;
                $arrayAusencias = array();
                $tr->addRow();
                $tr->addCell(utf8_decode('Ausências:'), 'left', 'datap');
                foreach($meses as $mes)
                {
                
                    $ausencias = Atividade::retornaAusencias($formdata->colaborador_id, substr($mes, 3, 2), substr($mes, 6, 4));                   
                    $arrayAusencias[$i] = $this->string->time_to_sec($ausencias);
                    $totalAusencias += $this->string->time_to_sec($ausencias);
                    
                    $tr->addCell($this->string->retira_segundos($ausencias), 'center', 'datap');
                    $tr->addCell(round($arrayAusencias[$i] * 100 / $arrayPontoTotal[$i]).'%', 'center', 'datap');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++; 
                
                }
                $totalAusencias = $this->string->sec_to_time($totalAusencias);
                $tr->addCell($this->string->retira_segundos($totalAusencias), 'center', 'datap');
                $tr->addCell(round( $this->string->time_to_sec($totalAusencias) * 100 / $this->string->time_to_sec($totalPontoTotal) ).'%', 'center', 'datap'); 
                
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', $colunas);
                
                // add a header row
                $tr->addRow();
                $tr->addCell(utf8_decode('Tipo Atividades'), 'center', 'header', $colunas);
                
                // add Tipo Atividades row
                $i = 0;
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                foreach($meses as $mes)
                {
                    $i++; 
                    $tr->addCell(substr(strtoupper($arrayMeses[intval(substr($mes, 3, 2))]), 0, 3), 'center', 'header');
                    $tr->addCell('%', 'center', 'header');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                }
                $tr->addCell('TOTAL', 'center', 'header');
                $tr->addCell('%', 'center', 'header');
                
                // verifica tipos de atividades do periodo
                $colour= FALSE;
                $tipoAtividades = array();
    	        $objects = Atividade::retornaTiposAtividadesPeriodo($formdata->colaborador_id, $formdata->ano_atividade_inicial.'-'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'-01', $formdata->ano_atividade_final.'-'.str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT).'-'.cal_days_in_month(CAL_GREGORIAN, str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT), $formdata->ano_atividade_final), $tickets );
                $i = 1;
                foreach ($objects as $row)
                {
                    $tipoAtividades[$i] = $row['tipo_atividade_id'];
                    $i++;
                }
                
                foreach($tipoAtividades as $ati)
                {
                    // adiciona as linhas por tipo de atividade   
                    $style = $colour ? 'datap' : 'datai'; 
                    $i = 0;
                    $totalAtividade = null;
                    $arrayAtividade = array();
                    
                    $tipoAtv = new TipoAtividade($ati);
                    
                    $tr->addRow();
                    $tr->addCell(utf8_decode($tipoAtv->nome.':'), 'left', $style);
                    foreach($meses as $mes)
                    {
                        $cri = new TCriteria;
                        if($formdata->colaborador_id > 0){
                        $cri->add(new TFilter("colaborador_id", "=", $formdata->colaborador_id));
                        }
                        if($tickets){
                        $cri->add(new TFilter("ticket_id", "IN", $arrayTickets ));
                        }
                    	$cri->add(new TFilter("extract('month' from data_atividade)", "=", substr($mes, 3, 2)));
                    	$cri->add(new TFilter("extract('year' from data_atividade)", "=", substr($mes, 6, 4))); 
                    	$cri->add(new TFilter("tipo_atividade_id", "=", $ati)); 
                    	$repo = new TRepository('Atividade');
                    	$count = $repo->count($cri);
                    	$tempo = 0;
                    	if($count)
                    	{
                    	    $horas = $repo->load($cri);	
                        	foreach ($horas as $h)
                        	{
                        	    $tempo += $this->string->time_to_sec($h->hora_fim) - $this->string->time_to_sec($h->hora_inicio);
                        	}
                    	}
                        
                        $arrayAtividade[$i] = $tempo;
                        $totalAtividade += $tempo;
                        $tempo  = $this->string->sec_to_time($tempo);
                    
                        $tr->addCell($this->string->retira_segundos($tempo), 'center', $style);
                        $tr->addCell(round($arrayAtividade[$i] * 100 / $arrayAtividadeTotal[$i]).'%', 'center', $style);   
                        $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                        $i++;       
                        $colour = !$colour;
                    }
                    $totalAtividade = $this->string->sec_to_time($totalAtividade);
                    $tr->addCell($this->string->retira_segundos($totalAtividade), 'center', $style);
                    $tr->addCell(round( $this->string->time_to_sec($totalAtividade) * 100 / $this->string->time_to_sec($totalAtividadeTotal) ).'%', 'center', $style); 
                }
                //row total
                $i = 0;
                $tr->addRow();
                $tr->addCell(utf8_decode('<b>Total:</b>'), 'left', 'footer');
                foreach($meses as $mes)
                {
                    $total = $this->string->sec_to_time($arrayAtividadeTotal[$i]);
                    $tr->addCell($this->string->retira_segundos($total), 'center', 'footer');
                    $tr->addCell('100%', 'center', 'footer');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++;
                }
                $tr->addCell($this->string->retira_segundos($totalAtividadeTotal), 'center', 'footer');
                $tr->addCell('100%', 'center', 'footer'); 
                
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', $colunas);
                
                // add Por Sistema row
                $tr->addRow();
                $tr->addCell(utf8_decode('Por sistema'), 'center', 'header', $colunas);
                
                $i = 0;
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                foreach($meses as $mes)
                {
                    $i++; 
                    $tr->addCell(substr(strtoupper($arrayMeses[intval(substr($mes, 3, 2))]), 0, 3), 'center', 'header');
                    $tr->addCell('%', 'center', 'header');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                }
                $tr->addCell('TOTAL', 'center', 'header');
                $tr->addCell('%', 'center', 'header');
                
                // verifica tipos de atividades do periodo
                $colour= FALSE;    
                $tipoSistemas = array();
    	        $objects = Atividade::retornaSistemasPeriodo($formdata->colaborador_id, $formdata->ano_atividade_inicial.'-'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'-01', $formdata->ano_atividade_final.'-'.str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT).'-'.cal_days_in_month(CAL_GREGORIAN, str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT), $formdata->ano_atividade_final), $tickets );
                $i = 1;
                foreach ($objects as $row)
                {
                    $tipoSistemas[$i] = $row['sistema_id'];
                    $i++;
                }
                
                foreach($tipoSistemas as $sis)
                {
                    // adiciona as linhas por tipo de atividade
                    $style = $colour ? 'datap' : 'datai';
                    $i = 0;
                    $totalSistema = null;
                    $arraySistema = array();
                    
                    $sistema = new Sistema($sis);
                    
                    $tr->addRow();
                    $tr->addCell(utf8_decode($sistema->nome.':'), 'left', $style);
                    foreach($meses as $mes)
                    {
                        $cri = new TCriteria;
                        if($formdata->colaborador_id > 0){
                        $cri->add(new TFilter("colaborador_id", "=", $formdata->colaborador_id));
                        }
                        if($tickets){
                        $cri->add(new TFilter("ticket_id", "IN", $arrayTickets ));
                        }
                    	$cri->add(new TFilter("extract('month' from data_atividade)", "=", substr($mes, 3, 2)));
                    	$cri->add(new TFilter("extract('year' from data_atividade)", "=", substr($mes, 6, 4))); 
                    	$cri->add(new TFilter("sistema_id", "=", $sis)); 
                    	$cri->add(new TFilter("ticket_id", "not in", array(328, 514))); 
                    	$repo = new TRepository('Atividade');
                    	$count = $repo->count($cri);
                    	$tempo = 0;
                    	if($count)
                    	{
                    	    $horas = $repo->load($cri);	
                        	foreach ($horas as $h)
                        	{
                        	    $tempo += $this->string->time_to_sec($h->hora_fim) - $this->string->time_to_sec($h->hora_inicio);
                        	}
                    	}
                        
                        $arraySistema[$i] = $tempo;
                        $totalSistema += $tempo;
                        $tempo  = $this->string->sec_to_time($tempo);
                    
                        $tr->addCell($this->string->retira_segundos($tempo), 'center', $style);
                        $tr->addCell(round($arraySistema[$i] * 100 / $arrayAtividadeTotal[$i]).'%', 'center', $style);   
                        $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                        $i++;       
                        $colour = !$colour;
                    }
                    $totalSistema = $this->string->sec_to_time($totalSistema);
                    $tr->addCell($this->string->retira_segundos($totalSistema), 'center', $style);
                    $tr->addCell(round( $this->string->time_to_sec($totalSistema) * 100 / $this->string->time_to_sec($totalAtividadeTotal) ).'%', 'center', $style); 
                }
                //row total
                $i = 0;
                $tr->addRow();
                $tr->addCell(utf8_decode('<b>Total:</b>'), 'left', 'footer');
                foreach($meses as $mes)
                {
                    $total = $this->string->sec_to_time($arrayAtividadeTotal[$i]);
                    $tr->addCell($this->string->retira_segundos($total), 'center', 'footer');
                    $tr->addCell('100%', 'center', 'footer');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++;
                }
                $tr->addCell($this->string->retira_segundos($totalAtividadeTotal), 'center', 'footer');
                $tr->addCell('100%', 'center', 'footer'); 
                
                // division row
                $tr->addRow();
                $tr->addCell($break, 'center', 'datai', $colunas);
                
                // add Por Cliente row
                $tr->addRow();
                $tr->addCell(utf8_decode('Por Cliente'), 'center', 'header', $colunas);
                
                $i = 0;
                $tr->addRow();
                $tr->addCell('', 'center', 'header');
                foreach($meses as $mes)
                {
                    $i++; 
                    $tr->addCell(substr(strtoupper($arrayMeses[intval(substr($mes, 3, 2))]), 0, 3), 'center', 'header');
                    $tr->addCell('%', 'center', 'header');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                }
                $tr->addCell('TOTAL', 'center', 'header');
                $tr->addCell('%', 'center', 'header');
                
                // monta array de clientes
                $tipoClientes = array();
                $objects = Atividade::retornaClientesPeriodo($formdata->colaborador_id, $formdata->ano_atividade_inicial.'-'.str_pad($formdata->mes_atividade_inicial, 2, '0', STR_PAD_LEFT).'-01', $formdata->ano_atividade_final.'-'.str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT).'-'.cal_days_in_month(CAL_GREGORIAN, str_pad($formdata->mes_atividade_final, 2, '0', STR_PAD_LEFT), $formdata->ano_atividade_final), $tickets );
                
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
                    $tipoClientes[$ind] += $this->string->time_to_sec($row['sum']);
                }
                
                arsort($tipoClientes);
                $colour= FALSE;
                foreach($tipoClientes as $key => $value)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $i = 0;
                    $totalClientes = null;
                    $arrayClientes = array();
                    $labelCliente = '';
                    if($key == '999'){
                    $labelCliente = '999 - ECS';
                    } else {
                    $entidade = new Entidade($key);  
                    $labelCliente = str_pad($key, 3, '0', STR_PAD_LEFT) . ' - ' . $entidade->entnomfan;
                    }

                    $tr->addRow();
                    $tr->addCell(utf8_decode($labelCliente), 'left', $style);
                    foreach($meses as $mes)
                    {
                        $cri = new TCriteria;
                        if($formdata->colaborador_id > 0){
                        $cri->add(new TFilter("colaborador_id", "=", $formdata->colaborador_id));
                        }
                        if(!$tickets){
                        $cliente = Pessoa::getPessoasEntidade($key);
                        $retorno = Ticket::getTicketsCliente($cliente);
                        $arrayTickets = $retorno;   
                        } 
                        
                        $cri->add(new TFilter("ticket_id", "IN", $arrayTickets ));
                    	$cri->add(new TFilter("extract('month' from data_atividade)", "=", substr($mes, 3, 2)));
                    	$cri->add(new TFilter("extract('year' from data_atividade)", "=", substr($mes, 6, 4))); 
                    	$cri->add(new TFilter("ticket_id", "not in", array(328, 514))); 
                    	$repo = new TRepository('Atividade');
                    	$count = $repo->count($cri);
                    	$tempo = 0;
                    	if($count)
                    	{
                    	    $horas = $repo->load($cri);	
                        	foreach ($horas as $h)
                        	{
                        	    $tempo += $this->string->time_to_sec($h->hora_fim) - $this->string->time_to_sec($h->hora_inicio);
                        	}
                    	}
                        
                        $arrayClientes[$i] = $tempo;
                        $totalClientes += $tempo;
                        $tempo  = $this->string->sec_to_time($tempo);
                    
                        $tr->addCell($this->string->retira_segundos($tempo), 'center', $style);
                        $tr->addCell(round($arrayClientes[$i] * 100 / $arrayAtividadeTotal[$i]).'%', 'center', $style);   
                        $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                        $i++;       
                        $colour = !$colour;
                    }
                    $totalClientes = $this->string->sec_to_time($totalClientes);
                    $tr->addCell($this->string->retira_segundos($totalClientes), 'center', $style);
                    $tr->addCell(round( $this->string->time_to_sec($totalClientes) * 100 / $this->string->time_to_sec($totalAtividadeTotal) ).'%', 'center', $style); 
                }
                //row total
                $i = 0;
                $tr->addRow();
                $tr->addCell(utf8_decode('<b>Total:</b>'), 'left', 'footer');
                foreach($meses as $mes)
                {
                    $total = $this->string->sec_to_time($arrayAtividadeTotal[$i]);
                    $tr->addCell($this->string->retira_segundos($total), 'center', 'footer');
                    $tr->addCell('100%', 'center', 'footer');   
                    $tr->addCell('&nbsp;&nbsp;&nbsp;&nbsp;', 'center', 'datai');
                    $i++;
                }
                $tr->addCell($this->string->retira_segundos($totalAtividadeTotal), 'center', 'footer');
                $tr->addCell('100%', 'center', 'footer'); 
                
                // stores the file
                if (!file_exists("app/output/Atividade.{$format}") OR is_writable("app/output/Atividade.{$format}"))
                {
                    $tr->save("app/output/Atividade.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/Atividade.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/Atividade.{$format}");
                
                // shows the success message
                new TMessage('info', 'Report generated. Please, enable popups in the browser (just in the web).');
            }
            else
            {
                new TMessage('error', 'No records found');
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
