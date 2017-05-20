<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class UnzipMovie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unzip:movie {input} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '解压特定路径下的所有zip包，将其中的视频内容输出, 使用此工具的前提需本地安装有unar';

    private $folderNum = 0;
    private $zipExtractSuccess = 0;
    private $zipExtractFail = 0;
    private $copiedFile = 0;
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

        $this->info("拷贝movie共{$this->copiedFile}个");

        return true;
    }

    function filterWhiteListFiles($files) {
        $whiteListStr = env('FILE_WHITE_LIST', '.,..');
        $whiteList = explode(',', $whiteListStr);
        $_files = collect($files);

        return ($_files->filter(function ($folder) use ($whiteList) {
            foreach($whiteList as $whiteFile) {
                if (strtolower(substr($folder, intval('-' . strlen($whiteFile)))) === $whiteFile) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * @param $files
     * @param $prefix
     * @return static
     * 为路径添加前置路径，比如获取 /tmp的子文件为 1.zip, 将其拼为 /tmp/1.zip
     */
    function prefixPathInfo($files, $prefix) {
        $files = collect($files);

        return ($files->map(function ($value) use ($prefix) {
            return $prefix . '/' . $value;
        }));
    }

    /**
     * @param $files
     * @return static
     * 获取视频文件
     */
    function getMovieFiles($files) {
        $files = collect($files);

        return ($files->filter(function ($file) {
            if (is_file($file)) {
                foreach($this->movieExtArr as $movieExt) {
                    $movieExtLength = strlen($movieExt);

                    if (strtolower(substr($file, intval('-'.$movieExtLength))) === $movieExt) {
                        return true;
                    }
                }
                return false;
            }
        }));
    }

    function emptyFolder($input) {
        if (substr($input, -6) !== '/{,.}*') {
            $input = $input . '/{,.}*';
        }

        $files = glob($input, GLOB_BRACE); // get all file names

        $files = $this->filterWhiteListFiles($files);

        $files->each(function($file) {
            if(is_file($file)) {
                unlink($file); // delete file
            } else {
                $this->emptyFolder($file);
                rmdir($file);
            }
        });
    }

    /**
     * @param $name
     * @return mixed
     * 尝试修复在zip解压过程中乱码的文件名, 判断是否是GBK编码的
     */
    function isFileNameGBK($name) {
        $badCodeStr = '╟Θ┬┬╝╥└∩┼╛┼╛╓▒▓Ñ╕°┤≤╗∩╨└╔═├└┼«│ñ╡├╒µ╩╟▓╗┤φ╠∞╜≥─│╨ú╤╒╓╡╕▀╔φ▓─║├╡─╝½╞╖╧╡╗¿▒│╫┼─╨╙╤╙δ═┴║└╟Θ╚╦╛╞╡Ω┤≤╒╜,╛°╢╘╡─╝½╞╖┼«╔±╕╔╡─2╕÷┤≤─╠╫╙┬╥╗╬,╗╣▓╗═ú╡─╦╡ú║╧δ╥¬úí╤≤└╧═Γ├╫╕Γ╫ε╨┬┴≈│÷╢½▌╕│ñ╞╜╛╞╡Ω╦½╖╔┴╜╕÷║≤╣ñ│º┤≥╣ñ├├░í╟ß╡πú¼┤≤║┌î┼╖█╦┐▓╗╢«╡├┴»╧π╧º╙±╕≈╓╓╫╦╩╞▒¼▓σ┼«╔±╦╝╚≡í╛╟╪╧╚╔·í┐╡┌╬σ▓┐úí╩╫╖ó─ú╠╪τ≈τ≈╡─╡⌡┤°╦┐═αí╛╦┐═α├└═╚╧╡┴╨í┐╝½╞╖║┌╦┐├└═╚┼√╝τ╖ó┼«╔±╓≈╠Γ▒÷╣▌┬⌠┼¬╖τ╔º ┐Φ╧┬╔ε║φ╣ⁿî┼ ─╨╓≈╘┘╠≥├└▒½ ║≤╚δ╢Ñ▓┘│ñ═╚┼«╔±';

        $badCode = str_split($badCodeStr);
        $badCode = array_unique($badCode);
        $badCode = collect($badCode);
        $badFlag = false;

        $badCode->each(function ($value) use ($name, &$badFlag) {
            $name = collect(str_split($name));

            $counter = 0;

            foreach($name as $character) {
                if ($character == $value) {
                    $counter ++;
                }

                if ($counter > 5) {
                    $badFlag = true;
                    return false;
                }
            }
        });

        return $badFlag;
    }

    /**
     * @param $files
     * @param $output
     * @return bool
     * 直接拷贝文件
     */
    function copyFile($files, $output) {
        $files->each(function ($value) use ($output) {
            $this->copiedFile ++;
            $_item = explode('/', $value);
            $filename = $_item[count($_item) - 1];
            copy($value, $output . '/' . $filename);
        });
        return true;
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
            return false;
        }

        $list = scandir($input);
        $list = $this->prefixPathInfo($list, $input);
        $folders = $this->filterWhiteListFiles($list);

        $movieFiles = $this->getMovieFiles($list);
        $this->copyFile($movieFiles, $output);

        $folders->each(function ($value) use ($output) {
            $this->copyMovieToDirectory($output, $value, false);
        });

        if ($emptyInput) {
            $this->emptyFolder($input);
        }

        return true;
    }

    function directoryTraversalUnzip($input, $output = '/tmp/zip-movie') {

        if (is_dir($input)) {
            $this->folderNum ++;
        } else {
            return false;
        }

        $list = scandir($input);
        $list = $this->prefixPathInfo($list, $input);
        $folders = $this->filterWhiteListFiles($list);

        $zipFiles = $list->filter(function ($file) {
            return is_file($file) && substr($file, -4) == '.zip';
        });

        if (!file_exists($output)) {
            mkdir($output);
        }

        $zip = new ZipArchive;
        $zipFiles->each(function ($item) use ($zip, $output){
            if ($zip->open($item) === TRUE) {

                $filenameGBK = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if($this->isFileNameGBK($zip->getNameIndex($i))) {
                        $filenameGBK = true;
                        break;
                    }
                }

                if ($filenameGBK) {
                    exec("unar -encoding GBK -output-directory $output -f $item");
                } else {
                    $zip->extractTo($output);
                }

                $zip->close();
                $this->zipExtractSuccess ++;
            } else {
                $this->zipExtractFail ++;
            }
        });

        $folders->each(function ($item) use ($output) {
            $this->directoryTraversalUnzip($item, $output);
        });

        return true;
    }

}
