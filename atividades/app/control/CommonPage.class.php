<?php
/**
 * FormSeekButtonView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class CommonPage extends TPage
{
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();

        TTransaction::open('atividade');
     
        $param['id'] = '';     
     
        if($param['id']){
            $pd = new PessoaDepartamento($param['id']);
        } else {
            $pd = new PessoaDepartamento();
        }
     
        $pd->departamento_id     = $param['departamento_id'];
        $pd->colaborador_id      = $param['colaborador_id'];
     
        $pd->store();
        
        TTransaction::close();

        parent::add('testes:');
    }
       
}
?>