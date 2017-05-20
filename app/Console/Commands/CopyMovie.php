<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CopyMovie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:movie {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '遍历某一目录下的视频文件全部拷贝至另一目录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $unzipMovie = new UnzipMovie();
        $inputFolder = $this->argument('input');
        $outputFolder = $this->argument('output');

        return $unzipMovie->copyMovieToDirectory($outputFolder, $inputFolder, false);
    }
}
