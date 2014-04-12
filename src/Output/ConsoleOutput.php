<?php

namespace Pecan\Output;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * ConsoleOutput
 *
 * This class borrows logic from Symfony's Console component for ensuring
 * compatibility with the PHP's output and error streams.
 */
class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private $pipes;
    private $stderr;

    /**
     * Constructor.
     *
     * @param LoopInterface $loop
     * @param bool|int $verbosity
     * @param null $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct(LoopInterface $loop, $verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
    {
        $this->initializePipes();

        parent::__construct(new Stream($this->pipes[0], $loop), $verbosity, $decorated, $formatter);

        $this->stderr = new StreamOutput(new Stream($this->pipes[1], $loop), $verbosity, $decorated, $formatter);
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
    public function setDecorated($decorated)
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    /**
     * {@inheritDoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    /**
     * {@inheritDoc}
     */
    public function setVerbosity($level)
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
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
    public function setErrorOutput(OutputInterface $error)
    {
        if (!$error instanceof StreamOutputInterface) {
            throw new \RuntimeException('Error Output must implement \\Pecan\\Output\\StreamOutputInterface');
        }

        $this->stderr = $error;
    }

    /**
     * Initialize the STDOUT and STDERR pipes.
     */
    protected function initializePipes()
    {
        $this->pipes = [
            fopen($this->hasStdoutSupport() ? 'php://stdout' : 'php://output', 'w'),
            fopen('php://stderr', 'w'),
        ];

        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, 0);
        }
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * IBM iSeries (OS400) exhibits character-encoding issues when writing to
     * STDOUT and doesn't properly convert ASCII to EBCDIC, resulting in garbage
     * output.
     *
     * @return boolean
     */
    protected function hasStdoutSupport()
    {
        return ('OS400' != php_uname('s'));
    }
}
