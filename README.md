# NonsensePHP

A Simple PHP Obfuscator

## Usage

```bash
USAGE nonsense.php obfuscate <options>

description: Generates obfuscated files in destination folder
example: php nonsense.php obfuscate --src="/path/to/files/" --dest="/path/to/out/files/" --msg="please dont touch the files" --iter=3
```

## Example 

```bash
./src/bin/nonsense.php obfuscate --src='./test/fixtures'
```

OR

```bash
./vendor/bin/nonsense.php obfuscate --src='./test/fixtures'
```

## Tests

```bash
./vendor/bin/phpunit test
```