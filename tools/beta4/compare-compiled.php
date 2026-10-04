<?php
/**
 * Compare the compiled PHP of two versions of the extension's templates.
 *
 * Development tool for 1.0.0-beta4 (the switch from phpBB's legacy template
 * syntax to native Twig). NOT shipped: it lives outside ext/.
 *
 * Why compiled output: a template rewritten from <!-- IF --> / {VAR} to
 * {% if %} / {{ VAR }} must mean exactly what it meant before. Rendering only
 * shows the branches a page happens to take; the compiled PHP contains every
 * branch. Both versions are compiled the way the board compiles them: Twig
 * 2.16.1 with phpBB 3.3.17's own lexer, tag parsers, operators and filters,
 * all loaded read-only from the phpBB test tree. The lexer also runs over
 * native Twig on the board, so it runs over both sides here.
 *
 * Normalisations, both reported per template:
 *   N1  The generated class name (__TwigTemplate_<hash>) is derived from the
 *       loader's cache key and differs between any two sources. Always applied.
 *   N2  phpBB's {% INCLUDE %} wraps Twig's include node in a namespace
 *       prologue and epilogue (phpbb/template/twig/node/includenode.php); Twig's
 *       {% include %} does not. For a literal path without '@' the wrapper does
 *       nothing, so it is removed, and getDebugInfo() is reduced to its template
 *       line numbers because the PHP line numbers shift with it. Applied only
 *       when N1 alone does not make the two sides identical.
 *
 * Usage (from the repository root; no git inside the PHP container, so the
 * reference revision is unpacked on the host first):
 *
 *   mkdir -p scratch/ref && git archive <rev> ext/uflagmey/donationcampaigns \
 *       | tar -x -C scratch/ref
 *   docker run --rm -v "$PWD":"$PWD" -w "$PWD" php:8.3-cli \
 *       php tools/beta4/compare-compiled.php \
 *       scratch/ref/ext/uflagmey/donationcampaigns ext/uflagmey/donationcampaigns \
 *       [--out=scratch/compiled]
 *
 * Exit codes: 0 every template identical (after N1, or N1+N2); 1 at least one
 * template differs or exists on one side only; 2 usage error or wrong phpBB/Twig.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

const EXPECTED_PHPBB = '3.3.17';
const EXPECTED_TWIG = '2.16.1';

$args = array_slice($argv, 1);
$out = null;
$dirs = array();

foreach ($args as $arg)
{
	if (strpos($arg, '--out=') === 0)
	{
		$out = substr($arg, 6);
		continue;
	}
	$dirs[] = rtrim($arg, '/');
}

if (count($dirs) !== 2 || !is_dir($dirs[0]) || !is_dir($dirs[1]))
{
	fwrite(STDERR, "usage: php tools/beta4/compare-compiled.php <old-extension-dir> <new-extension-dir> [--out=DIR]\n");
	exit(2);
}

$phpbb_root = (getenv('PHPBB_TEST_PATH') ?: dirname(__DIR__, 2) . '/.phpbb-test/phpbb-release-3.3.17') . '/phpBB';

if (!is_file($phpbb_root . '/vendor/autoload.php') || !is_file($phpbb_root . '/includes/constants.php'))
{
	fwrite(STDERR, "compare-compiled: phpBB test tree with vendor/ not found at $phpbb_root\n");
	exit(2);
}

preg_match("/define\('PHPBB_VERSION', '([^']+)'\)/", file_get_contents($phpbb_root . '/includes/constants.php'), $version);

if (($version[1] ?? '') !== EXPECTED_PHPBB)
{
	fwrite(STDERR, 'compare-compiled: expected phpBB ' . EXPECTED_PHPBB . ', found ' . ($version[1] ?? 'none') . "\n");
	exit(2);
}

require $phpbb_root . '/vendor/autoload.php';
require $phpbb_root . '/phpbb/class_loader.php';
(new \phpbb\class_loader('phpbb\\', $phpbb_root . '/phpbb/', 'php'))->register();

if (\Twig\Environment::VERSION !== EXPECTED_TWIG)
{
	fwrite(STDERR, 'compare-compiled: expected Twig ' . EXPECTED_TWIG . ', found ' . \Twig\Environment::VERSION . "\n");
	exit(2);
}

/**
 * The parts of phpBB's Twig extension that shape compiled code.
 *
 * phpBB's own extension needs a booted board environment in its constructor,
 * which only the EVENT/INCLUDEPHP/PHP tags use; the extension has none of
 * them, so an unknown one fails the compile loudly. Operators and filters are
 * phpBB's own; the functions are method callables with phpBB's names, never
 * closures (Twig 2.16.1 compiles a closure into invalid PHP on PHP >= 8.4).
 */
class compare_compiled_extension extends \Twig\Extension\AbstractExtension
{
	/** @var \phpbb\template\twig\extension */
	private $phpbb;

	public function __construct()
	{
		$this->phpbb = (new \ReflectionClass(\phpbb\template\twig\extension::class))->newInstanceWithoutConstructor();
	}

	public function getTokenParsers()
	{
		return array(
			new \phpbb\template\twig\tokenparser\defineparser,
			new \phpbb\template\twig\tokenparser\includeparser,
			new \phpbb\template\twig\tokenparser\includejs,
			new \phpbb\template\twig\tokenparser\includecss,
		);
	}

	public function getOperators()
	{
		return $this->phpbb->getOperators();
	}

