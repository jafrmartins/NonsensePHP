#!/usr/bin/env php

<?php

define('SIMPLE_OBFUSCATOR_COMMANDS', [
    "obfuscate" => [
        'description' => 'Generates obfuscated files in destination folder',
        'example' => 'php simpleobfuscate.php obfuscate --src="/path/to/files/" --dest="/path/to/out/files/" --msg="please dont touch the files" --iter=3'
    ],
]);

define('SIMPLE_OBFUSCATOR_CYPHERS', [

    'b64' => ['base64_decode', 'base64_encode'],
    'b16' => ['hex2bin', 'bin2hex'], 
    //'a85' => ['ascii85_decode', 'ascii85_encode']
    'rot13' => ['str_rot13', 'str_rot13'],
    'rev' => ['strrev', 'strrev']

]);

define('SIMPLE_OBFUSCATOR_ENCRYPTION', [

    'AES' => [
        function($data, $password) { openssl_decrypt($data, "AES-128-ECB", $password); }, 
        function($data, $password) { openssl_encrypt($data, "AES-128-ECB", $password); },
    ],

]);

function parse_argv(&$argv) {

    if( count($argv) < 1) { help(); }

    $command = isset($argv[1]) ? $argv[1] : null;
    if(!isset(SIMPLE_OBFUSCATOR_COMMANDS[$command])) { help(); }
    
    $args = [];
    for ($i = 2; $i < count($argv); $i++) {
        if (preg_match('/^--([^=]+)=(.*)/', $argv[$i], $match)) {
            $args[$match[1]] = $match[2];
        } else if(preg_match('/^--(.*)/', $argv[$i], $match)) {
            $args[$match[1]] = true;
        }
    }
    
    if(!count(array_keys($args))) {
        help($command);
    }
    
    return [$command, $args];

}


function recursive_glob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge(
            [],
            ...[$files, recursive_glob($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags)]
        );
    }
    array_multisort($files);
    return $files;
}

function help($command=null, $commands=SIMPLE_OBFUSCATOR_COMMANDS) {

    if($command && isset($commands[$command])) {
        $usage = PHP_EOL."USAGE simpleobfuscate.php $command <options>".PHP_EOL.PHP_EOL;
        foreach($commands[$command] as $k => $v) {
            $usage .= $k.": $v".PHP_EOL;
        } $usage .= PHP_EOL.PHP_EOL; die($usage);
    } else {
        $usage = PHP_EOL."USAGE: simpleobfuscate.php <command> <options>".PHP_EOL.PHP_EOL;
        foreach($commands as $command => $def) {
            $usage .= "\t".$command.PHP_EOL.PHP_EOL;
            foreach($commands[$command] as $k => $v) {
                $usage .= "\t\t".$k.": $v".PHP_EOL;
            }

            $usage .= PHP_EOL.PHP_EOL;
        } die($usage);
    }

}

function get_random_obfuscate_ops($max_ops, $bl=null) {

    $stack = [];
    $keys = array_keys(SIMPLE_OBFUSCATOR_CYPHERS);
    
    if(!empty($bl)) {

        $wl_keys = [];


        foreach($keys as $i => $wlkey) {
            if(!in_array($wlkey, $bl)) {
                $wl_keys[] = $wlkey;
            }
        }

        while(sizeof($stack)-1 < $max_ops) {
            $cypher = random_int(0, sizeof($wl_keys)-1);
            $stack[] = $wl_keys[$cypher];
        }

    } else {

        for($i = 0; $i < $max_ops; $i++) {

            $cypher = random_int(0, sizeof($keys)-1);
            $stack[] = $keys[$cypher];
    
        }

    }

    $encode = [];
    $decode = [];
    
    foreach($stack as $i => $cypher) {
        $encode[] = [$cypher, SIMPLE_OBFUSCATOR_CYPHERS[$cypher][1]];
    }

    foreach(array_reverse($stack) as $i => $cypher) {
        $decode[] = [$cypher, SIMPLE_OBFUSCATOR_CYPHERS[$cypher][0]];
    }

    return [$encode, $decode];

}

function copy_files($args, $files) {
    
    foreach($files as $i => $filepath) {

        $abspath = realpath($filepath);
        $relpath = str_replace($args['src'], '', $abspath);
        $destpath = $args['dest'].$relpath;
        
        @mkdir(dirname($destpath), 0777, true);
        @copy($abspath, $destpath);

    }

}

function compile_obfuscated_string($encode_keys, $source_string) {

    foreach($encode_keys as $i => $op) {
        $source_string = call_user_func($op[1], $source_string);
    } return $source_string;

}

