<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use PhpMqtt\Client\MQTTClient;

class daemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daemon:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Daemon';

    /**
     * The daemon MQTT ID
     *
     * @var string
     */
    protected $mqttClientId = "daemon-test-eficia";

     /**
     * The daemon MQTT URL
     *
     * @var string
     */
    private $mqttUrl;

    /**
     * The daemon MQTT PORT
     *
     * @var string
     */
     private $mqttPort;

    /**
     * The alertsStack
     *
     * @var array
     */
     private $alertsStack;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->mqttUrl = config('valentin.mqtt.url');
        $this->mqttPort = config('valentin.mqtt.port');
        $this->alertsStack = array();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mqtt = new MQTTClient($this->mqttUrl, $this->mqttPort, $this->mqttClientId);
        $mqtt->connect();
        $mqtt->subscribe('eficia/alerts', function ($topic, $message) {
            $messagesDate = Carbon::now()->valueOf() - 60000;
            foreach ($this->alertsStack as $key => $alert) {
                if ($key < $messagesDate) {
                    $this->alertsStack[$key] = null;
                    unset($this->alertsStack[$key]);
                }
            }
            $this->alertsStack[$messagesDate] = $message;
            
            if (count($this->alertsStack) >= 3) {{}
                Log::error(count($this->alertsStack) . " alertes ont étaient reçu sur la dernière minutes");
            }
        }, 0);
        $mqtt->loop(true);
    }
}
