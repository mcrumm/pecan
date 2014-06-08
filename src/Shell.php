<?php

namespace Pecan;

;
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

        $this->on('error',  function ($error) {
            $this->console->error($error);
            $exitCode = $error instanceof \Exception && $error->getCode() != 0 ? $error->getCode() : 1;
            $this->close($exitCode);
        });

        $this->once('running', function(Drupe $shell) {
            $this->console = $this->readline->console();
            $this->console->getOutput()->writeln($this->getHeader());
            $shell->setPrompt($this->getPrompt())->prompt();
        });

        $this->loop = $loop ?: Factory::create();

        parent::__construct(getenv('HOME').'/.history_'.$application->getName());
    }

    /**
     * @param float $interval
     * @throws \LogicException
     */
    public function run($interval = 0.001)
    {
        if ($this->running) {
            throw new \LogicException('The shell is already running.');
        }

        $this->running = true;

        parent::start($this->loop, $interval);

        $inputHandler = function ($command) {

            $command = (!$command && strlen($command) == 0) ? false : rtrim($command);

            if ('exit' === $command || false === $command) {
                $this->close();
                return;
            } else {
                $this->emit('data', [ $command, $this ]);
            }

            $ret = $this->application->run(new StringInput($command), $this->console->getOutput());

            if (0 !== $ret) {
                $this->console->error([ sprintf('<error>The command terminated with an error status (%s)</error>', $ret), true ]);
            } else {
                $this->readline->prompt();
            }
        };

        // Remove parent listener to override emit for 'data'
        $this->readline->removeAllListeners('line');
        $this->readline->on('line', $inputHandler);

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
     * @return Console\Output\ConsoleOutputInterface
     */
    public function getOutput()
    {
        return $this->console->getOutput();
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
