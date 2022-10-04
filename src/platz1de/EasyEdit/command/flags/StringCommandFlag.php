<?php

namespace platz1de\EasyEdit\command\flags;

class StringCommandFlag extends CommandFlag {
    private string $argument;

    public function __construct (string $name, string $argument = null){
        parent::__construct($name);
        if($argument !== null){
            $this->argument = $argument;
        }
    }


    public function needsArgument () : bool {
        return true;
    }

    /**
     * @param string $argument
     */
    public function setArgument(string $argument) : void{
        $this->argument = $argument;
    }

    /**
     * @return string
     */
    public function getArgument() : string{
        return $this->argument;
    }

    /**
     * @param string $argument
     */
    public function parseArgument(Session $session, string $argument) : void{

        $this->setArgument($argument);
    }
}