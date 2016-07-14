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
class THorarioTurnosValidator extends TFieldValidator
{
    /**
     * Validate a given value
     * @param $label Identifies the value to be validated in case of exception
     * @param $value Value to be validated
     * @param $parameters aditional parameters for validation (ex: mask)
     */
    public function validate($label, $value, $parameters = NULL)
    {
        
        //$parameters[0] = entrada 1º turno
        //$parameters[1] = saida 1º turno
        //$parameters[2] = entrada 2º turno
        //$parameters[3] = saida 2º turno
        //$parameters[4] = usuario
        //$parameters[5] = data
                
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
