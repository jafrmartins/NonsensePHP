<?php

namespace Saubi\NonsensePHP\Tests;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'nonsense.php';;

use PHPUnit\Framework\TestCase;

final class NonsenseTest extends TestCase
{

    protected $fixturesFolder = __DIR__ .  DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
    protected $binFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'nonsense.php';
    protected $outFolder = __DIR__ . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR;
    protected $message = 'test message for test purposes';

    public function testFunctionShouldGenerateEncodedExecutableText() {

        $original_source = "echo 1+1";
        $source_ops = get_random_obfuscate_ops(3);
        $program = compile_obfuscated_string($source_ops[0], $original_source);
        $executable = compile_runnable_obfuscated_string($source_ops[1], $program, true, false, true);

        $result = eval("return $executable;");
        $this->assertSame($result, $original_source);


    }

    public function testCLIShouldGenerateEencodedFile() {

        $outfile = $this->outFolder.DIRECTORY_SEPARATOR.'hello.php';
        $this->assertFileExists($outfile, "source not generated");

        $matches = 0;
        preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', file_get_contents($outfile), $matches);
        $this->assertSame(sizeof(explode(" ", $this->message))*2, sizeof($matches[0]));
        
    }
    
    public function testCLIShouldGenerateExecutableFile() {

        $outfile = $this->outFolder.DIRECTORY_SEPARATOR.'hello.php';
        $this->assertFileExists($outfile, "source not generated");

        $output = shell_exec('php ' . realpath($outfile));
        print($output);

    }

    public function testCLIShouldEncodeAllFilesInFolder() {

    }

    protected function setUp() : void
    {

        $options = [
            '--src="'.$this->fixturesFolder.'"',
            '--dest="'.$this->outFolder.'"',
            '--msg="'.$this->message.'"'
        ];
        var_dump('php ' .$this->binFile . " obfuscate " . implode(" ", $options));
        system('php ' .$this->binFile . " obfuscate " . implode(" ", $options));
    }

    protected function tearDown() : void
    {
        //system('rm -rf  ' .$this->outFolder);
    }

}