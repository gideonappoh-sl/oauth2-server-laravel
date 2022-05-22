<?php

namespace LocalizedLeague\Event;

class Generator implements GeneratorInterface
{
    use GeneratorTrait {
        addEvent as public;
    }
}
