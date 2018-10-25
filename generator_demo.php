<?php

require_once('PasswordGenerator.php');

echo "\nGenerate single password with default options & length: ";

// Build a generator with the default options.
$generator = PasswordGenerator::builder()->build();

// Generate a single password with the default length.
echo $generator->generate() . "\n";


echo "\nExclude all punctuation from 15-character password: ";

// Build a generator with punctuation excluded from the pool.
$generator = PasswordGenerator::builder()
    ->includePunctuation(false)
    ->build();

// Generate a single password of length 15.
echo $generator->generate(15) . "\n";


echo "\nExclude all punctuation and digits; require at least 1 upper-case and 1"
    . "\nlower-case character; generate 10 passwords, 16 characters each:\n";

/*
 * Build a generator with digits & punctuation excluded, requiring at least 1
 * upper- & 1 lower-case letter.
 */
$generator = PasswordGenerator::builder()
    ->includeDigit(false)
    ->includePunctuation(false)
    ->requireUpper(1)
    ->requireLower(1)
    ->build();

// Generate 10 passwords of 16 characters each.
echo print_r($generator->generate(16, 10), true) . "\n";
