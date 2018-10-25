<?php

require_once('PasswordGenerator.php');

echo "\nGenerate single password with default options & length: ";

echo PasswordGenerator::builder()->build()->generate(), "\n";

echo "\nExclude all punctuation from 15-character password: ";

$generator = PasswordGenerator::builder()
    ->includePunctuation(false)
    ->build();

echo $generator->generate(15), "\n";

echo "\nExclude all punctuation and digits; require at least 1 upper-case and 1"
    . "\nlower-case character; generate 10 passwords, 16 characters each:\n";

$generator = PasswordGenerator::builder()
    ->includeDigit(false)
    ->includePunctuation(false)
    ->requireUpper(1)
    ->requireLower(1)
    ->build();

echo print_r($generator->generate(16, 10), true), "\n";