	public function getFilters()
	{
		return $this->phpbb->getFilters();
	}

	public function getFunctions()
	{
		return array(
			new \Twig\TwigFunction('lang', array($this, 'lang')),
			new \Twig\TwigFunction('lang_defined', array($this, 'lang')),
			new \Twig\TwigFunction('lang_js', array($this, 'lang')),
		);
	}

	public function lang()
	{
		return '';
	}
}

/**
 * Every template of an extension directory, keyed "area/relative/path.html".
 */
function compare_compiled_templates($ext_dir)
{
	$areas = array('adm' => $ext_dir . '/adm/style');

	foreach (glob($ext_dir . '/styles/*/template', GLOB_ONLYDIR) as $style_dir)
	{
		$areas['styles/' . basename(dirname($style_dir))] = $style_dir;
	}

	$templates = array();

	foreach ($areas as $area => $dir)
	{
		if (!is_dir($dir))
		{
			continue;
		}

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

		foreach ($files as $file)
		{
			if (substr($file, -5) === '.html')
			{
				$templates[$area . '/' . substr($file, strlen($dir) + 1)] = file_get_contents($file);
			}
		}
	}

	ksort($templates);

	return $templates;
}

function compare_compiled_compile($name, $code)
{
	$loader = new \Twig\Loader\ArrayLoader(array($name => $code));
	$env = new \Twig\Environment($loader, array(
		'debug'			=> false,
		'autoescape'	=> false,
		'cache'			=> false,
	));
	$env->setLexer(new \phpbb\template\twig\lexer($env));
	$env->addExtension(new compare_compiled_extension());

	return $env->compileSource($loader->getSourceContext($name));
}

function compare_compiled_n1($php)
{
	return preg_replace('/__TwigTemplate_[0-9a-f]+/', '__TwigTemplate_X', $php);
}

function compare_compiled_n2($php)
{
	$php = preg_replace('/^( *)\$location = ("[^"@]*");\n\1\$namespace = false;\n\1if \(strpos\(\$location, \'@\'\) === 0\) \{\n(?:\1    .*\n){3}\1\}\n/m', '', $php);
	$php = preg_replace('/^( *)if \(\$namespace\) \{\n\1    \$this->env->setNamespaceLookUpOrder\(\$previous_look_up_order\);\n\1\}\n/m', '', $php);

	return preg_replace_callback('/return array \((.*?)\);/', function ($match) {
		preg_match_all('/=> (\d+)/', $match[1], $lines);
		return 'return TEMPLATE_LINES(' . implode(',', $lines[1]) . ');';
	}, $php);
}

/**
 * A minimal line diff (longest common subsequence), enough for templates.
 */
function compare_compiled_diff($a, $b)
{
	$a = explode("\n", $a);
	$b = explode("\n", $b);
	$n = count($a);
	$m = count($b);
	$lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

	for ($i = $n - 1; $i >= 0; $i--)
	{
		for ($j = $m - 1; $j >= 0; $j--)
		{
			$lcs[$i][$j] = ($a[$i] === $b[$j]) ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
		}
	}

	$diff = '';
	$i = $j = 0;

	while ($i < $n || $j < $m)
	{
		if ($i < $n && $j < $m && $a[$i] === $b[$j])
		{
			$i++;
			$j++;
		}
		else if ($i < $n && ($j === $m || $lcs[$i + 1][$j] >= $lcs[$i][$j + 1]))
		{
			$diff .= sprintf("-%4d %s\n", $i + 1, $a[$i++]);
		}
		else
		{
			$diff .= sprintf("+%4d %s\n", $j + 1, $b[$j++]);
		}
	}

	return $diff;
}

$old = compare_compiled_templates($dirs[0]);
$new = compare_compiled_templates($dirs[1]);
$names = array_unique(array_merge(array_keys($old), array_keys($new)));
sort($names);

printf("phpBB %s, Twig %s, PHP %s\n", EXPECTED_PHPBB, \Twig\Environment::VERSION, PHP_VERSION);
printf("old: %s\nnew: %s\n\n", $dirs[0], $dirs[1]);

$failed = 0;

foreach ($names as $name)
{
	if (!isset($old[$name]) || !isset($new[$name]))
	{
		printf("%-62s %s\n", $name, isset($old[$name]) ? 'ONLY-OLD' : 'ONLY-NEW');
		$failed++;
		continue;
	}

	$a = compare_compiled_n1(compare_compiled_compile($name, $old[$name]));
	$b = compare_compiled_n1(compare_compiled_compile($name, $new[$name]));

	if ($a === $b)
	{
		$status = 'IDENTICAL (N1)';
	}
	else if (compare_compiled_n2($a) === compare_compiled_n2($b))
	{
		$status = 'IDENTICAL (N1+N2)';
	}
	else
	{
		$status = 'DIFFERENT';
		$failed++;
	}

	printf("%-62s %s\n", $name, $status);

	if ($status === 'DIFFERENT')
	{
		echo compare_compiled_diff(compare_compiled_n2($a), compare_compiled_n2($b)), "\n";
	}

	if ($out !== null)
	{
		$base = $out . '/' . str_replace('/', '__', $name);
		@mkdir($out, 0777, true);
		file_put_contents($base . '.old.php', $a);
		file_put_contents($base . '.new.php', $b);
	}
}

printf("\n%d template(s), %d different or one-sided\n", count($names), $failed);

exit($failed ? 1 : 0);
