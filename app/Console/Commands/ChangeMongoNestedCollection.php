<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChangeMongoNestedCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changemongo:nestedcollection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '遍历Mongo表中，找到命中特定值的某个嵌套数组内的值，并对其进行修改';

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
        foreach ($this->collections as $collection) {
            foreach ($this->accountMap as $mongoID => $ldapName) {
                $queryArr = [
                    'tasks'=> [
                        '$elemMatch' => [
                            'collection' => [
                                '$elemMatch' => [
                                    '$in' => [
                                        $mongoID
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $setArr1 = [
                    '$set'=>[
                        'tasks.0.collection.$'=> $ldapName
                    ]
                ];
                $setArr2 = [
                    '$set'=>[
                        'tasks.$.collection.0'=> $ldapName
                    ]
                ];

                $r = $collection->updateMany(
                    $queryArr,
                    $setArr1
                );

                $this->modifiedCount += $r->getModifiedCount();

                $r = $collection->updateMany(
                    $queryArr,
                    $setArr2
                );

                $this->modifiedCount += $r->getModifiedCount();
            }
        }


        $this->info("总共完成修改记录 $this->modifiedCount");
    }
}
