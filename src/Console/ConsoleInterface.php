<?php

namespace Pecan\Console;

use Pecan\Console\Output\ConsoleOutputInterface;

/**
 * ConsoleInterface
 *
 * This interfaces attempts parity with its NodeJS counterpart.
 * @link http://nodejs.org/api/console.html
 */
interface ConsoleInterface
{
    public function setOutput(ConsoleOutputInterface $output);
    public function getOutput();
    public function format($message);
    public function log();
    public function info();
    public function error();
    public function warn();
    public function dir($object);
    public function time($label);
    public function timeEnd($label);
    public function trace($label);
    public function assert($expression, $messages = []);
}
