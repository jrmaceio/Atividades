<?php
/**
 * PessoaDepartamento Active Record
 * @author  <your-name-here>
 */
class PessoaDepartamento extends TRecord
{
    const TABLENAME = 'public.pessoa_departamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('colaborador_id');
        parent::addAttribute('departamento_id');
    }

     /**
     * Method set_departamento
     * Sample of usage: $pd->departamento = $object;
     * @param $object Instance of Departamento
     */
    public function set_departamento(Departamento $object)
    {
        $this->departamento         = $object;
        $this->departamento_id      = $object->id;
    }
    
    /**
     * Method get_departamento
     * Sample of usage: $pd->departamento->attribute;
     * @returns Departamento instance
     */
    public function get_departamento()
    {
        // loads the associated object
        if (empty($this->departamento))
            $this->departamento = new Departamento($this->departamento_id);
    
        // returns the associated object
        return $this->departamento;
    }

    /**
     * Method set_colaborador
     * Sample of usage: $dp->colaborador = $object;
     * @param $object Instance of Pessoa
     */
    public function set_colaborador(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->colaborador_id = $object->pessoa_codigo;
    }
    
    /**
     * Method get_colaborador
     * Sample of usage: $dp->colaborador->pessoa_nome;
     * @returns Pessoa instance
     */
    public function get_colaborador()
    {
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->colaborador_id);
    
        // returns the associated object
        return $this->pessoa;
    }



}
