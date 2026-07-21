<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests;

/**
 * Renders a phpBB template far enough to assert what reaches the page.
 *
 * Escaping now happens in the templates, with Twig's |e filter, so a test that
 * only inspects assigned variables can no longer see whether output is safe.
 * Standing up phpBB's real Twig environment needs a booted board; this
 * reproduces the two behaviours the escaping contract depends on:
 *
 *   {VAR|e}   escaped, exactly as Twig's |e would
 *   {VAR}     emitted verbatim, because phpBB disables autoescaping
 *
 * Conditionals are ignored — their branches are left in place — because what
 * is being asserted is how a value is ESCAPED, not which branch renders. The
 * real templates are additionally exercised on the Docker board.
 */
class template_renderer
{
	/**
	 * @param string $template Raw template source
	 * @param array $vars Flat template variables
	 * @param array $blocks Block variables, keyed by block name
	 * @return string
	 */
	public static function render($template, array $vars, array $blocks = array())
	{
		$html = self::render_blocks($template, $blocks);

		return self::substitute($html, $vars);
	}

	/**
	 * Expand each <!-- BEGIN x --> … <!-- END x --> once per row.
	 *
	 * @param string $html
	 * @param array $blocks
	 * @return string
	 */
	protected static function render_blocks($html, array $blocks)
	{
		// Blocks are discovered from the TEMPLATE, not from what was assigned.
		// A block the module assigned nothing to still has to render — that is
		// precisely the empty-table case, and driving the loop from $blocks
		// would silently leave its markup unexpanded and pass either way.
		preg_match_all('/<!-- BEGIN ([a-z_]+) -->/', $html, $found);

		foreach ($found[1] as $name)
		{
			$rows = isset($blocks[$name]) ? $blocks[$name] : array();

			$pattern = '/<!-- BEGIN ' . preg_quote($name, '/') . ' -->(.*?)<!-- END ' . preg_quote($name, '/') . ' -->/s';

			if (!preg_match($pattern, $html, $match))
			{
				continue;
			}

			$body = $match[1];
			$empty = '';

			// phpBB's BEGINELSE: the part after it renders instead of the
			// loop when the block has no rows. The ACP's empty-table row
			// lives there, so a renderer that ignored it would report an
			// empty list as no markup at all.
			if (strpos($body, '<!-- BEGINELSE -->') !== false)
			{
				list($body, $empty) = explode('<!-- BEGINELSE -->', $body, 2);
			}

			$rendered = '';

			foreach ($rows as $row)
			{
				$prefixed = array();

				foreach ($row as $key => $value)
				{
					$prefixed[$name . '.' . $key] = $value;
				}

				$rendered .= self::substitute($body, $prefixed);
			}

			if (!$rows)
			{
				$rendered = $empty;
			}

			$html = preg_replace($pattern, str_replace('$', '\\$', $rendered), $html, 1);
		}

		return $html;
	}

	/**
	 * @param string $html
	 * @param array $vars
	 * @return string
	 */
	protected static function substitute($html, array $vars)
	{
		foreach ($vars as $name => $value)
		{
			if (is_bool($value) || is_array($value))
			{
				continue;
			}

			$value = (string) $value;

			// The filter form first, or the bare replacement would eat it.
			$html = str_replace('{' . $name . '|e}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
			$html = str_replace('{' . $name . '}', $value, $html);
		}

		return $html;
	}
}
