<?php

namespace Pecan;

use Evenement\EventEmitterTrait;
use Pecan\Console\Output\PecanOutput;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;

/**
 * Pecan\Shell
 *
 * A non-blocking console shell for ReactPHP, based on the Symfony Console Component.
 *
 * @event running
 * @event data
 * @event error
 * @event close
 * @see \Symfony\Component\Console\Shell
 */
class Shell extends Drupe
{
    private $application;
    private $loop;
    private $running = false;

    /**
     * @param Application $application
     * @param LoopInterface $loop
     */
    public function __construct(Application $application, LoopInterface $loop = null)
    {
        $application->setAutoExit(false);
        $application->setCatchExceptions(true);

        $this->application = $application;

        //$this->on('error',  [ $this, 'errorHandler' ]);

        $this->once('running', function() {
            $this->readline->setPrompt($this->getPrompt());
            $this->console()->log([ $this->getHeader(), true ]);
            $this->prompt();
        });

        $this->loop = $loop ?: Factory::create();
    }

    /**
     * @param float $interval
     * @throws \LogicException
     */
    public function run($interval = 0.5)
    {
        if ($this->running) {
            throw new \LogicException('The shell is already running.');
        }

        $this->running = true;

        parent::start($this->loop, $interval);

        // Remove parent listener to override emit for 'data'
        $this->readline->removeAllListeners('line');

        $this->readline->on('line', [ $this, 'inputHandler' ]);

        $this->loop->run();
    }

    /**
     * Exits the shell.
     *
     * @param integer $exitCode
     * @return $this;
     */
    public function close($exitCode = 0)
    {
        if (!$this->running) { return $this; }

        return parent::close($exitCode);
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Writes the error to STDERR and closes the shell.
     * This method should only be called by internal callbacks.
     *
     * @param $error
     */
    public function errorHandler($error)
    {
        $this->console()->error($error);
        $exitCode = $error instanceof \Exception && $error->getCode() != 0 ? $error->getCode() : 1;
        $this->close($exitCode);
    }

    /**
     * This method should only be called by internal callbacks.
     *
     * @param $command
     */
    public function inputHandler($command) {

        $command = (!$command && strlen($command) == 0) ? false : rtrim($command);

        if ('exit' === $command || false === $command) {
            $this->close();
        } else {
            $this->emit('data', [ $command, $this ]);
        }

        $ret = $this->application->run(new StringInput($command), $this->output);

        if (0 !== $ret) {
            $this->console()->error([ sprintf('<error>The command terminated with an error status (%s)</error>', $ret), true ]);
        }

        $this->readline->prompt();
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

To exit the shell, type <comment>exit</comment> or <comment>^D</comment>.

EOF;
    }

    /**
     * Renders a prompt.
     *
     * @return string The prompt
     */
    protected function getPrompt()
    {
        return $this->application->getName() . ' > ';
    }

}
