<?php

namespace Pecan\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConsoleOutput
 *
 * This class borrows logic from the Symfony Console
 * component to define an interface for working with STDERR.
 */
abstract class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    /** @var \Pecan\Console\Output\StreamOutputInterface */
    protected $stderr;

    /**
     * {@inheritDoc}
     */
    public function setErrorOutput(OutputInterface $error)
    {
        if (!$error instanceof StreamOutputInterface) {
            throw new \RuntimeException('Error Output must implement \\Pecan\\Console\\Output\\StreamOutputInterface');
        }

        $this->stderr = $error;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorOutput()
    {
        return $this->stderr;
    }

    /**
     * {@inheritDoc}
     */
    public function emit($event, array $arguments = [])
    {
        parent::emit($event, $arguments);
        $this->stderr->emit($event, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function on($event, callable $listener)
    {
        parent::on($event, $listener);
        $this->stderr->on($event, $listener);
    }

    /**
     * Helper method to proxy event listeners to the wrapped stream.
     *
     * @param $event
     * @param callable $listener
     */
    public function once($event, callable $listener)
    {
        parent::once($event, $listener);
        $this->stderr->once($event, $listener);
    }

}
