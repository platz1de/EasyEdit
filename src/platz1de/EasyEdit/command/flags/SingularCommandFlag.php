<?php

namespace platz1de\EasyEdit\command\flags;

class SingularCommandFlag extends CommandFlag
{
    public function needsArgument() : bool{
        return false;
    }
}