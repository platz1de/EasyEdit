<?php

namespace platz1de\EasyEdit\command\flags;

abstract class CommandFlag {
    private string $name;
    /**
     * @var string[]
     */
    private array $aliases;
    private string $id;

    /**
     * @param string $name
     * @param string $aliases
     * @param string $id
     */
    public function __construct(string $name, array $aliases, string $id) {
        $this->name = $name;
        $this->aliases = $aliases;
        $this->id = $id;
    }

    abstract public function setArgument(mixed $argument): void;

    abstract public function getArgument() : mixed;

    public function getName() : string{
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getAliases() : array{
        return $this->aliases;
    }

    public function getId() : string{
        return $this->id;
    }

    abstract public function needsArgument() : bool;

    abstract public function parseArgument(Session $session, string $argument) : void;
}