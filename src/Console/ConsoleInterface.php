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
    /**
     * @param ConsoleOutputInterface $output
     * @return $this
     */
    public function setOutput(ConsoleOutputInterface $output);

    /**
     * @return \Pecan\Console\Output\ConsoleOutputInterface
     */
    public function getOutput();

    /**
     * @param $message
     * @return string
     */
    public function format($message);

    /**
     * @return $this
     */
    public function log();

    /**
     * @return $this
     */
    public function info();

    /**
     * @return $this
     */
    public function error();

    /**
     * @return $this
     */
    public function warn();

    /**
     * @param string $object
     * @return $this
     */
    public function dir($object);

    /**
     * @param string $label
     * @return $this
     */
    public function time($label);

    /**
     * @param string $label
     * @return $this
     */
    public function timeEnd($label);

    /**
     * @param string $label
     * @return $this
     */
    public function trace($label);

    /**
     * @param string $expression
     * @param array $messages
     * @return boolean
     */
    public function assert($expression, $messages = []);
}
