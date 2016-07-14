<?php
/**
 * Ponto Active Record
 * @author  <your-name-here>
 */
class Ponto extends TRecord
{
    const TABLENAME = 'ponto';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_ponto');
        parent::addAttribute('hora_entrada');
        parent::addAttribute('hora_saida');
        parent::addAttribute('colaborador_id');
        parent::addAttribute('hora_entrada_tarde');
        parent::addAttribute('hora_saida_tarde');
    }
    
    public function retornaUltimoPonto($user)
    {
        
        $conn = TTransaction::get();
        $result = $conn->query("SELECT data_ponto, id FROM ponto WHERE colaborador_id = {$user} order by data_ponto desc, id desc limit 1");
        
        foreach ($result as $row)
        {
            $data = $row['id'];
        }
        
        if(!$data)
        {
            $data = 0;
        }
        
        return $data;
        
    }
        
    public function retornaHoraInicio($data, $user, $horario)
    {
        if($data)
        {
            $conn = TTransaction::get();
            
            // busca ultima hora da atividade do dia com base no turno
            $hora = null;
            $result = $conn->query("SELECT MAX(hora_fim) as hora_fim FROM atividade WHERE data_atividade = '{$data}' AND colaborador_id = {$user} and hora_fim > '{$horario}' LIMIT 1");
            foreach ($result as $row)
            {
                $hora = $row['hora_fim'];
            }
                        
            return $hora;
            
        }
        else
        {
            return '08:00:00';
        }
        
    }
    
    public function saldoHorasMes($user)
    {
        $string = new StringsUtil;        
        $mes = date('m');
        $ano = date('Y');
        $conn = TTransaction::get();
        $result = $conn->query("select (hora_saida - hora_entrada) as horario_manha, (hora_saida_tarde - hora_entrada_tarde) as horario_tarde, 
                                (coalesce((hora_saida_tarde - hora_entrada_tarde),'00:00:00') + coalesce((hora_saida - hora_entrada), '00:00:00') ) as horario_total 
                                from ponto 
                                where colaborador_id = {$user} and 
                                      extract('month' from data_ponto) = {$mes} and 
                                      extract('year' from data_ponto) = {$ano} and 
                                      (hora_saida is not null or hora_saida_tarde is not null)");
        
        $cargaHoraria = $string->time_to_sec('08:48:00');
        $saldo = null;
        
        foreach ($result as $row) {
            $saldo += $string->time_to_sec($row['horario_total']) - $cargaHoraria;
        }
        
        return $string->sec_to_time($saldo);
    }
    
    public function horaPreenchidas($data, $user)
    {

        $conn = TTransaction::get();
        $result = $conn->query("select sum((hora_fim - hora_inicio)) as intervalo from atividade where data_atividade = '{$data}' and colaborador_id = {$user} and ticket_id not in (328, 514)");
        
        foreach ($result as $row)
        {
            $intervalo = $row['intervalo'];
        }
        
        return $intervalo;
        
    }
    
}