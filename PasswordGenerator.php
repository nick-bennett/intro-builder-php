<?php

/**
 * Abstract superclass of random password generators. There are actually no
 * abstract methods in this class; it is deliberately abstract so that it can 
 * only be constructed (as implemented here) by the Builder pattern, using the 
 * {@link PasswordGeneratorBuilder}.
 */
abstract class PasswordGenerator
{
    /** Upper-case letters in Basic Latin Unicode block. */
    public const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /** Lower-case letters in Basic Latin Unicode block. */
    public const LOWER = 'abcdefghijklmnopqrstuvwxyz';
    /** Digit characters in Basic Latin Unicode block. */
    public const DIGIT = '0123456789';
    /** Printable/visible punctuation/symbols in Basic Latin Unicode block. */
    public const SYMBOL = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

    private $pool;

    /**
     * Initializes the pool of characters from which passwords are drawn.
     * @param array $pool characters to draw from for entire password.
     */
    protected function __construct(array $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Instantiates and returns an instance of an anonymous subclass of {@link
     * PasswordGeneratorBuilder}, for use as a builder object in building an
     * instance of {@link PasswordGenerator}.
     *
     * @return PasswordGeneratorBuilder   builder object that can build a
     *                                    {@link PasswordGenerator}.
     */
    public static function builder(): PasswordGeneratorBuilder
    {
        return new class extends PasswordGeneratorBuilder
        {
            public function __construct()
            {
                parent::__construct();
            }
        };
    }

    /**
     * Returns the pool of characters available to this generator.
     *
     * @return array    characters in pool/
     */
    protected function getPool(): array
    {
        return $this->pool;
    }

    /**
     * Generates one or more passwords, as specified. If more than one
     * password is requested, an array of strings is returned; otherwise, a
     * string is returned.
     *
     * @param int $length number of characters in password.
     * @param int $count number of passwords to generate.
     * @return mixed          generated password, or array of passwords.
     * @throws Exception      if no CSPRNG stream of bits is available.
     */
    public function generate(int $length = 12, int $count = 1)
    {
        $passwords = [];
        for ($i = 0; $i < $count; $i++) {
            $passwords[] = $this->generateOne($length);
        }
        return ($count == 1) ? $passwords[0] : $passwords;
    }

    /**
     * Generates a single, simple password (possibly partial, and without
     * advanced policy processing) from the specified pool of characters.
     *
     * @param array $pool characters to draw from for this password.
     * @param int $length number of characters in password.
     * @return string         generated password.
     * @throws Exception      if no CSPRNG stream of bits is available.
     */
    protected function generateFromPool(array $pool, int $length): string
    {
        $password = '';
        $count = count($pool);
        for ($i = 0; $i < $length; $i++) {
            $password .= $pool[random_int(0, $count - 1)];
        }
        return $password;
    }

    /**
     * Generates a single password from the full pool of characters. To
     * implement advanced password policies, this method can be overridden.
     *
     * @param int $length number of characters in password.
     * @return string         generated password.
     * @throws Exception      if no CSPRNG stream of bits is available.
     */
    protected function generateOne(int $length): string
    {
        return $this->generateFromPool($this->pool, $length);
    }

}

/**
 * Abstract superclass for builder objects that create and intialize
 * instances of {@link PasswordGenerator} concrete subclasses..
 */
abstract class PasswordGeneratorBuilder
{

    private $upperIncluded = true;
    private $lowerIncluded = true;
    private $digitIncluded = true;
    private $punctuationIncluded = true;
    private $ambiguousExcluded = true;
    private $upperMin = 0;
    private $lowerMin = 0;
    private $digitMin = 0;
    private $punctuationMin = 0;
    private $tabu = '';

    /**
     * Initializes (trivially) this instance.
     */
    protected function __construct()
    {
    }

