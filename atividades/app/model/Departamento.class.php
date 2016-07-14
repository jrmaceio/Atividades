<?php
/**
 * Departamento Active Record
 * @author  <your-name-here>
 */
class Departamento extends TRecord
{
    const TABLENAME = 'public.departamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
    }


}
