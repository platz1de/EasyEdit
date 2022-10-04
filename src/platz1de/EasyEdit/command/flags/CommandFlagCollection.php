<?php

namespace platz1de\EasyEdit\command\flags;

class CommandFlagCollection {
    /**
     * @var CommandFlag[]
     */
    private array $flags;

    public function addFlag(CommandFlag $flag) : void{
        if(isset($flag->getName())){
            throw new DuplicateFlagExcaption($flag);
        }
        $this->flags[$flag->getName()] = $flag;
    }

    public function getStringFlag(string $name): StringCommandFlag{
        $flag = $this->flags[$name];
        if(!$flag instanceof StringCommandFlag){
            throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expacted String");
        }
        return $flag;
    }

    public function getFlag(string $name): IntCommandFlag{
        $flag = $this->flags[$name];
        if(!$flag instanceof IntCommandFlag){
            throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expacted Integer");
        }
        return $flag;
    }

    public function hasFlag(string $name) : bool{
        return isset($this->flags[$name]);
    }
}