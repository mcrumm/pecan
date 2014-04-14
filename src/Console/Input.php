<?php

namespace Pecan\Console;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

/**
 * Input is a non-blocking instance of the STDIN Stream.
 */
class Input extends Stream
{
    protected $writable = false;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        parent::__construct(STDIN, $loop);

        stream_set_blocking($this->stream, 0);

        $this->pause();
    }

}