function compile_runnable_obfuscated_string($decode_keys, $obfuscated_string, $quote=true, $runtime=false, $ops_only=false) {


    $decoded_source = $obfuscated_string;
    if($quote) $decoded_source = "\"".$obfuscated_string."\"";
    foreach($decode_keys as $i => $op) {
        $decoded_source = $op[1]."(".$decoded_source.")";
    }

    return $ops_only ? $decoded_source : "eval($decoded_source);";


}

function compile_encoded_runtime_and_source_and_message($msg, $source, $max_iter){

    $call_stack = [];

    $program_ops = get_random_obfuscate_ops($max_iter);
    $program = compile_obfuscated_string($program_ops[0], $source);
    
    foreach($msg as $i => $var) {

        if($i == sizeof($msg)-1) {

            $content = $program;

        } else {

            $content = substr($program, 0, random_int(0, strlen($program)-1));

        }

        $pos = strpos($program, $content);
        if ($pos !== false) {
            $program = substr_replace($program, '', $pos, strlen($content));
        }

        $call_stack[] = [$var, $content];

    }

    $call_vars = [];
    $var_ops = get_random_obfuscate_ops(sizeof($msg));
    $var_encode = $var_ops[0];
    $var_decode = array_reverse($var_ops[1]);

    $program = "".PHP_EOL;
    foreach($call_stack as $i => $arr) {

        $program .= "$$arr[0] = '".call_user_func($var_encode[$i][1], $arr[1])."';".PHP_EOL;
        $call_vars[] = $var_decode[$i][1]."($$arr[0])";

    }

    $program .= compile_runnable_obfuscated_string($program_ops[1], implode(".", $call_vars), false);
    return $program;
}

function obfuscate_string($original_source, $max_iter, $msg=null, $as_file=false) {

    $source = $original_source;
    
    // COMPILE SOURCE CODE

    $source_ops = get_random_obfuscate_ops($max_iter, ['rot13', 'rev']);
    $source_encode = $source_ops[0];
    $source_decode = $source_ops[1];

    $encoded_source = compile_obfuscated_string($source_encode, $source);
    $decoded_source = compile_runnable_obfuscated_string($source_decode, $encoded_source);

    $program = "$decoded_source";

    $encoded_program = compile_encoded_runtime_and_source_and_message($msg, $program, $max_iter);
    return $encoded_program;
    

}

function run_command_obfuscate($args) {

    $files = recursive_glob($args['src']."*.php");
    copy_files($args, $files);

    $sources = recursive_glob($args['dest']."*.php");
    foreach($sources as $i => $sourcepath) {
        $source = file_get_contents($sourcepath);
        $pos = strpos($source, "<?php");
        if ($pos !== false) {
            $source = substr_replace($source, '', $pos, strlen('<?php'));
        }
        $source = obfuscate_string($source, $args['iter'], $args['msg']);
        file_put_contents($sourcepath, "<?php ".PHP_EOL.$source);
    }

}

function run_command($argv) {
    
    list($command, $args) = parse_argv($argv);

    switch($command) {

        case "obfuscate":

            if(isset($args['src']) || isset($args['dest'])) {

                if(isset($args['src']) && !is_dir($args['src'])) {
                    die('please specify --src="/path/to/files/"'.PHP_EOL.PHP_EOL);
                }

                $args['src'] = realpath(rtrim($args['src'], DIRECTORY_SEPARATOR)).DIRECTORY_SEPARATOR;

                if(!isset($args['dest'])) {

                    $args['dest'] = getcwd().DIRECTORY_SEPARATOR."dist";
                    @mkdir(getcwd().DIRECTORY_SEPARATOR."dist", 0777, true);

                } else {
                    @mkdir($args['dest'], 0777, true);
                }

                if(!isset($args['dest']) || !is_dir($args['dest'])) {
                    die('please specify --dest="/path/to/out/files/"'.PHP_EOL.PHP_EOL);
                }

                $args['dest'] = realpath(rtrim($args['dest'], DIRECTORY_SEPARATOR)).DIRECTORY_SEPARATOR;

                if(!isset($args['msg'])) {
                    $args['msg'] = "please dont touch the files";
                } else {
                    $args['msg'] = strval($args['msg']);
                }

                $args['msg'] = explode(" ", $args['msg']);
                if(sizeof($args['msg']) < 3) {
                    die('please specify --msg="greater than three words"'.PHP_EOL.PHP_EOL);
                }

                if(!isset($args['iter'])) {
                    $args['iter'] = 3;
                } else {
                    $args['iter'] = intval($args['iter']);
                }

                if(!is_numeric($args['iter'])) {
                    die('please specify a numeric value for --iter'.PHP_EOL.PHP_EOL);
                }

                run_command_obfuscate($args);

            } else { help("zip"); } break;

    }


} 
if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
{
    run_command($argv);
}