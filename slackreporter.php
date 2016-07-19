<?php

require_once __DIR__.'/vendor/autoload.php';

if (!defined('_PS_VERSION_'))
	exit;

class SlackReporter extends Module {
    private $slackClient;

	public function __construct() {
		$this->name = 'slackreporter';
		$this->tab = 'others';
		$this->version = '0.0.1';
		$this->author = 'Ecomputer';
		$this->need_instance = 0;
		$this->ps_versions_compilancy = array('min' => '1.5', 'max' => '1.6');
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Slack reporter');
		$this->description = $this->l('Your shop should never throw errors in production. Be aware of PHP errors via Slack.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		/*if (!Configuration::get('MYMODULE_NAME'))  
    		$this->warning = $this->l('No name provided.'); */

    }

    public function install()
    {
        return parent::install() && $this->registerHook('actionDispatcher');
    }

    public function hookActionDispatcher() {
        $settings = [
            'username' => 'My Shop',
            //'icon'     => ''
            //'channel' => '#errors',
            //'link_names' => true
        ];

        $this->slackClient = new Maknz\Slack\Client('https://hooks.slack.com/services/.../.../...', $settings);

        set_error_handler([$this, "errorHandler"]);
        set_exception_handler([$this, "exceptionHandler"]);
        register_shutdown_function([$this, "checkForFatal"]);
    }


    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return;
        }
        $errorMsg = sprintf("ERROR - [%s] %s:%d - %s", $errno, $errfile, $errline, $errstr);

        $this->slackClient->send($errorMsg);
    }
    public function exceptionHandler(\Exception $e)
    {
        if (error_reporting() === 0) {
            return;
        }
        $errorMsg = sprintf(
            "EXCEPTION - [%s] %s:%d - %s\nStacktrace:\n\n%s",
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getMessage(),
            $e->getTraceAsString()
        );
        $this->slack->send($errorMsg, $this->exceptionChannel, $this->username);
    }

    public function checkForFatal() {
        $error = error_get_last();
        if ($error["type"] == E_ERROR)
            $this->slackClient->send(sprintf("ERROR - [%s] %s:%d - %s",$error["type"], $error["message"], $error["file"], $error["line"]));
    }


}
