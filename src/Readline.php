<?php

namespace Pecan;

use Evenement\EventEmitterTrait;
use Pecan\Console\Input;
use Pecan\Console\Output\PecanOutput;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;

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

    /** @var \Pecan\Console\Input */
    protected $input;

    /** @var \Pecan\Console\Output\ConsoleOutputInterface */
    protected $output;

    /** @var \Pecan\Console\ConsoleInterface */
    protected $console;

    /** @var boolean */
    private $hasReadline, $terminal, $paused = true, $closed = false, $running = false;

    /** @var callable */
    private $completer, $questionCallback;

    /** @var string */
    private $prompt, $oldPrompt;

    /**
     * @param callable $completer An optional callback for command auto-completion.
     * @param boolean $terminal Whether this console is a TTY or not.
     * @throws \LogicException if readline support is not available.
     */
    public function __construct(callable $completer = null, $terminal = null)
    {
        $this->hasReadline  = Readline::isFullySupported();

        if (!$completer && $this->hasReadline) { $completer = function () { return []; }; }

        $this->setCompleter($completer);

        $this->setPrompt('> ');
    }

    /**
     * @param LoopInterface $loop
     * @param float $interval
     * @throws \LogicException When called while already running.
     */
    public function start(LoopInterface $loop, $interval = 0.1)
    {
        if ($this->running) {
            throw new \LogicException('Readline is already running.');
        }

        $this->running  = true;
        $this->input    = new Input($loop);
        $this->output   = new PecanOutput($loop);
        $this->console  = new Console($this->output);

        if (!$this->terminal) {
            $this->terminal = $this->output->isDecorated();
        }

        // Setup I/O Error Emitters
        $errorEmitter = $this->getErrorEmitter();
        $this->output->on('error',  $errorEmitter);
        $this->input->on('error',   $errorEmitter);
        $this->input->on('end', function () { $this->close(); });

        if ($this->hasReadline) {
            $loop->addPeriodicTimer($interval, [ $this, 'readlineHandler' ]);
        } else {
            $this->input->on('data', [ $this, 'lineHandler' ]);
            $this->input->resume();
            $this->paused = false;
        }

        $this->emit('running', [ $this ]);
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
        return $this->console->format($this->prompt);
    }

    /**
     * @return Console\ConsoleInterface
     */
    public function console()
    {
        if ($this->running) {
            return $this->console;
        }
    }

    /**
     * Writes the prompt to the output.
     *
     * @return $this
     */
    public function prompt()
    {
        if ($this->hasReadline) {
            readline_callback_handler_install($this->getPrompt(), [ $this, 'lineHandler' ]);
        } else {
            if ($this->paused) { $this->resume(); }
            $this->console->log($this->getPrompt());
        }

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
     * @param $line
     */
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

        if ($this->hasReadline) {
            //readline_callback_handler_remove();
        }
    }

    public function readlineHandler(Timer $timer)
    {
        $w  = NULL;
        $e  = NULL;
        $r  = [ $this->input->stream ];
        $n  = stream_select($r, $w, $e, NULL);
        if ($n && in_array($this->input->stream, $r)) {
            readline_callback_read_char();
        }
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

}
