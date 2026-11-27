<?php
/* Copyright (C) 2026	Nicolas Vidal	<nicolas.vidal@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       test/phpunit/AiLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for ai/lib/ai.lib.php and the payload options of the Ai class
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/lib/ai.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests
 */
class AiLibTest extends CommonClassTest
{
	/**
	 * testGetAiReasoningEffort
	 *
	 * @return void
	 */
	public function testGetAiReasoningEffort()
	{
		global $conf;

		unset($conf->global->AI_API_CUSTOM_REASONING_EFFORT);
		$this->assertSame('', getAiReasoningEffort('custom'));

		$conf->global->AI_API_CUSTOM_REASONING_EFFORT = 'None';
		$this->assertSame('none', getAiReasoningEffort('custom'));
		$this->assertSame('', getAiReasoningEffort('chatgpt'), 'The value of a service must not apply to another one');

		$conf->global->AI_API_CUSTOM_REASONING_EFFORT = 'off';
		$this->assertSame('', getAiReasoningEffort('custom'));

		unset($conf->global->AI_API_CUSTOM_REASONING_EFFORT);
	}

	/**
	 * testGetAiTemperature
	 *
	 * @return void
	 */
	public function testGetAiTemperature()
	{
		global $conf;

		unset($conf->global->AI_API_CUSTOM_TEMPERATURE);
		$this->assertNull(getAiTemperature('custom'));

		$conf->global->AI_API_CUSTOM_TEMPERATURE = '0.2';
		$this->assertSame(0.2, getAiTemperature('custom'));
		$this->assertNull(getAiTemperature('chatgpt'), 'The value of a service must not apply to another one');

		foreach (array('0', '1.0', '3', '-0.5', 'low') as $invalid) {
			$conf->global->AI_API_CUSTOM_TEMPERATURE = $invalid;
			$this->assertNull(getAiTemperature('custom'), 'Invalid value '.$invalid);
		}

		unset($conf->global->AI_API_CUSTOM_TEMPERATURE);
	}

	/**
	 * testBuildPayloadSendsServiceOptions
	 *
	 * @return void
	 */
	public function testBuildPayloadSendsServiceOptions()
	{
		global $conf, $db;

		$previousService = getDolGlobalString('AI_API_SERVICE');
		$conf->global->AI_API_SERVICE = 'custom';
		unset($conf->global->AI_API_CUSTOM_TEMPERATURE, $conf->global->AI_API_CUSTOM_REASONING_EFFORT);
		$ai = new Ai($db);
		$result = $ai->buildPayload('Hello', 'model', 'textgenerationemail');
		$this->assertArrayNotHasKey('temperature', $result['arrayforpayload']);
		$this->assertArrayNotHasKey('reasoning_effort', $result['arrayforpayload']);

		$conf->global->AI_API_CUSTOM_TEMPERATURE = '0.2';
		$conf->global->AI_API_CUSTOM_REASONING_EFFORT = 'none';
		$result = $ai->buildPayload('Hello', 'model', 'textgenerationemail');
		$this->assertSame(0.2, $result['arrayforpayload']['temperature']);
		$this->assertSame('none', $result['arrayforpayload']['reasoning_effort']);

		if ($previousService === '') {
			unset($conf->global->AI_API_SERVICE);
		} else {
			$conf->global->AI_API_SERVICE = $previousService;
		}
		unset($conf->global->AI_API_CUSTOM_TEMPERATURE, $conf->global->AI_API_CUSTOM_REASONING_EFFORT);
	}
}
