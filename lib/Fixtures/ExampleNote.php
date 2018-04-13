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

namespace OCA\NextNote\Fixtures;

class ExampleNote {
	const TITLE = 'Welcome to NextNote';
	const NOTE_CONTENT = '<h5 style=text-align:center>Welcome to <a href=https://github.com/brantje/nextnote rel=noopener target=_blank>NextNote</a>! Notes are saved in Rich Text format.</h5>
<p>This app is considered an <strong><span style=background-color:#0ff;color:red>ALPHA</span></strong> release. <strong>Complete note loss is to be expected</strong> until <a href=https://github.com/brantje/nextnote/issues/50>sharing is fixed</a>, <a href=https://github.com/brantje/nextnote/issues/49>a version 1.0 release is added to the Nextcloud Appstore</a> and basic <a href=https://github.com/brantje/nextnote/issues/96 rel=noopener target=_blank>note import/export functionality</a> is implemented.</p>
<h5 style=text-align:center>Thanks for your interest! Still Want to help us test?</h5>
<ol>
<li>Join our <img alt=cool src=../../../apps/nextnote/js/vendor/tinymce/plugins/emoticons/img/smiley-cool.gif> <span style=background-color:#fc9>Telegram Chat</span>: <a href=https://t.me/nextnote>https://t.me/nextnote</a></li>
<li>You'll need SSH or terminal access to update NextNote to the latest build.</li>
<li><code>git pull</code> to update to the latest code -- <a href=https://github.com/brantje/nextnote/commits/master>updates are constant</a>.</li>
<li>Please report any issues with <a href=https://github.com/brantje/nextnote/issues rel=noopener target=_blank>NextNote here</a>.</li>
<li><a href=https://www.tinymce.com rel=noopener target=_blank>TinyMCE</a>, the rich text js library for NextNote, can receive bug reports here. <a href=https://github.com/tinymce/tinymce rel=noopener target=_blank>https://github.com/tinymce/tinymce</a></li>
<li>We appreciate your support!</li>
</ol>
<h5><strong>FAQ’s</strong></h5>
<p><em>Where is flat file/Markdown support, folder support, Etc.?</em></p>
<p style=text-align:left;padding-left:30px>Right now the focus is on stability and sharing. These additional features are on the hopeful roadmap.</p>
<p><em>Are keyboard shortcuts supported?</em></p>
<p style=padding-left:30px>Yes, they are! Just press <span style=font-size:12pt><strong>?</strong></span> in the menu to view them!';
}
