<?php

namespace Pecan\Output;

interface StreamOutputInterface
{
    public function on($event, callable $listener);
    public function emit($event, array $arguments = []);
    public function end($data = null);
}
