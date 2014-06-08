<?php

namespace Pecan\Console\Output;

use React\Stream\Stream;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output as Output;

/**
 * StreamOutput
 *
 * This class borrows logic from Symfony's Console component for ensuring
 * compatibility with the PHP's output stream.
 */
class StreamOutput extends Output implements StreamOutputInterface
{
    /** @var Stream */
    private $stream;

    /**
     * Constructor.
     *
     * @param Stream $stream
     * @param integer $verbosity
     * @param null $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct(Stream $stream, $verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
    {
        $this->setStream($stream);

        if (null === $decorated) {
            $decorated = $this->hasColorSupport();
        }

        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * @param Stream $stream
     * @return $this
     */
    public function setStream(Stream $stream)
    {
        $this->stream = $stream;

        $this->stream->on('error', function($error) {
            $this->emit('error', [ $error, $this ]);
        });

        return $this;
    }

    /**
     * @param null $data
     */
    public function end($data = null)
    {
        $this->stream->end($data);
    }

    /**
     * Helper method to proxy events to the wrapped stream.
     *
     * @param $event
     * @param array $arguments
     */
    public function emit($event, array $arguments = [])
    {
        $this->stream->emit($event, $arguments);
    }

    /**
     * Helper method to proxy event listeners to the wrapped stream.
     *
     * @param $event
     * @param callable $listener
     */
    public function on($event, callable $listener)
    {
        $this->stream->on($event, $listener);
    }

    /**
     * Helper method to proxy event listeners to the wrapped stream.
     *
     * @param $event
     * @param callable $listener
     */
    public function once($event, callable $listener)
    {
        $this->stream->once($event, $listener);
    }

    /**
     * Writes a message to the output.
     *
     * @param string $message A message to write to the output
     * @param boolean $newline Whether to add a newline or not
     */
    protected function doWrite($message, $newline)
    {
        $this->stream->write($newline ? $message . PHP_EOL : $message);
    }

    /**
     * Returns true if the stream supports colorization.
     *
     * Colorization is disabled if not supported by the stream:
     *
     *  -  Windows without Ansicon and ConEmu
     *  -  non tty consoles
     *
     * @return Boolean true if the stream supports colorization, false otherwise
     */
    protected function hasColorSupport()
    {
        // @codeCoverageIgnoreStart
        if (DIRECTORY_SEPARATOR == '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }

        return function_exists('posix_isatty') && @posix_isatty($this->stream->stream);
        // @codeCoverageIgnoreEnd
    }

}
