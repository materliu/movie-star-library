<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class UnzipFolderTemp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:folder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '解压特定路径下的所有zip包，将其中的视频内容输出';

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

    function correctFileName($name) {
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

        if ($badFlag) {
            echo $name;
            echo "\n";
            echo iconv('UTF-8', 'cp866//IGNORE', $name);
            echo "\n";
            echo iconv('cp866', 'UTF-8//IGNORE', $name);
            echo "\n";
            echo iconv('cp866', 'GBK//IGNORE', $name);
            echo "\n";
            echo mb_convert_encoding($name, "GBK");
            echo "\n";
        }

        return $badFlag;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = realpath('/Users/mater/Downloads/40.zip/40.zip');

        $zip = new ZipArchive;
        if ($zip->open($path) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                if($this->correctFileName($filename)) {
                    exec("unar -encoding GBK -output-directory /tmp/testencode -f $path");
                    break;
                }

            }
            $zip->close();
        }
    }
}
