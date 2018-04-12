<?php
/**
 * Nextcloud - NextNote
 *
 *
 * @copyright Copyright (c) 2017, Sander Brand (brantje@gmail.com)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\NextNote\Controller;

use OCA\NextNote\Db\Notebook;
use OCA\NextNote\Db\Note;
use OCA\NextNote\Fixtures\ShareFix;
use OCA\NextNote\Service\NotebookService;
use OCA\NextNote\Service\NoteService;
use OCA\NextNote\ShareBackend\NextNoteShareBackend;
use OCA\NextNote\Utility\NotFoundJSONResponse;
use OCA\NextNote\Utility\UnauthorizedJSONResponse;
use OCA\NextNote\Utility\Utils;
use \OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use \OCP\IRequest;
use OCP\IUserManager;
use OCP\Share;


class TranslationController extends ApiController {

	private $trans;

	public function __construct($AppName,
								IRequest $request,
								IL10N $trans
	) {
		parent::__construct(
			$AppName,
			$request,
			'GET, POST, DELETE, PUT, PATCH, OPTIONS',
			'Authorization, Content-Type, Accept',
			86400);
		$this->trans = $trans;
	}

	public function getLanguageStrings() {
		$translations = array(
			//'create.notebook' =>  $this->trans->t('Generating sharing keys ( %s / 2)','%step'),
			'new.notebook' =>  $this->trans->t('New notebook'),
			'not.grouped' =>  $this->trans->t('Not grouped'),
			'all' =>  $this->trans->t('all'),
		);
		return new JSONResponse($translations);
	}
}
