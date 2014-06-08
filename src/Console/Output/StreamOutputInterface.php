<?php

namespace Pecan\Console\Output;

use React\Stream\Stream;
use Symfony\Component\Console\Output\OutputInterface;

interface StreamOutputInterface extends OutputInterface
{
    /**
     * @return StreamOutput
     */
    public function setStream(Stream $stream);

    /**
     * @return void
     */
    public function on($event, callable $listener);

    /**
     * @return void
     */
    public function once($event, callable $listener);

    /**
     * @return void
     */
    public function emit($event, array $arguments = []);

    /**
     * @return void
     */
    public function end($data = null);
}
