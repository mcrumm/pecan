<?php

namespace Pecan;

use Pecan\Console\ConsoleInterface;
use Pecan\Console\Output\ConsoleOutputInterface;

/**
 * Console
 *
 * This implementation borrows liberally from its NodeJS counterpart.
 * @link http://nodejs.org/api/console.html
 *
 */
class Console implements ConsoleInterface
{
    /** @var \Pecan\Console\Output\ConsoleOutputInterface */
    protected $output;

    /**
     * @param ConsoleOutputInterface $output
     */
    public function __construct(ConsoleOutputInterface $output = null)
    {
        $this->output = $output;
    }

    /**
     * @param ConsoleOutputInterface $output
     * @return $this
     */
    public function setOutput(ConsoleOutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return ConsoleOutputInterface|null
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Formats a message.
     *
     * @param $message
     * @return string
     * @throws \LogicException When called before Output is set.
     */
    public function format($message)
    {
        if (!$this->output) {
            throw new \LogicException('A ConsoleOutputInterface must be set before calling format().');
        }

        return $this->output->getFormatter()->format($message);
    }


    /**
     * Writes log line(s) to STDOUT.
     *
     * @return $this
     */
    public function log()
    {
        if (!$this->output) { return $this; }

        foreach (func_get_args() as $line) {

            if (is_array($line)) {
                call_user_func_array([ $this->output, 'write' ], $line);
                continue;
            }

            $this->output->write($line);
        };

        return $this;
    }

    /**
     * @see log
     * @return $this
     */
    public function info()
    {
        if (!$this->output) { return $this; }

        call_user_func_array([ $this, 'log' ], func_get_args());

        return $this;
    }

    /**
     * Writes log line(s) to STDERR.
     *
     * @return $this
     */
    public function error()
    {
        if (!$this->output) { return $this; }

        foreach (func_get_args() as $line) {

            if (is_array($line)) {
                call_user_func_array([ $this->output, 'write' ], $line);
                continue;
            }

            $this->output->getErrorOutput()->write($line);
        };

        return $this;
    }

    /**
     * @see error
     * @return $this
     */
    public function warn()
    {
        return $this->_notImplemented(__METHOD__);
    }

    public function dir($object)
    {
        return $this->_notImplemented(__METHOD__);
    }

    public function time($label)
    {
        return $this->_notImplemented(__METHOD__);
    }

    public function timeEnd($label)
    {
        return $this->_notImplemented(__METHOD__);
    }

    public function trace($label)
    {
        return $this->_notImplemented(__METHOD__);
    }

    public function assert($expression, $messages = [])
    {
        $this->_notImplemented(__METHOD__);
        return false;
    }

    private function _notImplemented($method)
    {
        trigger_error($method . ' is not implemented.', E_USER_NOTICE);
        return $this;
    }

}
