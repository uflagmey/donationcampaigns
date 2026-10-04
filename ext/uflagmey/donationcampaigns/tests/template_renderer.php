<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests;

/**
 * Renders a template with phpBB's real template engine.
 *
 * Escaping happens in the templates, so a test that only inspects assigned
 * variables cannot see whether output is safe; it has to render. This class
 * renders through the engine the board uses, built the way phpBB 3.3.17 builds
 * it for its own template tests (tests/template/template_test_case.php,
 * setup_engine()): phpbb\template\twig\environment, twig, extension, lexer and
 * context, from the read-only phpBB test tree, on that tree's Twig 2.16.1.
 * tests/bootstrap.php refuses to run the suite on any other phpBB or Twig.
 *
 * Consequences a test relies on:
 *
 *   - Conditions are evaluated. A branch renders only when its condition
 *     holds for the variables the test assigned.
 *   - Legacy syntax ({VAR}, {VAR|e}, <!-- IF -->, …) and native Twig both go
 *     through phpBB's lexer, exactly as on the board, with autoescape off.
 *   - Block rows get phpBB's own row variables (S_ROW_COUNT, S_FIRST_ROW,
 *     S_LAST_ROW, S_NUM_ROWS) from phpbb\template\context.
 *   - lang() is phpBB's, over a language object with no language files
 *     loaded, so {L_KEY} and {{ lang('KEY') }} render as KEY. Whether a key
 *     has a translation is the language tests' business.
 *   - Core's overall_header.html, overall_footer.html and pagination.html are
 *     stubs: the board's own markup is not under test here. The header and
 *     footer stubs carry only what core's carry for assets, {$STYLESHEETS}
 *     and {$SCRIPTS}, so INCLUDECSS and INCLUDEJS output lands in the page as
 *     on the board.
 *   - The extension's @uflagmey_donationcampaigns namespace resolves to its
 *     prosilver template/ and theme/ directories, as phpbb\template\twig\twig
 *     registers it; INCLUDECSS and INCLUDEJS resolve real files.
 */
class template_renderer
{
	/** @var string|null Directory holding the core stubs and the rendered source */
	protected static $work_dir;

	/**
	 * Render a template source with the given variables.
	 *
	 * @param string $template Template source, legacy or native Twig
	 * @param array $vars Template variables
	 * @param array $blocks Block rows, keyed by block name, in assignment order
	 * @return string
	 */
	public static function render($template, array $vars, array $blocks = array())
	{
		global $phpbb_root_path, $phpEx;

		$work_dir = self::work_dir();
		$name = 'render_' . sha1($template) . '.html';
		file_put_contents($work_dir . '/' . $name, $template);

		$config = new \phpbb\config\config(array('load_tplcompile' => true, 'tpl_allow_php' => false, 'assets_version' => 1));
		$language = new \phpbb\language\language(new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx));
		$user = new \phpbb\user($language, '\phpbb\datetime');
		$filesystem = new \phpbb\filesystem\filesystem();
		$path_helper = new \phpbb\path_helper(
			new \phpbb\symfony_request(new \phpbb_mock_request()),
			$filesystem,
			new \phpbb_mock_request(),
			$phpbb_root_path,
			$phpEx
		);
		$context = new \phpbb\template\context();
		$environment = new \phpbb\template\twig\environment(
			$config,
			$filesystem,
			$path_helper,
			$phpbb_root_path . 'cache/twig',
			null,
			new \phpbb\template\twig\loader($filesystem, ''),
			null,
			array(
				'cache'			=> false,
				'debug'			=> false,
				'auto_reload'	=> true,
				'autoescape'	=> false,
			)
		);
		$template_engine = new \phpbb\template\twig\twig(
			$path_helper,
			$config,
			$context,
			$environment,
			$phpbb_root_path . 'cache/twig',
			$user,
			array(new \phpbb\template\twig\extension($context, $environment, $language))
		);
		$environment->setLexer(new \phpbb\template\twig\lexer($environment));

		$package = dirname(__DIR__);
		$template_engine->set_custom_style('donationcampaigns_tests', array(
			$work_dir,
			$package . '/styles/prosilver/template',
			$package . '/adm/style',
		));
		$environment->getLoader()->addPath($package . '/styles/prosilver/template', 'uflagmey_donationcampaigns');
		$environment->getLoader()->addPath($package . '/styles/prosilver/theme', 'uflagmey_donationcampaigns');

		$template_engine->assign_vars($vars);

		foreach ($blocks as $block => $rows)
		{
			foreach ($rows as $row)
			{
				$template_engine->assign_block_vars($block, $row);
			}
		}

		$template_engine->set_filenames(array('body' => $name));

		return $template_engine->assign_display('body');
	}

	/**
	 * A template's source with the extension's own includes pasted in.
	 *
	 * For tests that read template SOURCE (structure, attributes, names) of a
	 * page whose markup partly lives in a shared partial. Only what is certain
	 * from the source is resolved: a string-literal argument such as
	 * 'prefix': '' is substituted; a variable argument stays as the partial
	 * writes it ({{ percent }}), because what it renders as is the renderer's
	 * job, not a text substitution's. The partial's heading comment renders as
	 * nothing and is dropped.
	 *
	 * @param string $source
	 * @return string
	 */
	public static function inline_partials($source)
	{
		$pattern = "/\\{% include '@uflagmey_donationcampaigns\\/([a-z_]+\\.html)' with \\{(.*?)\\}(?: only)? %\\}/s";

		return preg_replace_callback($pattern, function ($include) {
			$partial = file_get_contents(dirname(__DIR__) . '/styles/prosilver/template/' . $include[1]);

			preg_match_all("/'([a-z_]+)'\\s*:\\s*'([^']*)'/", $include[2], $literals, PREG_SET_ORDER);

			foreach ($literals as $literal)
			{
				$partial = str_replace('{{ ' . $literal[1] . ' }}', $literal[2], $partial);
			}

			return rtrim(preg_replace('/\\{#.*?#\\}\\n?/s', '', $partial), "\n");
		}, $source);
	}

	/**
	 * A per-process directory with the core stubs, removed at shutdown.
	 *
	 * Created at run time rather than shipped as fixtures, so no template-like
	 * file sits in the repository outside the extension's real templates.
	 *
	 * @return string
	 */
	protected static function work_dir()
	{
		if (self::$work_dir === null)
		{
			self::$work_dir = sys_get_temp_dir() . '/donationcampaigns-templates-' . getmypid();

			if (!is_dir(self::$work_dir))
			{
				mkdir(self::$work_dir);
			}

			// The asset placeholders sit where core's own header and footer put
			// them (prosilver and adm overall_header.html / overall_footer.html).
			$stubs = array(
				'overall_header.html'	=> '{$STYLESHEETS}',
				'overall_footer.html'	=> '{$SCRIPTS}',
				'pagination.html'		=> '',
			);

			foreach ($stubs as $stub => $content)
			{
				file_put_contents(self::$work_dir . '/' . $stub, $content);
			}

			$work_dir = self::$work_dir;
			register_shutdown_function(function () use ($work_dir) {
				array_map('unlink', glob($work_dir . '/*'));
				rmdir($work_dir);
			});
		}

		return self::$work_dir;
	}
}
