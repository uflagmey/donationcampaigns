<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * A controller.helper double that records instead of rendering.
 *
 * The real helper needs a booted template environment to produce a page; these
 * tests only care what the controller asked it to do — which template, which
 * message, which route — and assert on the variables the controller assigned to
 * the template alongside it. So this records the calls and returns a bare
 * Response. The parent constructor is bypassed deliberately.
 */
class recording_helper extends \phpbb\controller\helper
{
	/** @var array{template:string,title:string}|null */
	public $rendered = null;

	/** @var string|null the message passed to message() */
	public $message = null;

	/** @var array<int, array{0:string,1:array}> every route() asked for */
	public $routed = array();

	public function __construct()
	{
	}

	public function render($template_file, $page_title = '', $status_code = 200, $display_online_list = false, $item_id = 0, $item = 'forum', $send_headers = false)
	{
		$this->rendered = array('template' => $template_file, 'title' => $page_title);

		return new Response('', $status_code);
	}

	public function route($route, array $params = array(), $is_amp = true, $session_id = false, $reference_type = \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH)
	{
		$this->routed[] = array($route, $params);

		return $route . (empty($params) ? '' : '?' . http_build_query($params));
	}

	public function message($message, array $parameters = array(), $title = 'INFORMATION', $code = 200)
	{
		$this->message = $message;

		return new Response('', $code);
	}
}
