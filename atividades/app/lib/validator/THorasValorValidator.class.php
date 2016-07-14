<?php
/**
 * Três campos
 *
 * @version    1.0
 * @package    validator
 * @author     Gustavo Emmel
 * @copyright  Copyright (c) 2006-2012 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THorasValorValidator extends TFieldValidator
{
    /**
     * Validate a given value
     * @param $label Identifies the value to be validated in case of exception
     * @param $value Value to be validated
     * @param $parameters aditional parameters for validation (ex: mask)
     */
    public function validate($label, $value, $parameters = NULL)
    {
        
        //$parameters[0] = status
        //$parameters[1] = horas
        //$parameters[1] = valor
        
        $status_validos = array(1, 3, 6);
                
        if((in_array($parameters[0], $status_validos)) && (!is_numeric($parameters[1]) || !$parameters[2]))
        {
            $erro = "Qte horas e Valor hora obrigatórios";
        }
                
        if(isset($erro))
        {
            throw new Exception($erro);
        }
        
    }
}
?>