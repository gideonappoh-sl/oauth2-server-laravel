<?php

namespace LocalizedLeague\Event;

interface GeneratorInterface
{
    /**
     * Release all the added events.
     *
     * @return EventInterface[]
     */
    public function releaseEvents();
}
