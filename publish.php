#!/usr/bin/env php
<?php

$publisher = new Publisher($argv);

$publisher->initialize()
    ->generate()
    ->publish();

/**
 * Class Publisher
 */
class Publisher
{

    /**
     * @var bool
     */
    protected $dryRun = FALSE;

    /**
     * @var array
     */
    protected $commands = array();

    /**
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {

        if (!empty($arguments[1]) && $arguments[1] === '--dry') {
            $this->dryRun = TRUE;
        }
    }

    /**
     * Initialize the environment.
     *
     * @return $this
     */
    public function initialize()
    {
        $webDirectory = $this->getWebDirectory();

        // Initialize web root directory
        if (!is_dir($webDirectory)) {

            $this->commands[] = 'git clone ' . $this->getGitRemote() . ' ' . $webDirectory;
            $this->commands[] = sprintf('cd %s; git checkout gh-pages', $webDirectory);
        }

        return $this;
    }

    /**
     * Generate static web pages.
     *
     * @return $this
     */
    public function generate()
    {
        // Create redirection to "en".
        // @todo add language detection?
        $this->createRedirectionPage();

        // Generate for English
        $this->commands[] = sprintf('cd %s/en; sculpin generate --url=/en --env=prod', $this->getSourceDirectory());
        $this->commands[] = sprintf('mv %s/en/output_prod %s/en', $this->getSourceDirectory(), $this->getWebDirectory());

        return $this;
    }

    /**
     * Publish the web pages.
     *
     * @return void
     */
    public function publish()
    {
        $this->execute($this->commands);
    }

    /**
     * @return string
     */
    protected function getWebDirectory()
    {
        $parts = explode('/', __DIR__);
        array_pop($parts);
        return implode('/', $parts) . '/web';
    }

    /**
     * @return string
     */
    protected function getSourceDirectory()
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    protected function getGitRemote()
    {
        // @todo find a more clever way here...
        return 'git@github.com:fudriot/fabarea.tk.git';
    }

    /**
     * Execute the commands
     *
     * @param mixed $commands
     * @return array
     */
    protected function execute($commands)
    {

        if (is_string($commands)) {
            $commands = array($commands);
        }

        if ($this->isDryRun()) {
            $this->log($commands);
            return array();
        }

        $result = array();
        foreach ($commands as $command) {
            exec($command, $result);
        }
        return $result;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function log($message = '')
    {
        if (is_array($message)) {
            foreach ($message as $line) {
                print trim($line) . PHP_EOL;
            }
        } else {
            print trim($message);
        }
    }

    /**
     * @param string $message
     * @return void
     */
    protected function createRedirectionPage()
    {
        if (!$this->dryRun) {

            $html = <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0; url=/en" />
</head>
<body>
</body>
</html>
EOF;

            file_put_contents($this->getWebDirectory() . '/index.html', $html);
        }
    }

    /**
     * Tells whether the dry run flag is found.
     *
     * @return bool
     */
    protected function isDryRun()
    {
        return $this->dryRun;
    }
}