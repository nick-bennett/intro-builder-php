<?php

require_once('PasswordGenerator.php');

echo "\nBasic use:\n";

echo "\n" . PasswordGenerator::builder()->build()->generate(12) . "\n";

echo "\nExclude all punctuation:\n";

$generator = PasswordGenerator::builder()
    ->includePunctuation(false)
    ->build();

echo "\n" . $generator->generate(12) . "\n";
 
echo "\nExclude all punctuation and digits; require at least 1 upper-case and 1" 
    . "\nlower-case character; generate 10 passwords, 16 characters each:\n";

$generator = PasswordGenerator::builder()
    ->includeDigit(false)
    ->includePunctuation(false)
    ->requireUpper(1)
    ->requireLower(1)
    ->build();

echo "\n";
print_r($generator->generate(16, 10));
