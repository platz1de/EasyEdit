<?php

namespace platz1de\EasyEdit\command\flags;

class CommandFlagParser {
    /**
     * @param string[] $args
     * @return CommandFlagCollection
     */
    public static function parseFlags(EasyEditCommand $command, array $args, Session $session): CommandFlagCollection
    {
        $known = $command->getKnownFlags();
        $ids = [];
        foreach($known as $flag){
            foreach($flag->getAliases() as $alias){
                $known[$alias] = $flag;
            }
            $ids[$flag->getId()] = $flag;
        }
        $flags = new CommandFlagCollection();
        for ($i = 0; $i < count($args); $i++) {
            if(str_starts_with($args[$i], "--")){
                try{
                    $flag = $known[strtolower(substr($args[$i], 2))];
                }catch(\Throwable){
                    throw new UnknownFlagException(substr($args[$i], 2));
                }
                if ($flag->needsArgument()) {
                    if(isset($args[$i + 1]){
                        $flag->parseArgument($session, $args[--$i]);
                    } else {
                        throw new InvalidFlagUsageException($flag);
                    }
                }
                $flags->addFlag($flag);
            } elseif (str_starts_with($args[$i], "-")) {
                $list = str_split(strtolower(substr($args[$i], 1)));
                foreach ($list as $i => $arg) {
                    try{
                        $flag = $ids[$arg];
                    }catch(\Throwable){
                        throw new UnknownFlagException(substr($args[$i], 2));
                    }
                    if($flag->needsArgument){
                        if($i === array_key_last($list)){
                            if(isset($args[$i + 1]){
                                $flag->parseArgument($session, $args[--$i]);
                            } else {
                                throw new InvalidFlagUsageException($flag);
                            }
                        } else {
                            throw new InvalidFlagUsageException($flag);
                        }
                    }
                    $flags->addFlag($flag);
                }
            } else {
                $flags->addFlag($command->getNamedArgumentFlag($i, strtolower($args[$i]));
            }
        }
        return $flags;
    }
}