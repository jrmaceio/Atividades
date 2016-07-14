<?php
class CalculoHorario
{
    
    public function retornoCargaHorariaDiaria($object)
    {
        $cargaHoraria = null;
        $string = new StringsUtil;
                
        if($object->hora_saida and $object->hora_saida_tarde){
            $HoraEntrada         = new DateTime($object->hora_entrada);
            $HoraSaida           = new DateTime($object->hora_saida);
            
            $cargaHoraria = $HoraSaida->diff($HoraEntrada)->format('%H:%I:%S');
            $totalPrimeiroTurno  = $string->time_to_sec($cargaHoraria);
            
            $HoraEntradaTarde    = new DateTime($object->hora_entrada_tarde);
            $HoraSaidaTarde      = new DateTime($object->hora_saida_tarde);
            
            $cargaHoraria = $HoraSaidaTarde->diff($HoraEntradaTarde)->format('%H:%I:%S');
            $totalSegundoTurno  = $string->time_to_sec($cargaHoraria);
            
            $total = $totalSegundoTurno + $totalPrimeiroTurno;
            $cargaHoraria = $string->sec_to_time($total);
            
            $cargaHoraria = substr($cargaHoraria,0,-3);
        }
        
        if($object->hora_saida and !$object->hora_saida_tarde) {
            $HoraEntrada         = new DateTime($object->hora_entrada);
            $HoraSaida           = new DateTime($object->hora_saida);
            
            $cargaHoraria = $HoraSaida->diff($HoraEntrada)->format('%H:%I');
        }
        
        if(!$object->hora_saida and $object->hora_saida_tarde) {
            $HoraEntrada         = new DateTime($object->hora_entrada_tarde);
            $HoraSaida           = new DateTime($object->hora_saida_tarde);
            
            $cargaHoraria = $HoraSaida->diff($HoraEntrada)->format('%H:%I');
        }
        
        return $cargaHoraria;
    }
    
}

?>