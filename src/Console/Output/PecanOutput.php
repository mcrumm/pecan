<?php

namespace Pecan\Console\Output;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * PecanOutput
 *
 * This class borrows logic from Symfony's Console component for ensuring
 * compatibility with the PHP's output and error streams.
 */
class PecanOutput extends ConsoleOutput
{
    private $pipes;

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

        $this->setErrorOutput(new StreamOutput(new Stream($this->pipes[1], $loop), $verbosity, $decorated, $formatter));
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
