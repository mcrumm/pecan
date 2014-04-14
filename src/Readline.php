<?php

namespace Pecan;

use Evenement\EventEmitterTrait;
use Pecan\Console\Input;
use Pecan\Console\ConsoleInterface;

/**
 * Readline allows reading of a stream (such as STDIN) on a line-by-line basis.
 *
 * This implementation is heavily inspired by the NodeJS Readline component.
 * @link http://nodejs.org/api/readline.html
 *
 * @event line
 * @event pause
 * @event resume
 * @event error
 * @event close
 */
class Readline
{
    use EventEmitterTrait;

    private $input;
    private $output;
    private $hasReadline;
    private $completer;
    private $prompt;
    private $oldPrompt;
    private $paused = true;
    private $closed = false;

    /** @var callable */
    private $questionCallback;

    /**
     * @param Input $input
     * @param ConsoleInterface $console
     * @param callable $completer An optional callback for command auto-completion.
     * @throws \LogicException if readline support is not available.
     */
    public function __construct(Input $input, ConsoleInterface $console, callable $completer = null)
    {
        $this->input        = $input;
        $this->output       = $console->getOutput();
        $this->console      = $console;
        $this->hasReadline  = Readline::isFullySupported();

        if (!$completer && $this->hasReadline) { $completer = function () { return []; }; }

        $this->setCompleter($completer);

        $errorEmitter = $this->getErrorEmitter();

        $this->output->on('error', $errorEmitter);
        $this->input->on('error', $errorEmitter);
        $this->input->on('data', [ $this, 'lineHandler' ]);
        $this->input->on('end', function () { $this->close(); });

        $this->setPrompt('> ');
    }

    /**
     * Sets the auto-complete callback
     *
     * @param callable $completer
     * @return $this
     * @throws \LogicException When PHP is not compiled with readline support.
     */
    public function setCompleter(callable $completer)
    {
        if (!Readline::isFullySupported()) {
            throw new \LogicException(sprintf('%s requires readline support to use the completer.', __CLASS__));
        }

        $this->completer = $completer;
        return $this;
    }

    /**
     * Sets the terminal prompt.
     *
     * @param string $prompt
     * @return $this
     */
    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Renders the prompt.
     *
     * @return string
     */
    public function getPrompt()
    {
        // using the formatter here is required when using readline
        return $this->output->getFormatter()->format($this->prompt);
    }

    /**
     * Writes the prompt to the output.
     *
     * @return $this
     */
    public function prompt()
    {
        if ($this->paused) { $this->resume(); }
        $this->output->write($this->getPrompt());
        return $this;
    }

    /**
     * Prompts for input.
     *
     * @param string $query
     * @param callable $callback
     * @return $this
     */
    public function question($query, callable $callback)
    {
        if (!is_callable($callback)) { return $this; }

        if ($this->questionCallback) {
            $this->prompt();
        } else {
            $this->oldPrompt = $this->prompt;
            $this->setPrompt($query);
            $this->questionCallback = $callback;
            $this->prompt();
        }

        return $this;
    }

    /**
     * Pauses the input stream.
     * @return $this
     */
    public function pause()
    {
        if ($this->paused) { return $this; }
        $this->input->pause();
        $this->paused = true;
        $this->emit('pause');
        return $this;
    }

    /**
     * Resumes the input stream.
     * @return $this
     */
    public function resume()
    {
        if (!$this->paused) { return $this; }
        $this->input->resume();
        $this->paused = false;
        $this->emit('resume');
        return $this;
    }

    /**
     * Closes the streams.
     * @return $this
     */
    public function close()
    {
        if ($this->closed) { return; }
        $this->pause();
        $this->closed = true;
        $this->emit('close');
    }

    /**
     * Returns whether or not readline is available to PHP.
     *
     * @return boolean
     */
    static public function isFullySupported()
    {
        return function_exists('readline');
    }

    /**
     * @return callable
     */
    protected function getErrorEmitter()
    {
        return function ($error, $input) {
            $this->emit('error', [ $error, $input ]);
        };
    }

    public function lineHandler($line)
    {
        if ($this->questionCallback) {
            $cb = $this->questionCallback;
            $this->questionCallback = null;
            $this->setPrompt($this->oldPrompt);
            $cb($line);
        } else {
            $this->emit('line', [ $line ]);
        }
    }

}
