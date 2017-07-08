# NextNote (alpha)
This application is a rewritten verion of ownNote.   
    
Changes:
- Replaced deprecated methods
- Removed XSS vulnerability (Via the announcements it was possible to inject javascript / html)
- Updated tiny MCE to  4.6.4.
- Fixed CSP error in tinymce.
- Ability to embed files from your nextcloud 
- Ability to link to files from your nextcloud
- Make use of Entity's, mappers, services

## Pull requests are very welcome!
The whole app has been rebuild, so there will be some bugs in there.   
Did you found a bug? Report it or fix it and send a PR.

## Features
- Import from Evernote
- Full fledged WYSIWYG editor
- Ability to save files to a folder as HTML files (untested)
- Share a note with a user or group
- Note grouping/categorization
- Archive notes


#Todo:
- [x] Refactor backend to make use of:
  - [x] Entity's
  - [x] Mappers
  - [x] Services
- [x] Switch to a AngularJS frontend
- [ ] Rename namespace from OwnNotes to NextNotes
- [ ] Implement note sharing
- [ ] Implement hierarchical structure for groups (PR's welcome!)
- [ ] Add markdown support
- [ ] Import from [Notes](https://github.com/nextcloud/notes) app.
- [ ] Switch between database or file mode
- [ ] Add admin section for allowed image / video domains due CSP.
- [ ] Encrypted notes? (What about sharing?)
- [ ] Travis tests (We really need help with this, so PR's welcome!)   
   
## Chat
There is a [Gitter](https://gitter.im/nextnotes/Lobby) chatroom available.
 
   
## Installation
- Place this app in **nextcloud/apps/ownnote** (Rename the extracted ZIP to "ownnote" or you will receive errors)
- Note: *custom_csp_policy* changes are no longer required

## Development

NextNotes uses a single `.js` file for the templates.   
This gives the benefit that we don't need to request every template with XHR.
For CSS we use SASS so you need ruby and sass installed.
`templates.js` and the CSS are built with grunt, so don't edit them as your changes will be overwritten next time grunt is ran.   
To watch for changes use `grunt watch`