<?php
class CommonPage extends TPage
{
    public function __construct()
    {
        parent::__construct();
        parent::add(new TLabel('Common page'));
 
 
        $string = new StringsUtil;
         
    	TTransaction::open('atividade');
    	
    	$cri = new TCriteria;
    //	$cri->add(new TFilter("colaborador_id", "=", 1));
    	$cri->add(new TFilter("ticket_id", "IN", array(328,514)));
    	$cri->add(new TFilter("extract('month' from data_atividade)", "=", '10'));
    	$cri->add(new TFilter("extract('year' from data_atividade)", "=", '2015')); 
    	$repo = new TRepository('Atividade');
    	$ausencias = $repo->count($cri);
    	
    	if($ausencias)
    	{
    	
        	$horas = $repo->load($cri);
        	
        	foreach ($horas as $h)
        	{
        	    $tempo += $string->time_to_sec($h->hora_fim) - $string->time_to_sec($h->hora_inicio);
        	    
        	}
        	
        	echo $string->sec_to_time($tempo);
        	
    	}
    	
    	//echo $ausencias;
    	TTransaction::close();
    	
    	
                
    }
}
?>