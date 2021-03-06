<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChangeMongoRootValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changemongo:rootvalue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '直接遍历修改Mongo数据库中命中某一条件的根节点下的字段并修改其值';

    private $modifiedCount = 0;

    private $accountMap = [
        '58cf4667c3666e025600e0d2' => 'fushuxian'
    ];

    private $docMap = [
        'elab_user_action',
        'elab_user_action_test',
        'elab_user_action_yanbao',
        'elab_user_action_yanbao_test'
    ];


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Client $mongo)
    {
        parent::__construct();
        $host = env('MONGO_HOST_EFUNDS');
        $port = env('MONGO_PORT_EFUNDS');
        $user = env('MONGO_USERNAME_EFUNDS');
        $password = env('MONGO_PASSWORD_EFUNDS');
        $dbname = env('MONGO_DB_EFUNDS');
        $this->mongoClient = new \MongoDB\Client("mongodb://$user:$password@$host:$port/$dbname");
        $this->collections = [];

        foreach($this->docMap as $doc) {
            $this->collections[] = $this->mongoClient->selectCollection($dbname, $doc);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach($this->collections as $collection) {
            foreach ($this->accountMap as $mongoID => $ldapName) {
                $queryArr = [
                    'uid'=> $mongoID
                ];

                $setArr = [
                    '$set'=>[
                        'uid'=> $ldapName
                    ]
                ];

                $r = $collection->updateMany(
                    $queryArr,
                    $setArr
                );

                $this->modifiedCount += $r->getModifiedCount();
            }
        }

        $this->info("总共完成修改记录 $this->modifiedCount");
    }
}
