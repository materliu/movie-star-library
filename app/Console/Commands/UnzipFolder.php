<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class UnzipFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unzip:folder {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '解压特定路径下的所有zip包，将其中的视频内容输出';

    private $folderNum = 0;
    private $zipExtractSuccess = 0;
    private $zipExtractFail = 0;
    private $copiedMovie = 0;
    private $movieExtArr = [];

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->movieExtArr = MovieExt::$movieExtArr;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $inputFolder = $this->argument('input');
        $outputFolder = $this->argument('output');

        $this->directoryTraversalUnzip(realpath($inputFolder));

        $this->info("共牵扯目录{$this->folderNum}个");
        $this->info("解压成功zip包{$this->zipExtractSuccess}个");
        $this->info("解压失败zip包{$this->zipExtractFail}个");

        $this->copyMovieToDirectory($outputFolder);

        $this->info("拷贝movie共{$this->copiedMovie}个");

        return true;
    }

    function emptyFolder($input) {
        if (substr($input, -6) !== '/{,.}*') {
            $input = $input . '/{,.}*';
        }

        $files = collect(glob($input, GLOB_BRACE)); // get all file names

        $files = $files->filter(function ($file) {
            return (substr($file, -1) !== '.') && (substr($file, -2) !== '..');
        });

        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            } else {
                $this->emptyFolder($file);
                rmdir($file);
            }
        }
    }

    /**
     * @param $output
     * @param string $input
     * @param bool $emptyInput
     * @return array|bool
     */
    function copyMovieToDirectory($output, $input = '/tmp/zip-movie', $emptyInput = true) {
        if (is_dir($input)) {
            // do nothing
        } else {
            return [];
        }

        $list = collect(scandir($input));

        for ($i=0,$j=count($list); $i<$j; $i++) {
            $list[$i] = $input . '/' . $list[$i];
        }

        $folders = $list->filter(function ($file) {
            return is_dir($file) && (substr($file, -1) !== '.') && (substr($file, -2) !== '..' && (substr($file, -4) !== '.tmp') && (substr($file, -12) !== '$RECYCLE.BIN'));
        });

        $movieFile = $list->filter(function ($file) {
            if (is_file($file)) {
                foreach($this->movieExtArr as $movieExt) {
                    $movieExtLength = strlen($movieExt);

                    if (strtolower(substr($file, intval('-'.$movieExtLength))) === $movieExt) {
                        return true;
                    }
                }
                return false;
            }
        });

        foreach($movieFile as $item) {
            $this->copiedMovie ++;
            $_item = explode('/', $item);

            copy($item, $output . '/' . $_item[count($_item) - 1]);
        }

        foreach($folders as $item) {
            $this->copyMovieToDirectory($output, $item, false);
        }

        if ($emptyInput) {
            $this->emptyFolder($input);
        }

        return true;
    }

    function directoryTraversalUnzip($input, $output = '/tmp/zip-movie') {

        if (is_dir($input)) {
            $this->folderNum ++;
        } else {
            return [];
        }

        $list = collect(scandir($input));

        for ($i=0,$j=count($list); $i<$j; $i++) {
            $list[$i] = $input . '/' . $list[$i];
        }

        $folders = $list->filter(function ($file) {
            return is_dir($file) && (substr($file, -1) !== '.') && (substr($file, -2) !== '..' && (substr($file, -4) !== '.tmp') && (substr($file, -12) !== '$RECYCLE.BIN'));
        });

        $zipFile = $list->filter(function ($file) {
            return is_file($file) && substr($file, -4) == '.zip';
        });

        $zip = new ZipArchive;

        if (!file_exists($output)) {
            mkdir($output);
        }

        foreach($zipFile as $item) {
            if ($zip->open($item) === TRUE) {
                $zip->extractTo($output);
                $zip->close();
                $this->zipExtractSuccess ++;
            } else {
                $this->zipExtractFail ++;
            }
        }

        foreach($folders as $item) {
            $this->directoryTraversalUnzip($item, $output);
        }

        return true;
    }

}
