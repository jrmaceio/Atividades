<?php
/**
 * CargaHoraria Active Record
 * @author  <your-name-here>
 */
class CargaHoraria extends TRecord
{
    const TABLENAME = 'public.carga_horaria';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
        
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mes');
        parent::addAttribute('ano');
        parent::addAttribute('horario');
        parent::addAttribute('colaborador_id');
    }
    
    /**
     * Method set_pessoa
     * Sample of usage: $ticket->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->colaborador_id = $object->pessoa_codigo;
    }
    
    /**
     * Method get_pessoa
     * Sample of usage: $ticket->pessoa->pessoa_nome;
     * @returns Pessoa instance
     */
    public function get_pessoa()
    {
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->colaborador_id);
    
        // returns the associated object
        return $this->pessoa;
    }

}
