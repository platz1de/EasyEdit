<?php

namespace platz1de\EasyEdit\command\flags;

class StringCommandFlag extends CommandFlag {
    private int $argument;

    public function __construct (string $name, int $argument = null){
        parent::__construct($name);
        if($argument !== null){
            $this->argument = $argument;
        }
    }


    public function needsArgument () : bool {
        return true;
    }

    /**
     * @param int $argument
     */
    public function setArgument(int $argument) : void{
        $this->argument = $argument;
    }

    /**
     * @return int
     */
    public function getArgument() : int{
        return $this->argument;
    }

    /**
     * @param string $argument
     */
    public function parseArgument(Session $session, string $argument) : void{
        if(!is_numeric($argument)){
            throw new InvalidFlagUsageException($this);
        }
        $this->setArgument((int) $argument);
    }
}