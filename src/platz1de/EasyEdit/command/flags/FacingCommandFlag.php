<?php

namespace platz1de\EasyEdit\command\flags;

class FacingCommandFlag extends IntCommandFlag {
    /**
     * @param string $argument
     */
    public function parseArgument(Session $session, string $argument) : void{
        $this->setArgument(ArgumentParser::parseFacing($session, $argument));
    }
}