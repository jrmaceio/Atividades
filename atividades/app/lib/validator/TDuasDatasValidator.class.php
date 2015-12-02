<?php
/**
 * Dois campos
 *
 * @version    1.0
 * @package    validator
 * @author     Gustavo Emmel
 * @copyright  Copyright (c) 2006-2012 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDuasDatasValidator extends TFieldValidator
{
    /**
     * Validate a given value
     * @param $label Identifies the value to be validated in case of exception
     * @param $value Value to be validated
     * @param $parameters aditional parameters for validation (ex: mask)
     */
    public function validate($label, $value, $parameters = NULL)
    {
        
        $erro = null;
        
        $data1 = $parameters[0];
        $data2 = $parameters[1];
            
        if(strtotime($data1) > strtotime($data2)){
            $erro = "Data inicial maior que data final";
        } 
        
        if(isset($erro))
        {
            throw new Exception($erro);
        }
        
    }
}
?>