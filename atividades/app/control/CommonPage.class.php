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


        $data1 = '2015-09-01';
        $data2 = '2015-11-01';
            
        if(strtotime($data1) > strtotime($data2)){
            echo 'erroooouuuuu';
        }     
            

        parent::add('testes:');
    }
       
}
?>