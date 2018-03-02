---


---

<h1 id="introduction-to-the-builder-pattern-in-php">Introduction to the Builder Pattern in PHP</h1>
<p>The Builder pattern is one of the “Gang of Four” (GoF) design patterns. It is classified as a <em>creational design pattern</em> – i.e. it’s intended to address some brittle historical practices (i.e. anti-patterns) for creating object instances.</p>
<p>When using the Builder pattern, we create an object of one type by creating an object of a second, supporting type, invoking a number of methods on the second object, ending with a method that returns a fully constructed object instance (often immutable) of the first type.</p>
<h2 id="scenario">Scenario</h2>
<h3 id="requirements">Requirements</h3>
<p>A client has asked us to develop a random password generator component. In joint design conversations, we agree on the following minimal technical and functional requirements:</p>
<ol>
<li>
<p>The generator functionality will be abstracted and encapsulated into a class; instances of the class will be created on demand, and used to generate one or more passwords in a single request.</p>
</li>
<li>
<p>The generator must be configurable, to allow inclusion/exclusion of the following:</p>
<ul>
<li>Upper-case letters from the Latin alphabet;</li>
<li>Lower-case letters from the Latin alphabet;</li>
<li>The digits 0-9;</li>
<li>Punctuation and (printable) symbols.</li>
</ul>
</li>
<li>
<p>Against our advice, the client insisted (and we accepted) that for each of the above character sets, the generator must be configurable with a minimum count, to enforce policies such as “A password must include at least one upper-case letter, one lower-case letter, one digit, and one punctuation character.”</p>
</li>
<li>
<p>In general, the generator should support all of the punctuation and symbol characters in the Basic Latin Unicode block, except for the space character (i.e. the ranges <code>\u0021–\u002F</code>, <code>\u003A–\u0040</code>, <code>\u005B–\u0060</code>, <code>\u007B–\u007E</code>). However, on exploring the contexts in which the generated passwords might be used, we agreed with the client that the generator should support some constraints on punctuation and symbols – specifically, the generator must allow, on initialization, the exclusion of a subset of punctuation and symbol characters.</p>
</li>
<li>
<p>The generator must allow optional exclusion of the mutually ambiguous character pairs, “1” &amp; “l” (lower-case “L”), and “0” (zero) &amp; upper-case “O”. This option must be enabled by default.</p>
</li>
<li>
<p>The generator class should have no dependencies on external configuration files. <em>All</em> of the above configuration should be specifiable by the class’s consumer.</p>
</li>
<li>
<p>At our insistence, the lifecycle of the generator object will be one-way: initialize/configure a generator instance, then use it; changing configuration options on a generator instance after we use it to generate passwords will not be supported.</p>
</li>
</ol>
<h3 id="technical-specifications">Technical Specifications</h3>
<p>Our next task is to propose an API for consuming the class – that is, for instantiating, initializing, and invoking methods on instances of the class. Very quickly, we come to the realization that, with the wide variety of configuration options, initializing the generator could become pretty complicated. In many (maybe most) use cases, we won’t need to change more than one or two of the configuration options from their default values – but in a few cases, we’ll need to change most of those options. We need to come up with an initialization approach that not only makes the generator component easy to work with for the simple use cases, but flexible enough for the tricky ones.</p>
<h4 id="approach-1-constructor-tricks">Approach 1: Constructor tricks</h4>
<ul>
<li>
<p><strong>Constructor overloads</strong></p>
<p><strong>Can we have multiple constructor methods, with different numbers/types of parameters, to support the range of configuration scenarios we anticipate?</strong></p>
<p>No. PHP doesn’t support constructor method overloading (or overloading of any other method or function, for that matter). In PHP, the <em>signature</em> of a function or method – which must be unique within the scope of the function or method – consists only of the method name. (This is also the case for JavaScript, Python, and C – though not for C++, Java, C#, and <a href="http://VB.NET">VB.NET</a>.)</p>
<p>Even if we could use overloaded constructors, such an approach would arguably be a poor fit for this situation. A small number of constructors might be acceptable, but we could easily end up with many more for our generator – following a practice we sometimes call the <em>telescoping constructor anti-pattern</em>.</p>
</li>
<li>
<p><strong>Constructor with several parameters</strong></p>
<p><strong>Can’t we can define a constructor with parameters for all of the configuration options?</strong></p>
<p>We certainly could – and we’d end up with a constructor that’s much more complicated to work with than we’d like. It would have the necessary flexibility, but it wouldn’t be simple to use even in the most basic use cases.</p>
</li>
<li>
<p><strong>Constructor with default parameter values</strong></p>
<p><strong>Can we define a constructor with default values for one or more parameters?</strong></p>
<p>Yes. We might do something like this:</p>
<pre class=" language-php"><code class="prism  language-php"><span class="token keyword">class</span> <span class="token class-name">PasswordGenerator</span> 
<span class="token punctuation">{</span>

	<span class="token keyword">private</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$digitIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$punctuationIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$ambiguousExcluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minUpper</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minLower</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minDigits</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minPunctuation</span><span class="token punctuation">;</span>
	<span class="token comment">// More fields here ...</span>
	
	<span class="token keyword">public</span> <span class="token function">__construct</span><span class="token punctuation">(</span>
		bool <span class="token variable">$upperIncluded</span> <span class="token operator">=</span> <span class="token boolean">true</span><span class="token punctuation">,</span> 
		bool <span class="token variable">$lowerIncluded</span> <span class="token operator">=</span> <span class="token boolean">true</span><span class="token punctuation">,</span>
		bool <span class="token variable">$digitIncluded</span> <span class="token operator">=</span> <span class="token boolean">true</span><span class="token punctuation">,</span>
		bool <span class="token variable">$punctuationIncluded</span> <span class="token operator">=</span> <span class="token boolean">true</span><span class="token punctuation">,</span> 
		bool <span class="token variable">$ambiguousExcluded</span> <span class="token operator">=</span> <span class="token boolean">true</span><span class="token punctuation">;</span>
		int <span class="token variable">$minUpper</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minLower</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minDigits</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minPunctuation</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		<span class="token comment">// And so on ...</span>
	<span class="token punctuation">)</span> <span class="token punctuation">{</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">upperIncluded</span> <span class="token operator">=</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">lowerIncluded</span> <span class="token operator">=</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">digitIncluded</span> <span class="token operator">=</span> <span class="token variable">$digitIncluded</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">punctuationIncluded</span> <span class="token operator">=</span> <span class="token variable">$punctuationIncluded</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">ambiguousExcluded</span> <span class="token operator">=</span> <span class="token variable">$ambiguousExcluded</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">minUpper</span> <span class="token operator">=</span> <span class="token variable">$minUpper</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">minLower</span> <span class="token operator">=</span> <span class="token variable">$minLower</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">minDigits</span> <span class="token operator">=</span> <span class="token variable">$minDigits</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">minPunctuation</span> <span class="token operator">=</span> <span class="token variable">$minPunctuation</span><span class="token punctuation">;</span>
		<span class="token comment">// More initialization code here ...</span>
	<span class="token punctuation">}</span>

	<span class="token comment">// More methods here ...</span>

<span class="token punctuation">}</span>
</code></pre>
<p>Unfortunately, this doesn’t improve matters much. When we initialize <strong><code>PasswordGenerator</code></strong> using the constructor above, we can only omit arguments at the right end of the parameter list; we can’t skip some in the middle and specify others after that. We might work around this by using <code>null</code> default values, skipping the types on the parameter declarations, or using <em>nullable</em> types (where a question mark precedes the type in the parameter declaration); any of those options would let us specify <code>null</code> arguments in an invocation to indicate that the corresponding parameters should take their default values. But we’d still have a long list of parameters, and the arguments we pass on invocation would have to match the parameter order exactly. Producing effective documentation – not just for the generator API, but also for any code that consumes the generator component – would be a challenge.</p>
</li>
<li>
<p><strong>Combining multiple Boolean values into a bit field</strong></p>
<p><strong>Rather than use several <code>bool</code> parameters, can we combine them into a single <code>int</code>, treated as <em>bit field</em>?</strong></p>
<p>Indeed, this would reduce the number of parameters significantly in a situation like this one. Consider the following:</p>
<pre class=" language-php"><code class="prism  language-php"><span class="token keyword">class</span> <span class="token class-name">Generator</span> 
<span class="token punctuation">{</span>

	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">UPPER_INCLUDED</span> <span class="token operator">=</span> <span class="token number">1</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">LOWER_INCLUDED</span> <span class="token operator">=</span> <span class="token number">2</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">DIGIT_INCLUDED</span> <span class="token operator">=</span> <span class="token number">4</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">PUNCTUATION_INCLUDED</span> <span class="token operator">=</span> <span class="token number">8</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">AMBIGUOUS_EXCLUDED</span> <span class="token operator">=</span> <span class="token number">16</span><span class="token punctuation">;</span>
	<span class="token comment">// More option flags here ...</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">DEFAULT_OPTIONS</span> <span class="token operator">=</span> 
		<span class="token constant">UPPER_INCLUDED</span>
		<span class="token operator">|</span> <span class="token constant">LOWER_INCLUDED</span>
		<span class="token operator">|</span> <span class="token constant">DIGIT_INCLUDED</span>
		<span class="token operator">|</span> <span class="token constant">PUNCTUATION_INCLUDED</span>
		<span class="token operator">|</span> <span class="token constant">AMBIGUOUS_EXCLUDED</span><span class="token punctuation">;</span> <span class="token comment">// Maybe more ...</span>

	<span class="token keyword">private</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$digitIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$punctuationIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$ambiguousExcluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minUpper</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minLower</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minDigits</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minPunctuation</span><span class="token punctuation">;</span>
	<span class="token comment">// More fields here ...</span>

	<span class="token keyword">public</span> <span class="token function">__construct</span><span class="token punctuation">(</span>
		int <span class="token variable">$options</span> <span class="token operator">=</span> <span class="token constant">DEFAULT_OPTIONS</span><span class="token punctuation">,</span> 
		int <span class="token variable">$minUpper</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minLower</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minDigits</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		int <span class="token variable">$minPunctuation</span> <span class="token operator">=</span> <span class="token number">0</span><span class="token punctuation">,</span>
		<span class="token comment">// And so on ...</span>
	<span class="token punctuation">)</span> <span class="token punctuation">{</span>
		<span class="token comment">// Unpack the option values from the bit field.</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">upperIncluded</span> <span class="token operator">=</span> <span class="token punctuation">(</span><span class="token punctuation">(</span><span class="token variable">$options</span> <span class="token operator">&amp;</span> <span class="token constant">UPPER_INCLUDED</span><span class="token punctuation">)</span> <span class="token operator">&gt;</span> <span class="token number">0</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">lowerIncluded</span> <span class="token operator">=</span> <span class="token punctuation">(</span><span class="token punctuation">(</span><span class="token variable">$options</span> <span class="token operator">&amp;</span> <span class="token constant">LOWER_INCLUDED</span><span class="token punctuation">)</span> <span class="token operator">&gt;</span> <span class="token number">0</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">digitIncluded</span> <span class="token operator">=</span> <span class="token punctuation">(</span><span class="token punctuation">(</span><span class="token variable">$options</span> <span class="token operator">&amp;</span> <span class="token constant">DIGIT_INCLUDED</span><span class="token punctuation">)</span> <span class="token operator">&gt;</span> <span class="token number">0</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">punctuationIncluded</span> <span class="token operator">=</span> <span class="token punctuation">(</span><span class="token punctuation">(</span><span class="token variable">$options</span> <span class="token operator">&amp;</span> <span class="token constant">PUNCTUATION_INCLUDED</span><span class="token punctuation">)</span> <span class="token operator">&gt;</span> <span class="token number">0</span><span class="token punctuation">)</span><span class="token punctuation">;</span>			
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">ambiguousExcluded</span> <span class="token operator">=</span> <span class="token punctuation">(</span><span class="token punctuation">(</span><span class="token variable">$options</span> <span class="token operator">&amp;</span> <span class="token constant">AMBIGUOUS_EXCLUDED</span><span class="token punctuation">)</span> <span class="token operator">&gt;</span> <span class="token number">0</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token comment">// And so on ...</span>
		<span class="token comment">// More initialization code here ...</span>
	<span class="token punctuation">}</span>

	<span class="token comment">// More methods here ...</span>

<span class="token punctuation">}</span>
</code></pre>
<p>Now we might create an instance of <strong><code>PasswordGenerator</code></strong> that uses upper-case letters and digits (for example) this way:</p>
<pre class=" language-php"><code class="prism  language-php"><span class="token keyword">new</span> <span class="token class-name">Generator</span><span class="token punctuation">(</span>
	Generator<span class="token punctuation">:</span><span class="token punctuation">:</span><span class="token constant">DIGIT_INCLUDED</span> <span class="token operator">|</span> Generator<span class="token punctuation">:</span><span class="token punctuation">:</span><span class="token constant">UPPER_INCLUDED</span> <span class="token operator">|</span> Generator<span class="token punctuation">:</span><span class="token punctuation">:</span><span class="token constant">EXCLUDE_AMBIGOUS</span><span class="token punctuation">,</span>
	<span class="token number">0</span><span class="token punctuation">,</span> <span class="token number">0</span><span class="token punctuation">,</span> <span class="token number">0</span><span class="token punctuation">,</span> <span class="token number">0</span><span class="token punctuation">,</span>
	<span class="token comment">// And so on ...</span>
<span class="token punctuation">)</span><span class="token punctuation">;</span>
</code></pre>
<p>We’ve reduced the number of parameters, and we no longer have to remember the order of our option flags. Also, since those flags are all powers of 2, we can combine them for the invocation using the bitwise operators (recommended), or just addition – for example, note that <code>1 + 2 + 8</code> gives the same value (11) as <code>1 | 2 | 8</code>.</p>
<p>On the other hand, only Boolean parameters can be packed into bit fields in this fashion. Also, while many “old school” programmers are used to bit fields, a less experienced programmer may find them quite confusing, and will need to rely more on external documentation, rather than the API itself being more effectively self-documenting.</p>
</li>
<li>
<p><strong>Using an associative array for configuration options</strong></p>
<p><strong>Can we simply specify all the configuration option flags and settings as elements in an associative array, or properties of an object?</strong></p>
<p>In fact, this is an approach used by many PHP, JavaScript, and Python libraries: rather than initializing a complex object (or invoking a complex method) by passing a long list of arguments, we pass a much shorter argument list, where one or more of the arguments is itself an object or associative array.</p>
<pre class=" language-php"><code class="prism  language-php"><span class="token keyword">class</span> <span class="token class-name">Generator</span> 
<span class="token punctuation">{</span>

	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">UPPER_INCLUDED_KEY</span> <span class="token operator">=</span> <span class="token string">'upperIncluded'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">LOWER_INCLUDED_KEY</span> <span class="token operator">=</span> <span class="token string">'lowerIncluded'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">DIGIT_INCLUDED_KEY</span> <span class="token operator">=</span> <span class="token string">'digitIncluded'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">PUNCTUATION_INCLUDED_KEY</span> <span class="token operator">=</span> <span class="token string">'punctuationIncluded'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">AMBIGUOUS_EXCLUDED_KEY</span> <span class="token operator">=</span> <span class="token string">'ambiguousExcluded'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">MIN_UPPER_KEY</span> <span class="token operator">=</span> <span class="token string">'minUpper'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">MIN_LOWER_KEY</span> <span class="token operator">=</span> <span class="token string">'minLower'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">MIN_DIGITS_KEY</span> <span class="token operator">=</span> <span class="token string">'minDigits'</span><span class="token punctuation">;</span>
	<span class="token keyword">public</span> <span class="token keyword">const</span> <span class="token constant">MIN_PUNCTUATION_KEY</span> <span class="token operator">=</span> <span class="token string">'minPunctuation'</span><span class="token punctuation">;</span>
	<span class="token comment">// More keys here ...</span>

	<span class="token keyword">private</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$digitIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$punctuationIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$ambiguousExcluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minUpper</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minLower</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minDigits</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minPunctuation</span><span class="token punctuation">;</span>
	<span class="token comment">// More fields here ...</span>

	<span class="token keyword">public</span> <span class="token function">__construct</span><span class="token punctuation">(</span><span class="token keyword">array</span> <span class="token variable">$options</span> <span class="token operator">=</span> <span class="token punctuation">[</span><span class="token punctuation">]</span><span class="token punctuation">)</span>
	<span class="token punctuation">{</span>
		<span class="token comment">// Extract the options from the array.</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">upperIncluded</span> <span class="token operator">=</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token variable">$options</span><span class="token punctuation">,</span> <span class="token constant">UPPER_INCLUDED_KEY</span><span class="token punctuation">,</span> <span class="token boolean">true</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">lowerIncluded</span> <span class="token operator">=</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token variable">$options</span><span class="token punctuation">,</span> <span class="token constant">LOWER_INCLUDED_KEY</span><span class="token punctuation">,</span> <span class="token boolean">true</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">digitIncluded</span> <span class="token operator">=</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token variable">$options</span><span class="token punctuation">,</span> <span class="token constant">DIGIT_INCLUDED_KEY</span><span class="token punctuation">,</span> <span class="token boolean">true</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">punctuationIncluded</span> <span class="token operator">=</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token variable">$options</span><span class="token punctuation">,</span> <span class="token constant">PUNCTUATION_INCLUDED_KEY</span><span class="token punctuation">,</span> <span class="token boolean">true</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">ambiguousExcluded</span> <span class="token operator">=</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token variable">$options</span><span class="token punctuation">,</span> <span class="token constant">AMBIGUOUS_EXCLUDED_KEY</span><span class="token punctuation">,</span> <span class="token boolean">true</span><span class="token punctuation">)</span><span class="token punctuation">;</span>
		<span class="token comment">// And so on ...</span>
		<span class="token comment">// More initialization code here ...</span>
	<span class="token punctuation">}</span>

	<span class="token keyword">private</span> <span class="token keyword">function</span> <span class="token function">getDefault</span><span class="token punctuation">(</span><span class="token keyword">array</span> <span class="token variable">$array</span><span class="token punctuation">,</span> string <span class="token variable">$key</span><span class="token punctuation">,</span> <span class="token variable">$defaultValue</span><span class="token punctuation">)</span>
	<span class="token punctuation">{</span>
		<span class="token keyword">return</span> <span class="token punctuation">(</span><span class="token function">isset</span><span class="token punctuation">(</span><span class="token variable">$array</span><span class="token punctuation">[</span><span class="token variable">$key</span><span class="token punctuation">]</span><span class="token punctuation">)</span> <span class="token operator">||</span> <span class="token function">array_key_exists</span><span class="token punctuation">(</span><span class="token variable">$key</span><span class="token punctuation">,</span> <span class="token variable">$array</span><span class="token punctuation">)</span><span class="token punctuation">)</span> <span class="token operator">?</span> 
			<span class="token variable">$array</span><span class="token punctuation">[</span><span class="token variable">$key</span><span class="token punctuation">]</span> <span class="token punctuation">:</span> <span class="token variable">$defaultValue</span><span class="token punctuation">;</span>
	<span class="token punctuation">}</span>
	
	<span class="token comment">// More methods here ...</span>

<span class="token punctuation">}</span>
</code></pre>
<p>Potentially, we could use this approach to collapse all of our constructor parameters to a single associative array. Values could be specified (or not) in any order in the array, and the resulting constructor logic wouldn’t be affected. Of all of the “constructor tricks” approaches described, this is arguably the best.</p>
<p>However, the API (at least the constructor portion) is less self-documenting than ever. We’d have to write a lot of additional documentation (probably as phpDocumentor comments) to explain how it works.</p>
</li>
</ul>
<h4 id="approach-2-using-accessors-and-mutators-getters-and-setters">Approach 2: Using accessors and mutators (getters and setters)</h4>
<p>Rather than write a constructor that’s complicated in its invocation – or in the implementation code required to make the invocation less complicated – we might instead write a very simple constructor, and use mutators to set the generator options. As is often the case when we use accessors and mutators, this gives us a certain level of encapsulation (generally a good thing), at the expense of boilerplate code.</p>
<p>(Note that the example here doesn’t make use of the PHP “magic methods” <code>__set</code> and <code>__get</code>. These methods can be very useful – though they have serious shortcomings if we have an aim of writing self-documenting code – but they’re outside the scope of this introduction.)</p>
<pre class=" language-php"><code class="prism  language-php"><span class="token keyword">class</span> <span class="token class-name">Generator</span> 
<span class="token punctuation">{</span>

	<span class="token keyword">private</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$digitIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$punctuationIncluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$ambiguousExcluded</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minUpper</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minLower</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minDigits</span><span class="token punctuation">;</span>
	<span class="token keyword">private</span> <span class="token variable">$minPunctuation</span><span class="token punctuation">;</span>
	<span class="token comment">// More fields here ...</span>
	
	<span class="token keyword">public</span> <span class="token function">__construct</span><span class="token punctuation">(</span><span class="token punctuation">)</span>
	<span class="token punctuation">{</span>
		<span class="token comment">// General initialization code here ...</span>
	<span class="token punctuation">}</span>

	<span class="token keyword">public</span> <span class="token function">isUpperIncluded</span><span class="token punctuation">(</span><span class="token punctuation">)</span><span class="token punctuation">:</span> boolean 
	<span class="token punctuation">{</span>
		<span class="token keyword">return</span> <span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">upperIncluded</span><span class="token punctuation">;</span>
	<span class="token punctuation">}</span>

	<span class="token keyword">public</span> <span class="token function">setUpperIncluded</span><span class="token punctuation">(</span>boolean <span class="token variable">$upperIncluded</span><span class="token punctuation">)</span>
	<span class="token punctuation">{</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">upperIncluded</span> <span class="token operator">=</span> <span class="token variable">$upperIncluded</span><span class="token punctuation">;</span>
	<span class="token punctuation">}</span>

	<span class="token keyword">public</span> <span class="token function">isLowerIncluded</span><span class="token punctuation">(</span><span class="token punctuation">)</span><span class="token punctuation">:</span> boolean 
	<span class="token punctuation">{</span>
		<span class="token keyword">return</span> <span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token punctuation">}</span>

	<span class="token keyword">public</span> <span class="token function">setLowerIncluded</span><span class="token punctuation">(</span>boolean <span class="token variable">$lowerIncluded</span><span class="token punctuation">)</span>
	<span class="token punctuation">{</span>
		<span class="token variable">$this</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token property">lowerIncluded</span> <span class="token operator">=</span> <span class="token variable">$lowerIncluded</span><span class="token punctuation">;</span>
	<span class="token punctuation">}</span>

	<span class="token comment">// More getters and setters here ...</span>
	
	<span class="token comment">// More methods here ...</span>

<span class="token punctuation">}</span>
</code></pre>
<p>This certainly seems to be a reasonable approach. Among other benefits, we could include new configuration options in the future, without modifying the approach. Further, if we (and the client) ever decide to load configuration options from files, there are configuration libraries that will infer our property names from the names in a file, and automatically invoke the appropriate mutators to set the options. This approach is also much more self-documenting than any of the constructor-oriented options described above.</p>
<p>On the other hand, unless we add more code to our mutators, there’s no guarantee that after some sequence of mutator invocations, the generator is in a suitable state to begin generating passwords; ensuring that would mean making the mutators much more aware of the entire state of the generator than we’d normally like them to be. Further, there’s nothing preventing modifications of a generator instance via a mutator, even after we’ve begun using it to generate passwords. To satisfy the requirements that we and the client agreed to, the generator objects should really be <em>immutable</em> – that is, after instantiation and initialization by a constructor, the object state shouldn’t be allowed to change. Since the point of a mutator is to change the state of an object, this approach may be a dead end for us.</p>
<h4 id="approach-3-constructing-immutable-objects-with-a-builder">Approach 3: Constructing immutable objects with a Builder</h4>
<p>Let’s examine a different approach altogether. Instead of one class with a complicated constructor (which we could use to create an immutable object, at the cost of code that’s difficult to document, maintain, and use), or one class with simple constructor with mutators for every configuration option (more self-documenting, but won’t produce immutable objects), we’ll implement the Builder pattern with 2 classes:</p>
<ul>
<li>
<p><strong><code>PasswordGeneratorBuilder</code></strong></p>
<p>Instances of this class will be mutable objects that aren’t themselves password generators, but rather <em>builders</em> for password generator objects. This class will have a number of methods for setting the configuration options, e.g.</p>
<ul>
<li><code>includeUpper(bool $include = true)</code></li>
<li><code>requireUpper(int $min = 1)</code></li>
<li><code>includeLower(bool $include = true)</code></li>
<li><code>requireLower(int $min = 1)</code></li>
<li>…</li>
</ul>
<p>Each of these option-setting methods will be written to support a <em>fluent interface</em>, where the return value of each method on which the method was invoked, so that as many options can be set as needed, in a very direct fashion, via <em>method chaining</em>. (The fluent interface for method chaining isn’t an intrinsic element of the Builder pattern, but it is often employed as part of the pattern.)</p>
<p>Most importantly, <strong><code>PasswordGeneratorBuilder</code></strong> will have a method to create and return a password generator, with the current set of applied options:</p>
<ul>
<li><code>build()</code></li>
</ul>
</li>
<li>
<p><strong><code>PasswordGenerator</code></strong></p>
<p>This class is the type returned  by the <code>build</code> method of <strong><code>PasswordGeneratorBuilder</code></strong>. The API for this class will be extremely simple, including just 2 public methods:</p>
<ul>
<li>
<p><code>generate(int $length, $int count = 1)</code></p>
<p>This method will generate and return passwords.</p>
</li>
<li>
<p><code>builder()</code></p>
<p>This will be a <code>static</code> method, creating and returning an instance of <strong><code>PasswordGeneratorBuilder</code></strong> (or of a subclass of that).</p>
</li>
</ul>
</li>
</ul>
<p>To summarize how these classes will be used: the consumer code will invoke <strong><code>PasswordGenerator::builder</code></strong> to get an instance of <strong><code>PasswordGeneratorBuilder</code></strong>; then, after setting options via methods of the latter class, the consumer will use the <strong><code>build</code></strong> method of that class to get a <strong><code>PasswordGenerator</code></strong>, and then use the <code>generate</code> method of <em>that</em> class to generate passwords.</p>
<p>At first glance, this usage choreography probably seems convoluted – and in fact, the Builder pattern doesn’t require that we do things exactly this way. But this aspect of the implementation lets us define both of these classes as <code>abstract</code> classes, with <code>protected</code> constructors. There will be <em>no way</em> for consumer code to create an instances of either of these two classes using the <strong><code>new</code></strong> keyword with a constructor; it will have to use the methods mentioned above – which is exactly what we wanted. Further, since these are both <code>abstract</code> classes, some of the more specialized aspects of the processing will be performed by overridden methods in subclasses. Implementing in this fashion should give us a lot of flexibility for further subclassing, as necessary (e.g. for specialized password generation requirements we haven’t anticipated yet).</p>
<h3 id="implementation">Implementation</h3>
<p>The accompanying PHP files contain the implementation of the above classes, along with an example script that demonstrates their use.</p>
<ul>
<li>
<p><code>PasswordGenerator.php</code></p>
<p>Commented source code for the <strong><code>PasswordGeneratorBuilder</code></strong> and <strong><code>PasswordGenerator</code></strong> classes.</p>
</li>
<li>
<p><code>generator_demo.php</code></p>
<p>Script with 3 usage examples.</p>
</li>
</ul>

