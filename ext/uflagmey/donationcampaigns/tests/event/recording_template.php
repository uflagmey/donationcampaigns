<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

/**
 * A template that renders nothing and remembers everything.
 *
 * The listener's entire observable contract is the set of variables it
 * assigns, so the tests assert on exactly that. Rendering the real prosilver
 * template would need a whole board; what matters here is that the right
 * values — and only the right values — reach the template layer.
 */
class recording_template implements \phpbb\template\template
{
	/** @var array Flat template variables, by name */
	public $vars = array();

	/** @var array Block variables, by block name */
	public $blocks = array();

	public function assign_vars(array $vararray)
	{
		foreach ($vararray as $key => $value)
		{
			$this->vars[$key] = $value;
		}

		return $this;
	}

	public function assign_var($varname, $varval)
	{
		$this->vars[$varname] = $varval;

		return $this;
	}

	public function assign_block_vars($blockname, array $vararray)
	{
		if (!isset($this->blocks[$blockname]))
		{
			$this->blocks[$blockname] = array();
		}

		$this->blocks[$blockname][] = $vararray;

		return $this;
	}

	public function assign_block_vars_array($blockname, array $block_vars_array)
	{
		foreach ($block_vars_array as $vararray)
		{
			$this->assign_block_vars($blockname, $vararray);
		}

		return $this;
	}

	/**
	 * @param string $blockname
	 * @return array
	 */
	public function block($blockname)
	{
		return isset($this->blocks[$blockname]) ? $this->blocks[$blockname] : array();
	}

	// ----------------------------------------------------- unused interface

	public function clear_cache()
	{
		return $this;
	}

	public function set_filenames(array $filename_array)
	{
		return $this;
	}

	public function get_user_style()
	{
		return array();
	}

	public function set_style($style_directories = array('styles'))
	{
		return $this;
	}

	public function set_custom_style($names, $paths)
	{
		return $this;
	}

	public function destroy()
	{
		return $this;
	}

	public function destroy_block_vars($blockname)
	{
		unset($this->blocks[$blockname]);

		return $this;
	}

	public function display($handle)
	{
		return $this;
	}

	public function assign_display($handle, $template_var = '', $return_content = true)
	{
		return '';
	}

	public function append_var($varname, $varval)
	{
		$this->vars[$varname] = (isset($this->vars[$varname]) ? $this->vars[$varname] : '') . $varval;

		return $this;
	}

	public function retrieve_vars(array $vararray)
	{
		$result = array();

		foreach ($vararray as $name)
		{
			$result[$name] = isset($this->vars[$name]) ? $this->vars[$name] : null;
		}

		return $result;
	}

	public function retrieve_var($varname)
	{
		return isset($this->vars[$varname]) ? $this->vars[$varname] : null;
	}

	public function retrieve_block_vars($blockname, array $vararray)
	{
		return $this->block($blockname);
	}

	public function alter_block_array($blockname, array $vararray, $key = false, $mode = 'insert')
	{
		return true;
	}

	public function find_key_index($blockname, $key)
	{
		return false;
	}

	public function get_source_file_for_handle($handle)
	{
		return '';
	}
}
