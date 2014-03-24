<?php

namespace Pecan;

use Evenement\EventEmitter;
use Evenement\EventEmitterTrait;
use Pecan\Output\ConsoleOutput;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Stream\Stream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;

/**
 * A non-blocking console shell, based on the Symfony Console Shell.
 *
 * @event running
 * @event exit
 */
class Shell extends EventEmitter
{
    private $application;

    /** @var Stream */
    private $input;

    /** @var ConsoleOutput */
    private $output;

    private $isRunning = false;

    /**
     * Constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;

        $this->on('running', function(Shell $shell) {
            $shell->write([ $this->getHeader(), $this->getPrompt() ], false);
        });
    }

    /**
     * @param LoopInterface $loop
     * @throws \LogicException
     */
    public function start(LoopInterface $loop)
    {
        $this->input = new Stream(STDIN, $loop);
        $this->input->on('error', [ $this, 'handleError' ]);
        $this->input->on('data',  [ $this, 'handleInput' ]);

        $this->output = new ConsoleOutput($loop);
        $this->output->on('error', [ $this, 'handleError' ]);

        if ($this->isRunning()) {
            throw new \LogicException('The shell is already running.');
        }

        $this->isRunning = true;

        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);

        $this->on('data', function($command) {

            $ret = $this->application->run(new StringInput($command), $this->output);

            if (0 !== $ret) {
                $this->output->writeln(sprintf('<error>The command terminated with an error status (%s)</error>', $ret));
            }
        });

        $this->on('output', [ $this, 'write' ]);

        $this->emit('running', [ $this ]);
    }

    /**
     * Helper method to write messages to the Shell output.
     *
     * @param $messages
     * @param $newline
     */
    public function write($messages, $newline = true)
    {
        $this->output->write($messages, $newline);
    }

    /**
     * Exits the shell.
     *
     * @event exit
     */
    public function close($code = 0)
    {
        if (!$this->isRunning()) { return; }

        $this->handleClose($code)->then(function($code) {
            // @codeCoverageIgnoreStart
            exit($code);
            // @codeCoverageIgnoreEnd
        });

        $this->emit('exit', [ $code, $this ]);

        $this->output->end();
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->isRunning;
    }

    /**
     * This method should only be called by the Shell when an data event is emitted on the input stream.
     *
     * @param $command
     */
    public function handleInput($command)
    {
        $command = (!$command && strlen($command) == 0) ? false : rtrim($command);

        if ('exit' === $command || false === $command) {
            $this->close();
        }

        $this->emit('data', [ $command, $this ]);

        $this->output->write($this->getPrompt());
    }

    /**
     * Emits the error and exits the shell.
     *
     * @param $error
     */
    public function handleError($error)
    {
        $this->output->getErrorOutput()->write($error);
        $exitCode = $error instanceof \Exception && $error->getCode() != 0 ? $error->getCode() : 1;
        $this->close($exitCode);
    }

    /**
     * @param int $code
     * @return \React\Promise\Promise
     */
    protected function handleClose($code = 0)
    {
        $deferred = new Deferred();

        $this->output->on('end', function() use ($deferred, $code) {
            $deferred->resolve($code);
        });

        return $deferred->promise();
    }

    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    protected function getHeader()
    {
        return <<<EOF

Welcome to the <info>{$this->application->getName()}</info> shell (<comment>{$this->application->getVersion()}</comment>).

At the prompt, type <comment>help</comment> for some help,
or <comment>list</comment> to get a list of available commands.

To exit the shell, type <comment>^D</comment>.

EOF;
    }

    /**
     * Renders a prompt.
     *
     * @return string The prompt
     */
    protected function getPrompt()
    {
        // using the formatter here is required when using readline
        return $this->output->getFormatter()->format($this->application->getName().' > ');
    }

}
