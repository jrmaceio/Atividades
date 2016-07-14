<?php
/**
 * UserPontoReport Report
 * @author  <your name here>
 */
class UserPontoReport extends TPage
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
        TTransaction::open('atividade');
        
        // creates the form
        $this->form = new TQuickForm('form_Ponto_report');
        $this->form->class = 'tform'; // change CSS class
        $this->string = new StringsUtil;
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Espelho do ponto');
        
        // create the form fields
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter("origem", "=", 1));
        $criteria->add(new TFilter("codigo_cadastro_origem", "=", 100));
        $criteria->add(new TFilter("ativo", "=", 1));
        $newparam['order'] = 'pessoa_nome';
        $newparam['direction'] = 'asc';
        $criteria->setProperties($newparam); // order, offset
           
        $repo = new TRepository('Pessoa');
        $pessoas = $repo->load($criteria);

        foreach($pessoas as $pessoa) {
            $arrayPessoas[$pessoa->pessoa_codigo] = $pessoa->pessoa_nome;
        }
        
        $colaborador_id                 = new TCombo('colaborador_id');
        $colaborador_id->setDefaultOption(FALSE);
        $colaborador_id->addItems($arrayPessoas);
      
        $mes_atividade                  = new TCombo('mes_atividade');
        $mes_atividade->addItems($this->string->array_meses());
        $mes_atividade->setDefaultOption(FALSE);
        $mes_atividade->setValue(date('m'));
        
        $ano_atividade                  = new TCombo('ano_atividade');
        $anos = array(
                            2015 => '2015',
                            2016 => '2016'
                      );         
        $ano_atividade->addItems($anos);
        $ano_atividade->setDefaultOption(FALSE);
        $ano_atividade->setValue(date('Y'));
        
        $output_type = new TRadioGroup('output_type');


        // add the fields
        $this->form->addQuickField('Colaborador:', $colaborador_id,  250 );
        $this->form->addQuickField('Mês referencia:', $mes_atividade,  250 );
        $this->form->addQuickField('Ano referencia:', $ano_atividade,  100 );

        $this->form->addQuickField('Saida:', $output_type,  100 , new TRequiredValidator);
        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('html');
        $output_type->setLayout('horizontal');
        
        // add the action button
        $this->form->addQuickAction(_t('Generate'), new TAction(array($this, 'onGenerate')), 'fa:cog blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        TTransaction::close();
        parent::add($container);
    }
    
    /**
     * Generate the report
     */
    function onGenerate()
    {
        try
        {
            // open a transaction with database 'atividade'
            TTransaction::open('atividade');
            
            // get the form data into an active record
            $formdata = $this->form->getData();

            $mes = $formdata->mes_atividade;
            $ano = $formdata->ano_atividade;
            
            $ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));        
            $inicio = $ano.'-'.$mes.'-01';
            $final  = $ano.'-'.$mes.'-'.$ultimo_dia;
        
            $repository = new TRepository('Ponto');
            $criteria   = new TCriteria;           
            $criteria->add(new TFilter('colaborador_id', '=', $formdata->colaborador_id));
            $criteria->add(new TFilter("data_ponto", "between", $inicio),  TExpression::AND_OPERATOR);        
            $criteria->add(new TFilter("", "", $final),  TExpression::AND_OPERATOR);

            $newparam['order'] = 'id';
            $newparam['direction'] = 'asc';
            $criteria->setProperties($newparam); // order, offset
           
            $objects = $repository->load($criteria, FALSE);
            $format  = $formdata->output_type;
            
            if ($objects)
            {
                $widths = array(50,75,75,75,75,75,75,50);
                
                switch ($format)
                {
                    case 'html':
                        $tr = new TTableWriterHTML($widths);
                        break;
                    case 'pdf':
                        $tr = new TTableWriterPDF($widths);
                        break;
                    case 'rtf':
                        if (!class_exists('PHPRtfLite_Autoloader'))
                        {
                            PHPRtfLite::registerAutoloader();
                        }
                        $tr = new TTableWriterRTF($widths);
                        break;
                }
                
                // create the document styles
                $tr->addStyle('title', 'Arial', '10', 'B',   '#ffffff', '#A3A3A3');
                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#EEEEEE');
                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                $tr->addStyle('header', 'Arial', '16', '',   '#ffffff', '#6B6B6B');
                $tr->addStyle('footer', 'Times', '10', 'I',  '#000000', '#A3A3A3');
                
                // add a header row
                $tr->addRow();
                $tr->addCell('Espelho do ponto '.utf8_decode($this->string->array_meses()[$formdata->mes_atividade]).' - '.$formdata->ano_atividade, 'center', 'header', 8);
                
                $colaborador = new Pessoa($formdata->colaborador_id);
                $tr->addRow();
                $tr->addCell(utf8_decode($colaborador->pessoa_nome), 'center', 'header', 8);
                
                // add titles row
                $tr->addRow();
                $tr->addCell('Dia', 'center', 'title');
                $tr->addCell(utf8_decode('1ª Entrada'), 'center', 'title');
                $tr->addCell(utf8_decode('1ª Saida'), 'center', 'title');
                $tr->addCell(utf8_decode('2ª Entrada'), 'center', 'title');
                $tr->addCell(utf8_decode('2ª Saida'), 'center', 'title');
                $tr->addCell(utf8_decode('H. Ponto'), 'center', 'title');
                $tr->addCell(utf8_decode('Atividade'), 'center', 'title');
                $tr->addCell(utf8_decode('Prod.'), 'center', 'title');
                
                // controls the background filling
                $colour= FALSE;
                
                $sumHoraPonto     = null;
                $sumHoraAtividade = null;
                
                // data rows
                foreach ($objects as $object)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell(substr($this->string->formatDateBR($object->data_ponto), 0, 2), 'center', $style);
                    $tr->addCell($this->string->retira_segundos($object->hora_entrada), 'center', $style);
                    $tr->addCell($this->string->retira_segundos($object->hora_saida), 'center', $style);
                    $tr->addCell($this->string->retira_segundos($object->hora_entrada_tarde), 'center', $style);
                    $tr->addCell($this->string->retira_segundos($object->hora_saida_tarde), 'center', $style);
                    $tr->addCell($horaPonto     = $this->string->calcularCargaHorariaDiaria(null, $object, null), 'center', $style);
                    $tr->addCell($horaAtividade = $this->string->retornaIntervalo(null, $object, null), 'center', $style);
                    $tr->addCell($this->string->calculaPercentualProdutividade(null, $object, null), 'center', $style);
                    
                    $sumHoraPonto     += $this->string->time_to_sec($horaPonto.':00');
                    $sumHoraAtividade += $this->string->time_to_sec($horaAtividade.':00');
                                        
                    $colour = !$colour;
                }
                
                $produtividade = round($sumHoraAtividade * 100 / $sumHoraPonto);
                
                if($produtividade > 59){
                     $percProd = "<span style='color:#007BFF'><b>".$produtividade."%</b></span>";
                } else {
                     $percProd = "<span style='color:#FFB300'><b>".$produtividade."%</b></span>";
                } 
                
                // footer row
                $tr->addRow();
                $tr->addCell('', 'center', 'footer', 5);
                $tr->addCell(substr($this->string->sec_to_time($sumHoraPonto),0,-3), 'center', 'footer');
                $tr->addCell(substr($this->string->sec_to_time($sumHoraAtividade),0,-3), 'center', 'footer');
                $tr->addCell($percProd, 'center', 'footer');
                
                $criteria     = new TCriteria;
                $criteria->add(new TFilter("mes",             "=", $formdata->mes_atividade));
                $criteria->add(new TFilter("ano",             "=", $formdata->ano_atividade));
                $criteria->add(new TFilter("colaborador_id",  "=", $formdata->colaborador_id));
                $repo         = new TRepository('CargaHoraria');
                $cargaHoraria = $repo->load($criteria);
                $horarioMes = null;
                foreach($cargaHoraria as $carga) {
                    $horarioMes = $carga->horario;
                }
                
                $saldoMes = $sumHoraPonto - $this->string->time_to_sec($horarioMes);
                
                $tr->addRow();
                $tr->addCell('&nbsp', 'center', 'datai', 8);
                
                $tr->addRow();
                $tr->addCell('&nbsp', 'center', 'datai', 8);
                
                $tr->addRow();
                $tr->addCell('% trabalhado', 'left', 'datap', 2);
                $tr->addCell(round($sumHoraPonto * 100 / $this->string->time_to_sec($horarioMes), 2), 'right', 'datap');
                $tr->addCell('', 'center', 'datap', 5);
                
                $tr->addRow();
                $tr->addCell('Horas normais', 'left', 'datap', 2);
                $tr->addCell(substr($horarioMes, 0, -3), 'right', 'datap');
                $tr->addCell('', 'center', 'datap', 5);
                
                $tr->addRow();
                $tr->addCell('Horas trabalhadas', 'left', 'datap', 2);
                $tr->addCell(substr($this->string->sec_to_time($sumHoraPonto),0,-3), 'right', 'datap');
                $tr->addCell('', 'center', 'datap', 5);
  
                $tr->addRow();
                $tr->addCell('Horas de Saldo', 'left', 'datap', 2);
                $tr->addCell(substr($this->string->sec_to_time($saldoMes), 0, -3), 'right', 'datap');
                $tr->addCell('', 'center', 'datap', 5);
                
                $tr->addRow();
                $tr->addCell('&nbsp', 'center', 'datai', 4);
                $tr->addCell('&nbsp', 'center', 'datai', 4);
                
                $tr->addRow();
                $tr->addCell(utf8_decode('Responsável'), 'center', 'datai', 4);
                $tr->addCell(utf8_decode($colaborador->pessoa_nome), 'center', 'datai', 4);
  
                
                // stores the file
                if (!file_exists("app/output/Ponto.{$format}") OR is_writable("app/output/Ponto.{$format}"))
                {
                    $tr->save("app/output/Ponto.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/Ponto.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/Ponto.{$format}");
                
                // shows the success message
                new TMessage('info', 'Relatorio gerado. Por favor, habilite popups no navegador (somente para web).');
            }
            else
            {
                new TMessage('error', 'Sem registros encontrados');
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
