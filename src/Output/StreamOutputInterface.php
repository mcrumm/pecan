<?php

namespace Pecan\Output;

use React\Stream\Stream;
use Symfony\Component\Console\Output\OutputInterface;

interface StreamOutputInterface extends OutputInterface
{
    public function setOutputStream(Stream $stream);
    public function on($event, callable $listener);
    public function emit($event, array $arguments = []);
    public function end($data = null);
}