    /**
     * Sets or clears the option to include upper-case characters.
     *
     * @param bool $include             include (true) or exclude (false) upper-
     *                                  case characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function includeUpper(bool $include = true): PasswordGeneratorBuilder
    {
        $this->upperIncluded = $include;
        return $this;
    }

    /**
     * Sets or clears the option to include lower-case characters.
     *
     * @param bool $include             include (true) or exclude (false) lower-
     *                                  case characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function includeLower(bool $include = true): PasswordGeneratorBuilder
    {
        $this->lowerIncluded = $include;
        return $this;
    }

    /**
     * Sets or clears the option to include digit characters.
     *
     * @param bool $include             include (true) or exclude (false) digit
     *                                  characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function includeDigit(bool $include = true): PasswordGeneratorBuilder
    {
        $this->digitIncluded = $include;
        return $this;
    }

    /**
     * Sets or clears the option to include punctuation/symbol characters.
     *
     * @param bool $include             include (true) or exclude (false)
     *                                  puntuation characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function includePunctuation(bool $include = true): PasswordGeneratorBuilder
    {
        $this->punctuationIncluded = $include;
        return $this;
    }

    /**
     * Sets or clears the option to exclude the ambiguous character pairs,
     * "0"/"O" and "1"/"l".
     *
     * @param bool $include             Exclude (true) or include (false) the
     *                                  ambiguous character pairs.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function excludeAmbiguous(bool $exclude = true): PasswordGeneratorBuilder
    {
        $this->ambiguousExcluded = $exclude;
        return $this;
    }

    /**
     * Sets the minimum number of upper-case characters required in the
     * generated password. Note that before this method is invoked, the default
     * minimum is 0 (zero); if the method is then invoked with the default
     * parameter value, the minimum increases to 1 (one).
     *
     * @param int $min                  minimum required number of upper-case
     *                                  characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function requireUpper(int $min = 1): PasswordGeneratorBuilder
    {
        $this->upperMin = $min;
        return $this;
    }

    /**
     * Sets the minimum number of lower-case characters required in the
     * generated password. Note that before this method is invoked, the default
     * minimum is 0 (zero); if the method is then invoked with the default
     * parameter value, the minimum increases to 1 (one).
     *
     * @param int $min                  minimum required number of lower-case
     *                                  characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function requireLower(int $min = 1): PasswordGeneratorBuilder
    {
        $this->lowerMin = $min;
        return $this;
    }

    /**
     * Sets the minimum number of digit characters required in the generated
     * password. Note that before this method is invoked, the default minimum is
     * 0 (zero); if the method is then invoked with the default parameter value,
     * the minimum increases to 1 (one).
     *
     * @param int $min                  minimum required number of digit
     *                                  characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function requireDigit(int $min = 1): PasswordGeneratorBuilder
    {
        $this->digitMin = $min;
        return $this;
    }

    /**
     * Sets the minimum number of punctuation characters required in the
     * generated password. Note that before this method is invoked, the default
     * minimum is 0 (zero); if the method is then invoked with the default
     * parameter value, the minimum increases to 1 (one).
     *
     * @param int $min                  minimum required number of punctuation
     *                                  characters.
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function requirePunctuation(int $min = 1): PasswordGeneratorBuilder
    {
        $this->punctuationMin = $min;
        return $this;
    }

    /**
     * Excludes a set of characters (specified as a string) from the pool. While
     * the intent of this is primarily to restrict the set of punctuation
     * symbols in the pool (as appropriate), it can be used to remove
     * <i>any</i> characters from the pool. Also note that specifying characters
     * here that are not in the pool does not cause an error.
     *
     * @param string $forbidden         characters to remove
     * @return PasswordGeneratorBuilder this instance, for use in fluent
     *                                  interface method chaining.
     */
    public function forbid(string $forbidden): PasswordGeneratorBuilder
    {
        $this->tabu = $forbidden;
        return $this;
    }

    /**
     * Creates, initializes, and returns an instance of a {@link
     * PasswordGenerator} subclass, with the {@link generateOne} method
     * overridden to enforce any character subset minimum requirements.
     *
     * @return PasswordGenerator    fully initialized, immutable builder.
     */
    public function build(): PasswordGenerator
    {
        $pool = $this->pool();
        return new class($pool, $this->upperMin, $this->lowerMin, $this->digitMin, $this->punctuationMin)
            extends PasswordGenerator
        {

            private $upper;
            private $lower;
            private $digit;
            private $punctuation;
            private $upperMin;
            private $lowerMin;
            private $digitMin;
            private $punctuationMin;

            public function __construct(string $pool, int $upperMin, int $lowerMin, int $digitMin, int $punctuationMin)
            {
                $poolArray = str_split($pool);
                parent::__construct($poolArray);
                $this->upperMin = $upperMin;
                $this->lowerMin = $lowerMin;
                $this->digitMin = $digitMin;
                $this->punctuationMin = $punctuationMin;
                $this->upper = array_values(array_intersect($poolArray, str_split(PasswordGenerator::UPPER)));
                $this->lower = array_values(array_intersect($poolArray, str_split(PasswordGenerator::LOWER)));
                $this->digit = array_values(array_intersect($poolArray, str_split(PasswordGenerator::DIGIT)));
                $this->punctuation = array_values(array_intersect($poolArray, str_split(PasswordGenerator::SYMBOL)));
            }

            protected function generateOne(int $length): string
            {
                $password = $this->generateFromPool($this->upper, $this->upperMin)
                    . $this->generateFromPool($this->lower, $this->lowerMin)
                    . $this->generateFromPool($this->digit, $this->digitMin)
                    . $this->generateFromPool($this->punctuation, $this->punctuationMin);
                $additional = parent::generateOne($length - strlen($password));
                $password .= $additional;
                $passwordArray = str_split($password);
                shuffle($passwordArray);
                return join($passwordArray);
            }

        };

    }

    /**
     * Aggregates the character set inclusions and exclusions into a single
     * string containing all the characters a generator will have available,
     * then returns that string.
     *
     * @return string   all characters in pool.
     */
    protected function pool(): string
    {
        $pool = '';
        if ($this->upperIncluded) {
            $pool .= PasswordGenerator::UPPER;
        }
        if ($this->lowerIncluded) {
            $pool .= PasswordGenerator::LOWER;
        }
        if ($this->punctuationIncluded) {
            $pool .= PasswordGenerator::SYMBOL;
        }
        if ($this->digitIncluded) {
            $pool .= PasswordGenerator::DIGIT;
            if ($this->ambiguousExcluded) {
                if ($this->lowerIncluded) {
                    $pool = str_replace(['1', 'l'], '', $pool);
                }
                if ($this->upperIncluded) {
                    $pool = str_replace(['0', 'O'], '', $pool);
                }
            }
        }
        if ($this->tabu != '') {
            $tabuArray = str_split($this->tabu);
            $pool = str_replace($tabuArray, '', $pool);
        }
        return $pool;
    }

}
