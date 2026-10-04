<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\tests\template_renderer;

/**
 * The template renderer every rendering test relies on.
 *
 * The rendering tests are evidence only as far as the renderer is the board's
 * engine. These pin that down: the versions it runs on, and the behaviours a
 * hand-written renderer used to get wrong or leave out.
 */
class template_engine_test extends \phpbb_test_case
{
	/**
	 * The phpBB and Twig the board runs. tests/bootstrap.php already stops the
	 * suite on any other; this states it where a reader of the tests looks.
	 */
	public function test_the_engine_is_phpbb_3_3_17_on_twig_2_16_1()
	{
		$this->assertSame('3.3.17', PHPBB_VERSION);
		$this->assertSame('2.16.1', \Twig\Environment::VERSION);
	}

	/**
	 * Only the branch whose condition holds renders.
	 */
	public function test_conditions_are_evaluated()
	{
		$template = '<!-- IF S_FLAG -->yes<!-- ELSE -->no<!-- ENDIF -->';

		$this->assertSame('yes', template_renderer::render($template, array('S_FLAG' => true)));
		$this->assertSame('no', template_renderer::render($template, array('S_FLAG' => false)));
		$this->assertSame('no', template_renderer::render($template, array()));
	}

	/**
	 * Row variables are phpBB's own, from phpbb\template\context.
	 */
	public function test_block_rows_carry_phpbb_row_variables()
	{
		$html = template_renderer::render(
			'<!-- BEGIN row -->{row.S_ROW_COUNT}<!-- IF row.S_FIRST_ROW -->F<!-- ENDIF --><!-- IF row.S_LAST_ROW -->L<!-- ENDIF -->,<!-- BEGINELSE -->empty<!-- END row -->',
			array(),
			array('row' => array(array(), array(), array()))
		);

		$this->assertSame('0F,1,2L,', $html);
		$this->assertSame('empty', template_renderer::render('<!-- BEGIN row -->x<!-- BEGINELSE -->empty<!-- END row -->', array()));
	}

	/**
	 * No language file is loaded, so a language key renders as itself.
	 */
	public function test_a_language_key_renders_as_the_key()
	{
		$this->assertSame('SOME_KEY SOME_KEY', template_renderer::render("{L_SOME_KEY} {{ lang('SOME_KEY') }}", array()));
	}

	/**
	 * phpBB turns autoescaping off: only an explicit |e escapes.
	 */
	public function test_only_the_escape_filter_escapes()
	{
		$this->assertSame(
			'<b>&"\' &lt;b&gt;&amp;&quot;&#039;',
			template_renderer::render('{VALUE} {VALUE|e}', array('VALUE' => '<b>&"\''))
		);
	}

	/**
	 * The extension's namespace reaches its theme directory, as on the board:
	 * INCLUDECSS of the shipped stylesheet resolves instead of failing.
	 */
	public function test_the_extension_stylesheet_include_resolves()
	{
		$this->assertSame('', trim(template_renderer::render(
			file_get_contents(dirname(__DIR__, 2) . '/styles/prosilver/template/event/overall_header_head_append.html'),
			array()
		)));
	}
}
